package com.example.aicompanionapp.ui.screens
import com.example.aicompanionapp.data.db.entity.ThreadEntity

data class HistoryScreenUiState(
    val threads: List<ThreadEntity> = emptyList(),
    val isLoading: Boolean = false,
    val error: String? = null
    // val navigateToThreadId: String? = null // Removed this, will use SharedFlow for navigation events
)
