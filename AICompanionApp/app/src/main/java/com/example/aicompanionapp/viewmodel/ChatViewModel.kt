package com.example.aicompanionapp.viewmodel

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.viewModelScope
import com.example.aicompanionapp.data.ResultWrapper
import com.example.aicompanionapp.data.repository.ChatRepository
import com.example.aicompanionapp.ui.screens.ChatScreenUiState
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

// The "new" is a placeholder to signify that a new chat should be created.
const val NEW_CHAT_THREAD_ID_MARKER = "new"

class ChatViewModel(
    private val chatRepository: ChatRepository,
    application: Application, // AndroidViewModel requires Application
    private val savedStateHandle: SavedStateHandle // For accessing navigation arguments (like threadId)
) : AndroidViewModel(application) {

    private val _uiState = MutableStateFlow(ChatScreenUiState(isLoading = true))
    val uiState: StateFlow<ChatScreenUiState> = _uiState.asStateFlow()

    // Get threadId from SavedStateHandle (passed via navigation)
    // This allows the ViewModel to survive process death and restore its state.
    private val openaiThreadIdFromNav: String? = savedStateHandle["threadId"]

    init {
        initializeChat()
    }

    private fun initializeChat() {
        viewModelScope.launch {
            if (openaiThreadIdFromNav != null && openaiThreadIdFromNav != NEW_CHAT_THREAD_ID_MARKER) {
                _uiState.update { it.copy(isLoading = true, currentThreadOpenaiId = openaiThreadIdFromNav) }
                loadChatDetails(openaiThreadIdFromNav)
                observeMessages(openaiThreadIdFromNav)
            } else {
                // This is a new chat or an invalid state, prepare for creation on first message.
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        threadTitle = "New Chat",
                        messages = emptyList(),
                        currentThreadOpenaiId = NEW_CHAT_THREAD_ID_MARKER // Use marker
                    )
                }
            }
        }
    }

    private fun loadChatDetails(threadId: String) {
        viewModelScope.launch {
            // Fetch thread title
            when (val threadResult = chatRepository.getThreadByOpenaiId(threadId)) {
                is ResultWrapper.Success -> {
                    _uiState.update { it.copy(threadTitle = threadResult.data?.title ?: "Chat") }
                }
                is ResultWrapper.Error -> {
                    _uiState.update { it.copy(threadTitle = "Chat", error = "Error loading title: " + threadResult.message) }
                }
            }
            // Refresh messages from remote - this also updates isLoading state internally if needed
            // Or, if refreshMessagesForThread doesn't handle isLoading, do it here.
            // _uiState.update { it.copy(isLoading = true) } // Already true from initializeChat
            when (val refreshResult = chatRepository.refreshMessagesForThread(threadId)) {
                is ResultWrapper.Success -> {
                    // isLoading will be set to false by the message observer if successful
                }
                is ResultWrapper.Error -> {
                    _uiState.update { it.copy(isLoading = false, error = "Error refreshing messages: " + refreshResult.message) }
                }
            }
        }
    }

    private fun observeMessages(threadId: String) {
        viewModelScope.launch {
            chatRepository.getMessagesForThread(threadId)
                .catch { e ->
                    _uiState.update { it.copy(isLoading = false, error = e.message ?: "Error observing messages") }
                }
                .collect { messages ->
                    // Only update if the current thread is still the one being observed
                    if (_uiState.value.currentThreadOpenaiId == threadId) {
                         _uiState.update { it.copy(isLoading = false, messages = messages, error = null) }
                    }
                }
        }
    }

    fun sendMessage(messageContent: String) {
        if (messageContent.isBlank()) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSendingMessage = true) }
            var currentThreadId = _uiState.value.currentThreadOpenaiId

            if (currentThreadId == null || currentThreadId == NEW_CHAT_THREAD_ID_MARKER) {
                // Create new thread first, then send message.
                // Use first part of message as a potential title.
                val newThreadTitle = messageContent.take(30) + if (messageContent.length > 30) "..." else ""
                when (val result = chatRepository.createNewThread(title = newThreadTitle, initialMessage = messageContent)) {
                    is ResultWrapper.Success -> {
                        val newThreadId = result.data
                        // Update SavedStateHandle. This will trigger re-initialization through a new NavController if this VM is tied to NavGraph lifecycle.
                        // Or, more directly, update the currentOpenaiThreadId in the state and re-init parts of the VM.
                        savedStateHandle["threadId"] = newThreadId // This is key for persistence and nav argument updates
                        // After new thread is created by repository (which includes sending the initial message),
                        // the init block's collectLatest on currentOpenaiThreadId (if it were a flow from savedStateHandle)
                        // would re-trigger observation.
                        // For now, manually update state and start observing.
                        _uiState.update { it.copy(isSendingMessage = false, currentThreadOpenaiId = newThreadId, threadTitle = newThreadTitle) }
                        observeMessages(newThreadId) // Start observing messages for the new thread
                        // No need to call refresh, as createNewThread with initialMessage handles it via repository's sendMessage.
                    }
                    is ResultWrapper.Error -> {
                        _uiState.update { it.copy(isSendingMessage = false, error = result.message) }
                    }
                }
            } else {
                // Send message to existing thread
                when (val result = chatRepository.sendMessage(currentThreadId, messageContent)) {
                    is ResultWrapper.Success -> {
                        // Message list will update via flow after refreshMessagesForThread is called by repo's sendMessage.
                        _uiState.update { it.copy(isSendingMessage = false) }
                    }
                    is ResultWrapper.Error -> {
                        _uiState.update { it.copy(isSendingMessage = false, error = result.message) }
                    }
                }
            }
        }
    }

    fun getThreadTitleFromDb(threadId: String) {
        if (threadId == NEW_CHAT_THREAD_ID_MARKER) {
            _uiState.update { it.copy(threadTitle = "New Chat") }
            return
        }
        viewModelScope.launch {
             when (val threadResult = chatRepository.getThreadByOpenaiId(threadId)) {
                is ResultWrapper.Success -> {
                    _uiState.update { it.copy(threadTitle = threadResult.data?.title ?: "Chat") }
                }
                is ResultWrapper.Error -> {
                    _uiState.update { it.copy(threadTitle = "Chat") } // Default on error
                }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
