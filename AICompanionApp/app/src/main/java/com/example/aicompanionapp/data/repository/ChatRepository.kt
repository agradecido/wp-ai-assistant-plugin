package com.example.aicompanionapp.data.repository

import com.example.aicompanionapp.data.ResultWrapper
import com.example.aicompanionapp.data.db.entity.MessageEntity
import com.example.aicompanionapp.data.db.entity.ThreadEntity
import kotlinx.coroutines.flow.Flow

interface ChatRepository {
    fun getAllThreads(): Flow<List<ThreadEntity>>
    fun getMessagesForThread(openaiThreadId: String): Flow<List<MessageEntity>>

    // Fetches a single thread by its OpenAI ID from the local database.
    suspend fun getThreadByOpenaiId(openaiThreadId: String): ResultWrapper<ThreadEntity?>

    // Creates a new thread, optionally with an initial message.
    // Returns the OpenAI Thread ID.
    suspend fun createNewThread(title: String = "New Chat", initialMessage: String? = null): ResultWrapper<String>

    // Sends a user message to a thread, triggers assistant run, and fetches response.
    suspend fun sendMessage(openaiThreadId: String, userMessageContent: String): ResultWrapper<Unit>

    // Fetches latest messages for a thread from API and updates local DB.
    suspend fun refreshMessagesForThread(openaiThreadId: String): ResultWrapper<Unit>

    // Deletes a thread locally (and potentially remotely in the future).
    suspend fun deleteThread(openaiThreadId: String): ResultWrapper<Unit>

    // Updates the title of an existing thread.
    suspend fun updateThreadTitle(openaiThreadId: String, newTitle: String): ResultWrapper<Unit>
}
