package com.example.aicompanionapp.viewmodel

import android.app.Application
import androidx.lifecycle.SavedStateHandle
import app.cash.turbine.test
import com.example.aicompanionapp.data.ResultWrapper
import com.example.aicompanionapp.data.db.entity.MessageEntity
import com.example.aicompanionapp.data.db.entity.ThreadEntity
import com.example.aicompanionapp.data.repository.ChatRepository
import com.example.aicompanionapp.ui.screens.ChatScreenUiState // Import correct UiState
import com.example.aicompanionapp.util.MainCoroutineRule
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.runTest
import org.junit.Assert.*
import org.junit.Before
import org.junit.Rule
import org.junit.Test
import org.mockito.Mock
import org.mockito.Mockito.*
import org.mockito.MockitoAnnotations

@ExperimentalCoroutinesApi
class ChatViewModelTest {

    @get:Rule
    val mainCoroutineRule = MainCoroutineRule()

    @Mock
    private lateinit var mockChatRepository: ChatRepository

    @Mock
    private lateinit var mockApplication: Application

    // SavedStateHandle needs to be mocked carefully.
    // We can't directly mock its get/set for flows easily without more complex setup.
    // A common approach is to pass a real, but controlled, SavedStateHandle.
    private lateinit var savedStateHandle: SavedStateHandle


    private lateinit var viewModel: ChatViewModel

    @Before
    fun setUp() {
        MockitoAnnotations.openMocks(this)
        // Initialize SavedStateHandle for each test.
        // This allows setting initial nav args like "threadId".
    }

    private fun initializeViewModel(initialThreadId: String?) {
        savedStateHandle = SavedStateHandle() // Create a fresh handle
        if (initialThreadId != null) {
            savedStateHandle["threadId"] = initialThreadId
        }
        viewModel = ChatViewModel(mockChatRepository, mockApplication, savedStateHandle)
    }

    @Test
    fun `init with NEW_CHAT_THREAD_ID_MARKER sets new chat state`() = runTest {
        initializeViewModel(NEW_CHAT_THREAD_ID_MARKER)

        viewModel.uiState.test {
            val expectedState = ChatScreenUiState(
                isLoading = false, // Should be false as it's a new chat, no loading needed
                threadTitle = "New Chat",
                messages = emptyList(),
                currentThreadOpenaiId = NEW_CHAT_THREAD_ID_MARKER
            )
            assertEquals(expectedState, awaitItem())
            cancelAndIgnoreRemainingEvents()
        }
    }

    @Test
    fun `init with existing threadId loads details and messages`() = runTest {
        val threadId = "existing_thread_id"
        val threadEntity = ThreadEntity(localId = 1, openaiThreadId = threadId, title = "Existing Chat", createdAt = 0, lastModifiedAt = 0)
        val messages = listOf(MessageEntity(localId = 1, threadId = threadId, openaiMessageId = "msg1", role = "user", content = "Hello", createdAt = 0))

        `when`(mockChatRepository.getThreadByOpenaiId(threadId)).thenReturn(ResultWrapper.Success(threadEntity))
        `when`(mockChatRepository.refreshMessagesForThread(threadId)).thenReturn(ResultWrapper.Success(Unit))
        `when`(mockChatRepository.getMessagesForThread(threadId)).thenReturn(flowOf(messages))

        initializeViewModel(threadId)

        viewModel.uiState.test {
            // Initial state (isLoading = true from constructor)
            var state = awaitItem()
            assertTrue(state.isLoading)
            assertEquals(threadId, state.currentThreadOpenaiId)

            // After loading details and messages
            // Multiple updates might occur: title, then messages, isLoading false.
            // Turbine's `awaitItem` consumes one by one.

            // Skip intermediate states until messages are loaded and isLoading is false
            val finalState = expectMostRecentItem()

            assertEquals(threadId, finalState.currentThreadOpenaiId)
            assertEquals("Existing Chat", finalState.threadTitle)
            assertEquals(messages, finalState.messages)
            assertFalse(finalState.isLoading)

            cancelAndIgnoreRemainingEvents()
        }

        verify(mockChatRepository).getThreadByOpenaiId(threadId)
        verify(mockChatRepository).refreshMessagesForThread(threadId)
        verify(mockChatRepository).getMessagesForThread(threadId)
    }

