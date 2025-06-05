package com.example.aicompanionapp.ui.screens
import com.example.aicompanionapp.data.db.entity.MessageEntity
// import com.example.aicompanionapp.data.db.entity.ThreadEntity // Not directly holding ThreadEntity here

data class ChatScreenUiState(
    val messages: List<MessageEntity> = emptyList(),
    val currentThreadOpenaiId: String? = null, // The ID of the thread being viewed
    val threadTitle: String = "Chat", // Title of the current thread
    val isLoading: Boolean = false, // For initial loading of messages or thread details
    val isSendingMessage: Boolean = false, // True when a message is being sent (API call in progress)
    val error: String? = null
)
