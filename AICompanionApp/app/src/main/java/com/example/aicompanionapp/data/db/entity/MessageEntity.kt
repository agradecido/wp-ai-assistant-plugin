package com.example.aicompanionapp.data.db.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
    tableName = "messages",
    foreignKeys = [
        ForeignKey(
            entity = ThreadEntity::class,
            parentColumns = ["openai_thread_id"], // Referencing openai_thread_id in ThreadEntity
            childColumns = ["thread_id"],         // This entity's column that is the foreign key
            onDelete = ForeignKey.CASCADE        // If a thread is deleted, its messages are also deleted
        )
    ],
    // Index on thread_id for faster querying of messages for a thread.
    // Index on openai_message_id to ensure messages from API are unique and for quick lookups.
    indices = [Index(value = ["thread_id"]), Index(value = ["openai_message_id"], unique = true)]
)
data class MessageEntity(
    @PrimaryKey(autoGenerate = true)
    val localId: Long = 0,

    @ColumnInfo(name = "thread_id") // This stores the openaiThreadId from ThreadEntity
    val threadId: String,

    @ColumnInfo(name = "openai_message_id") // OpenAI's own ID for the message
    val openaiMessageId: String,

    val role: String, // "user" or "assistant"
    val content: String, // The text content of the message
    val createdAt: Long, // Timestamp of message creation on OpenAI

    // Optional: Add fields for message status if needed (e.g., pending, sent, failed)
    // val status: String? = null,

    // Optional: Add fields for local-only messages or UI states
    // val isLocal: Boolean = false
)
