package com.example.aicompanionapp.data.db.entity

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
    tableName = "threads",
    indices = [Index(value = ["openai_thread_id"], unique = true)]
)
data class ThreadEntity(
    @PrimaryKey(autoGenerate = true)
    val localId: Long = 0,

    @ColumnInfo(name = "openai_thread_id")
    val openaiThreadId: String,

    val title: String, // User-defined or auto-generated title for the thread
    val createdAt: Long, // Timestamp of creation on OpenAI
    var lastModifiedAt: Long // Timestamp of last message or user interaction, for sorting
)
