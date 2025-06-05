package com.example.aicompanionapp.viewmodel

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.example.aicompanionapp.data.ResultWrapper
import com.example.aicompanionapp.data.repository.ChatRepository
import com.example.aicompanionapp.ui.screens.HistoryScreenUiState
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

class HistoryViewModel(
    private val chatRepository: ChatRepository,
    application: Application // AndroidViewModel requires Application context
) : AndroidViewModel(application) {

    private val _uiState = MutableStateFlow(HistoryScreenUiState(isLoading = true))
    val uiState: StateFlow<HistoryScreenUiState> = _uiState.asStateFlow()

    // Using SharedFlow for one-time navigation events.
    // This is generally preferred over exposing state in UiState for navigation triggers.
    private val _navigateToThreadEvent = MutableSharedFlow<String>()
    val navigateToThreadEvent: SharedFlow<String> = _navigateToThreadEvent.asSharedFlow()

    init {
        loadThreads()
    }

    private fun loadThreads() {
        viewModelScope.launch {
            // Set initial loading state
            _uiState.update { it.copy(isLoading = true) }
            chatRepository.getAllThreads()
                // Note: .onStart {} is for Flow cold start, not necessarily beginning of data emission here.
                // isLoading=true was set above.
                .catch { e ->
                    _uiState.update { it.copy(isLoading = false, error = e.message ?: "Unknown error loading threads") }
                }
                .collect { threads ->
                    _uiState.update { it.copy(isLoading = false, threads = threads, error = null) }
                }
        }
    }

    fun createNewThread(title: String = "New Conversation") {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) } // Indicate loading for the create operation
            when (val result = chatRepository.createNewThread(title = title, initialMessage = null)) { // Assuming initial message is not sent from history
                is ResultWrapper.Success -> {
                    // The list of threads will update automatically due to the Flow from getAllThreads.
                    // Emit event to navigate to the newly created thread.
                    _navigateToThreadEvent.emit(result.data)
                    // isLoading will be reset by the flow collection or if an error occurs.
                    // If createNewThread doesn't trigger list update fast enough, manually set isLoading false here.
                    _uiState.update { it.copy(isLoading = false) }
                }
                is ResultWrapper.Error -> {
                    _uiState.update { it.copy(isLoading = false, error = result.message) }
                }
            }
        }
    }

    fun deleteThread(openaiThreadId: String) {
        viewModelScope.launch {
            // Optionally, show a specific loading state for deletion if it's a long operation
            // _uiState.update { it.copy(isLoading = true) } // Or a specific 'isDeleting' flag
            when (val result = chatRepository.deleteThread(openaiThreadId)) {
                is ResultWrapper.Success -> {
                    // List will update via Flow.
                    // Any UI confirmation (e.g., Snackbar) could be triggered via another SharedFlow event.
                    // _uiState.update { it.copy(isLoading = false) } // Reset loading if it was set
                }
                is ResultWrapper.Error -> {
                    _uiState.update { it.copy(error = result.message /*, isLoading = false */) }
                }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
