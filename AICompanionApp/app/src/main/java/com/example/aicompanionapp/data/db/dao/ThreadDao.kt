package com.example.aicompanionapp.data.db.dao

import androidx.room.*
import com.example.aicompanionapp.data.db.entity.ThreadEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface ThreadDao {
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertOrUpdateThread(thread: ThreadEntity): Long // Returns localId

    @Query("SELECT * FROM threads WHERE openai_thread_id = :openaiThreadId")
    suspend fun getThreadByOpenaiId(openaiThreadId: String): ThreadEntity?

    // Get all threads, ordered by when they were last modified (e.g., new message added)
    @Query("SELECT * FROM threads ORDER BY lastModifiedAt DESC")
    fun getAllThreads(): Flow<List<ThreadEntity>>

    // Update the lastModifiedAt timestamp for a thread, useful when new messages are added
    @Query("UPDATE threads SET lastModifiedAt = :timestamp WHERE openai_thread_id = :openaiThreadId")
    suspend fun updateThreadLastModified(openaiThreadId: String, timestamp: Long)

    @Query("DELETE FROM threads WHERE openai_thread_id = :openaiThreadId")
    suspend fun deleteThreadByOpenaiId(openaiThreadId: String): Int // Returns number of rows affected

    @Query("SELECT EXISTS(SELECT 1 FROM threads WHERE openai_thread_id = :openaiThreadId LIMIT 1)")
    suspend fun threadExists(openaiThreadId: String): Boolean

    // You might also want a method to get a thread by its local ID if needed for UI interactions
    @Query("SELECT * FROM threads WHERE localId = :localId")
    suspend fun getThreadByLocalId(localId: Long): ThreadEntity?

    // Update the title of a thread
    @Query("UPDATE threads SET title = :newTitle WHERE openai_thread_id = :openaiThreadId")
    suspend fun updateThreadTitle(openaiThreadId: String, newTitle: String)
}
