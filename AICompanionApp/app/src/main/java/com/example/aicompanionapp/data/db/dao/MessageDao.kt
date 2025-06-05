package com.example.aicompanionapp.data.db.dao

import androidx.room.*
import com.example.aicompanionapp.data.db.entity.MessageEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface MessageDao {
    // Using OnConflictStrategy.REPLACE in case we fetch a message that already exists (e.g., during sync)
    // This will update it based on its openai_message_id.
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertMessage(message: MessageEntity): Long // Returns localId

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertMessages(messages: List<MessageEntity>)

    // Get messages for a specific thread (using openai_thread_id), ordered by creation time
    // to display them chronologically.
    @Query("SELECT * FROM messages WHERE thread_id = :openaiThreadId ORDER BY createdAt ASC")
    fun getMessagesForThread(openaiThreadId: String): Flow<List<MessageEntity>>

    // Get messages for a specific thread (using openai_thread_id), ordered by creation time (descending)
    // Useful for getting the latest message.
    @Query("SELECT * FROM messages WHERE thread_id = :openaiThreadId ORDER BY createdAt DESC")
    fun getMessagesForThreadDescending(openaiThreadId: String): Flow<List<MessageEntity>>


    @Query("DELETE FROM messages WHERE thread_id = :openaiThreadId")
    suspend fun deleteMessagesForThread(openaiThreadId: String): Int // Returns number of rows affected

    @Query("SELECT * FROM messages WHERE openai_message_id = :openaiMessageId")
    suspend fun getMessageByOpenaiId(openaiMessageId: String): MessageEntity?

    // Delete a specific message by its openai_message_id
    @Query("DELETE FROM messages WHERE openai_message_id = :openaiMessageId")
    suspend fun deleteMessageByOpenaiId(openaiMessageId: String): Int

    // Get the latest message for a given thread
    @Query("SELECT * FROM messages WHERE thread_id = :openaiThreadId ORDER BY createdAt DESC LIMIT 1")
    suspend fun getLatestMessageForThread(openaiThreadId: String): MessageEntity?
}
