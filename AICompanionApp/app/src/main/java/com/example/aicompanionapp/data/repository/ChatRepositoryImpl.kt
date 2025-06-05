package com.example.aicompanionapp.data.repository

import android.util.Log
import com.example.aicompanionapp.api.ApiClient
import com.example.aicompanionapp.api.OpenAIApiService
import com.example.aicompanionapp.data.ResultWrapper
import com.example.aicompanionapp.data.db.dao.MessageDao
import com.example.aicompanionapp.data.db.dao.ThreadDao
import com.example.aicompanionapp.data.db.entity.MessageEntity
import com.example.aicompanionapp.data.db.entity.ThreadEntity
import com.example.aicompanionapp.data.model.AddMessageRequest
import com.example.aicompanionapp.data.model.RunRequest
// Import CreateThreadRequest if you need to pass metadata, though default is empty.
// import com.example.aicompanionapp.data.model.CreateThreadRequest
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import java.util.Date // Preferred over java.sql.Date for modern Kotlin
import java.util.UUID // For generating temporary local IDs if needed

class ChatRepositoryImpl(
    private val threadDao: ThreadDao,
    private val messageDao: MessageDao,
    private val openAIApiService: OpenAIApiService
) : ChatRepository {

    // It's crucial that ApiClient.OPENAI_API_KEY is correctly configured and replaced.
    // Using "Bearer " prefix as per OpenAI API authentication scheme.
    private val apiKey = "Bearer ${ApiClient.OPENAI_API_KEY}"
    private val assistantId = ApiClient.ASSISTANT_ID
    private val defaultBetaHeader = OpenAIApiService.OPENAI_BETA_HEADER

    companion object {
        private const val TAG = "ChatRepositoryImpl"
        private const val MAX_RUN_POLL_ATTEMPTS = 25 // Increased slightly
        private const val RUN_POLL_DELAY_MS = 2000L // 2 seconds
    }

    override fun getAllThreads(): Flow<List<ThreadEntity>> = threadDao.getAllThreads()

    override fun getMessagesForThread(openaiThreadId: String): Flow<List<MessageEntity>> =
        messageDao.getMessagesForThread(openaiThreadId)

    override suspend fun getThreadByOpenaiId(openaiThreadId: String): ResultWrapper<ThreadEntity?> = withContext(Dispatchers.IO) {
        try {
            val thread = threadDao.getThreadByOpenaiId(openaiThreadId)
            ResultWrapper.Success(thread)
        } catch (e: Exception) {
            Log.e(TAG, "Error getting thread by OpenAI ID: $openaiThreadId", e)
            ResultWrapper.Error("Failed to get thread from DB: ${e.message}", e)
        }
    }

    override suspend fun createNewThread(title: String, initialMessage: String?): ResultWrapper<String> = withContext(Dispatchers.IO) {
        try {
            // For createThread, the request body can be empty or contain metadata.
            // Using the default CreateThreadRequest() which is an empty body.
            val response = openAIApiService.createThread(apiKey = apiKey, betaHeader = defaultBetaHeader)
            if (response.isSuccessful && response.body() != null) {
                val openaiThreadId = response.body()!!.id
                val now = Date().time
                val newThread = ThreadEntity(
                    openaiThreadId = openaiThreadId,
                    title = title.ifBlank { "New Chat @ ${Date(now)}" }, // Default title if blank
                    createdAt = response.body()!!.created_at * 1000, // API returns seconds
                    lastModifiedAt = now
                )
                threadDao.insertOrUpdateThread(newThread)

                if (!initialMessage.isNullOrBlank()) {
                    // Send the initial message. This will also trigger run and refresh.
                    // Note: sendMessage is a suspend function and will complete its own ResultWrapper.
                    // We might want to chain this differently or ensure its success.
                    // For now, fire and forget the result of initial message for simplicity of createNewThread return.
                    // A more robust implementation might wait for this or handle its error.
                    sendMessage(openaiThreadId, initialMessage)
                }
                ResultWrapper.Success(openaiThreadId)
            } else {
                val errorBody = response.errorBody()?.string()
                Log.e(TAG, "Create thread API error: ${response.code()} - ${response.message()} - $errorBody")
                ResultWrapper.Error("API Error ${response.code()}: ${response.message()} - $errorBody")
            }
        } catch (e: Exception) {
            Log.e(TAG, "Create thread exception", e)
            ResultWrapper.Error("Failed to create thread: ${e.message}", e)
        }
    }

    override suspend fun sendMessage(openaiThreadId: String, userMessageContent: String): ResultWrapper<Unit> = withContext(Dispatchers.IO) {
        try {
            // 1. Save user message locally (optimistic update)
            // Using UUID for a unique temporary openaiMessageId for local storage before API confirms one.
            // This helps if we needed to update this specific local entry later with the real ID.
            val tempOpenaiMessageId = "local_user_${UUID.randomUUID()}"
            val userMessageEntity = MessageEntity(
                threadId = openaiThreadId,
                openaiMessageId = tempOpenaiMessageId,
                role = "user",
                content = userMessageContent,
                createdAt = Date().time
            )
            messageDao.insertMessage(userMessageEntity)
            threadDao.updateThreadLastModified(openaiThreadId, Date().time)

            // 2. Add message to OpenAI thread
            val addMessageRequest = AddMessageRequest(role = "user", content = userMessageContent)
            val addMsgResponse = openAIApiService.addMessageToThread(apiKey, defaultBetaHeader, openaiThreadId, addMessageRequest)

            if (!addMsgResponse.isSuccessful || addMsgResponse.body() == null) {
                Log.e(TAG, "API Error adding message: ${addMsgResponse.code()} - ${addMsgResponse.message()} - ${addMsgResponse.errorBody()?.string()}")
                // Optional: Update local message status to 'failed'
                return@withContext ResultWrapper.Error("API Error adding message: ${addMsgResponse.code()} - ${addMsgResponse.message()}")
            }
            // Update the local message with the actual OpenAI message ID
            // This requires a DAO method like:
            // @Query("UPDATE messages SET openai_message_id = :newOpenaiId WHERE openai_message_id = :tempOpenaiId")
            // suspend fun updateMessageOpenaiId(tempOpenaiId: String, newOpenaiId: String)
            // For now, we assume insertMessage with OnConflictStrategy.REPLACE on openaiMessageId handles this if we re-fetch.
            // Or, more simply, the refreshMessagesForThread will fetch the new message with its proper ID.


            // 3. Run the assistant
            val runRequest = RunRequest(assistant_id = assistantId) // Instructions can be added here if needed
            val runResponse = openAIApiService.runAssistant(apiKey, defaultBetaHeader, openaiThreadId, runRequest)
            if (!runResponse.isSuccessful || runResponse.body() == null) {
                Log.e(TAG, "API Error running assistant: ${runResponse.code()} - ${runResponse.message()} - ${runResponse.errorBody()?.string()}")
                return@withContext ResultWrapper.Error("API Error running assistant: ${runResponse.code()} - ${runResponse.message()}")
            }
            val runId = runResponse.body()!!.id

            // 4. Poll for run completion
            var attempts = 0
            var currentRunStatus: String?
            while (attempts < MAX_RUN_POLL_ATTEMPTS) {
                delay(RUN_POLL_DELAY_MS)
                val statusResponse = openAIApiService.getRunStatus(apiKey, defaultBetaHeader, openaiThreadId, runId)
                if (!statusResponse.isSuccessful || statusResponse.body() == null) {
                    Log.w(TAG, "Polling: API Error getting run status: ${statusResponse.code()} - ${statusResponse.message()} - ${statusResponse.errorBody()?.string()}")
                    // Decide if to retry or fail here. For now, count as an attempt and continue.
                    attempts++
                    continue
                }
                currentRunStatus = statusResponse.body()!!.status
                Log.d(TAG, "Run ID $runId on thread $openaiThreadId status: $currentRunStatus (Attempt: ${attempts + 1})")

                when (currentRunStatus) {
                    "completed" -> {
                        // 5. Fetch new messages (which includes assistant's response) and store them
                        return@withContext refreshMessagesForThread(openaiThreadId)
                    }
                    "failed", "cancelled", "expired" -> {
                        val errorMsg = statusResponse.body()?.last_error?.message ?: "Run $currentRunStatus"
                        Log.e(TAG, "Run $runId on thread $openaiThreadId $currentRunStatus. Error: $errorMsg")
                        return@withContext ResultWrapper.Error("Assistant run $currentRunStatus: $errorMsg")
                    }
                    "requires_action" -> {
                        // Handle function calling if implemented. For now, it's an unsupported state.
                        Log.w(TAG, "Run $runId on thread $openaiThreadId requires action. This is not yet handled.")
                        return@withContext ResultWrapper.Error("Assistant run requires action (not implemented).")
                    }
                    // Other statuses: "queued", "in_progress" - continue polling
                }
                attempts++
            }
            ResultWrapper.Error("Assistant run timed out after $attempts attempts for run $runId.")

        } catch (e: Exception) {
            Log.e(TAG, "Send message exception for thread $openaiThreadId", e)
            ResultWrapper.Error("Failed to send message: ${e.message}", e)
        }
    }

    override suspend fun refreshMessagesForThread(openaiThreadId: String): ResultWrapper<Unit> = withContext(Dispatchers.IO) {
        try {
            // Fetch all messages since the last known message, or all if none known.
            // For simplicity, fetching a decent limit and replacing/adding.
            // OpenAI's default order is 'desc' (latest first).
            val messagesResponse = openAIApiService.getThreadMessages(apiKey, defaultBetaHeader, openaiThreadId, limit = 100, order = "asc")

            if (messagesResponse.isSuccessful && messagesResponse.body() != null) {
                val apiMessages = messagesResponse.body()!!.data
                val messageEntities = apiMessages.mapNotNull { msg ->
                    val contentValue = msg.content.firstOrNull { it.type == "text" }?.text?.value
                    if (contentValue.isNullOrBlank() && msg.role == "assistant") { // Skip assistant messages with no text content part
                        Log.w(TAG, "Assistant message ${msg.id} has no text content. Skipping.")
                        null
                    } else {
                        MessageEntity(
                            threadId = openaiThreadId, // Ensure this is openaiThreadId from parameter
                            openaiMessageId = msg.id,
                            role = msg.role,
                            content = contentValue ?: "", // Use empty string if content is somehow null after check
                            createdAt = msg.created_at * 1000 // API returns seconds, convert to ms
                        )
                    }
                }

                if (messageEntities.isNotEmpty()) {
                    // Using insertMessages which should have OnConflictStrategy.REPLACE
                    // based on openaiMessageId (if MessageEntity primary key or unique index is openaiMessageId)
                    // My MessageEntity has localId as PK and unique index on openai_message_id.
                    // So, insertMessages with OnConflictStrategy.REPLACE will work as intended.
                    messageDao.insertMessages(messageEntities)
                    threadDao.updateThreadLastModified(openaiThreadId, Date().time) // Update thread's last modified time
                }
                ResultWrapper.Success(Unit)
            } else {
                val errorBody = messagesResponse.errorBody()?.string()
                Log.e(TAG, "Refresh messages API error: ${messagesResponse.code()} - ${messagesResponse.message()} - $errorBody")
                ResultWrapper.Error("API Error ${messagesResponse.code()}: ${messagesResponse.message()} - $errorBody")
            }
        } catch (e: Exception) {
            Log.e(TAG, "Refresh messages exception for thread $openaiThreadId", e)
            ResultWrapper.Error("Failed to refresh messages: ${e.message}", e)
        }
    }

    override suspend fun updateThreadTitle(openaiThreadId: String, newTitle: String): ResultWrapper<Unit> = withContext(Dispatchers.IO) {
        try {
            // threadDao.updateThreadTitle(openaiThreadId, newTitle) // Assumes DAO has this specific update
            // More general approach if only insertOrUpdateThread is available:
            val existingThread = threadDao.getThreadByOpenaiId(openaiThreadId)
            if (existingThread != null) {
                threadDao.insertOrUpdateThread(existingThread.copy(title = newTitle, lastModifiedAt = Date().time))
                ResultWrapper.Success(Unit)
            } else {
                ResultWrapper.Error("Thread with ID $openaiThreadId not found locally.")
            }
        } catch (e: Exception) {
            Log.e(TAG, "Error updating thread title for $openaiThreadId", e)
            ResultWrapper.Error("Database error updating title: ${e.message}", e)
        }
    }

    override suspend fun deleteThread(openaiThreadId: String): ResultWrapper<Unit> = withContext(Dispatchers.IO) {
        try {
            val deletedRows = threadDao.deleteThreadByOpenaiId(openaiThreadId) // This also cascades in DB
            if (deletedRows > 0) {
                Log.i(TAG, "Thread $openaiThreadId deleted locally.")
                // Optional: Attempt to delete from OpenAI server.
                // val deleteResponse = openAIApiService.deleteThread(apiKey, defaultBetaHeader, openaiThreadId)
                // if (deleteResponse.isSuccessful) {
                //     Log.i(TAG, "Thread $openaiThreadId deleted from OpenAI server.")
                // } else {
                //     Log.w(TAG, "Failed to delete thread $openaiThreadId from OpenAI server: ${deleteResponse.code()} - ${deleteResponse.errorBody()?.string()}")
                //     // Decide if this should make the overall operation an error. For now, local success is enough.
                // }
                ResultWrapper.Success(Unit)
            } else {
                Log.w(TAG, "Thread $openaiThreadId not found locally for deletion.")
                ResultWrapper.Error("Thread not found locally.")
            }
        } catch (e: Exception) {
            Log.e(TAG, "Delete thread exception for $openaiThreadId", e)
            ResultWrapper.Error("Failed to delete thread: ${e.message}", e)
        }
    }
}