    @Test
    fun `sendMessage to new thread creates thread and updates state`() = runTest {
        val userMessage = "Hello, new world!"
        val newThreadId = "generated_thread_id"
        val expectedTitle = userMessage.take(30) // As per ViewModel logic

        // Initial setup for a "new" chat
        initializeViewModel(NEW_CHAT_THREAD_ID_MARKER)

        `when`(mockChatRepository.createNewThread(title = expectedTitle, initialMessage = userMessage))
            .thenReturn(ResultWrapper.Success(newThreadId))
        // Assume getMessagesForThread will be called for the newThreadId after creation
        `when`(mockChatRepository.getMessagesForThread(newThreadId)).thenReturn(flowOf(emptyList())) // Or messages if createNewThread populates them

        viewModel.uiState.test {
            // Initial "new chat" state
            assertEquals(ChatScreenUiState(isLoading = false, threadTitle = "New Chat", currentThreadOpenaiId = NEW_CHAT_THREAD_ID_MARKER), awaitItem())

            viewModel.sendMessage(userMessage)

            // State during message sending
            var sendingState = awaitItem()
            assertTrue(sendingState.isSendingMessage)
            assertEquals(NEW_CHAT_THREAD_ID_MARKER, sendingState.currentThreadOpenaiId) // Still new marker initially

            // State after thread creation and message sending finishes
            // The ViewModel updates SavedStateHandle, which should trigger a new UI state emission reflecting the new thread ID.
            // This is the tricky part to test without deeper SavedStateHandle mocking or observing its changes.
            // We expect currentThreadOpenaiId to change to newThreadId.
            // And isSendingMessage to be false.
            // The flow for messages on the newThreadId will also start.

            // Let's verify the repository call first
            verify(mockChatRepository).createNewThread(title = expectedTitle, initialMessage = userMessage)

            // After sendMessage, the ViewModel updates savedStateHandle["threadId"] = newThreadId
            // and then _uiState for isSendingMessage, currentThreadOpenaiId, and threadTitle.
            // Then it calls observeMessages(newThreadId).

            // Expecting a state where sending is false, and threadId is updated
            var finalState = awaitItem() // This should be the state where isSendingMessage is false
                                         // and currentThreadOpenaiId = newThreadId
            if(finalState.isSendingMessage) finalState = awaitItem() // Consume another if the previous was still sending=true

            assertFalse(finalState.isSendingMessage)
            assertEquals(newThreadId, finalState.currentThreadOpenaiId)
            assertEquals(expectedTitle, finalState.threadTitle)

            // Verify that message observation starts for the new thread
            verify(mockChatRepository).getMessagesForThread(newThreadId)

            cancelAndIgnoreRemainingEvents()
        }
    }

    @Test
    fun `sendMessage to existing thread calls repository and updates state`() = runTest {
        val threadId = "existing_thread_id"
        val userMessage = "Another message"
        initializeViewModel(threadId) // Assume this thread is already loaded

        `when`(mockChatRepository.sendMessage(threadId, userMessage)).thenReturn(ResultWrapper.Success(Unit))
        // Assume getMessagesForThread is already active from init. sendMessage in repo calls refreshMessagesForThread.

        viewModel.uiState.test {
            // Skip initial loading states from init
            skipItems(1) // Or more, depending on how many states init produces before stable

            viewModel.sendMessage(userMessage)

            // State during message sending
            var sendingState = awaitItem()
            assertTrue(sendingState.isSendingMessage)
            assertEquals(threadId, sendingState.currentThreadOpenaiId)

            // State after message sending finishes
            var finalState = awaitItem()
             if(finalState.isSendingMessage) finalState = awaitItem() // Consume intermediate if any

            assertFalse(finalState.isSendingMessage)

            cancelAndIgnoreRemainingEvents()
        }
        verify(mockChatRepository).sendMessage(threadId, userMessage)
    }

    @Test
    fun `sendMessage failure updates error state`() = runTest {
        val threadId = "existing_thread_id"
        val userMessage = "Test message"
        val errorMessage = "Failed to send"
        initializeViewModel(threadId)

        `when`(mockChatRepository.sendMessage(threadId, userMessage)).thenReturn(ResultWrapper.Error(errorMessage))

        viewModel.uiState.test {
            skipItems(1) // Skip initial loading states

            viewModel.sendMessage(userMessage)

            var sendingState = awaitItem() // isSendingMessage = true
            assertTrue(sendingState.isSendingMessage)

            var errorState = awaitItem() // isSendingMessage = false, error = "Failed to send"
            if(errorState.isSendingMessage) errorState = awaitItem()


            assertFalse(errorState.isSendingMessage)
            assertEquals(errorMessage, errorState.error)

            cancelAndIgnoreRemainingEvents()
        }
    }

    @Test
    fun `clearError nullifies error in uiState`() = runTest {
        initializeViewModel(null) // No specific thread needed for this
        // Manually set an error state
        (viewModel.uiState as MutableStateFlow).value = ChatScreenUiState(error = "Initial Error")

        viewModel.clearError()

        assertNull(viewModel.uiState.value.error)
    }
}
