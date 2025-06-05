package com.example.aicompanionapp.viewmodel

import android.app.Application
import app.cash.turbine.test
import com.example.aicompanionapp.data.ResultWrapper
import com.example.aicompanionapp.data.db.entity.ThreadEntity
import com.example.aicompanionapp.data.repository.ChatRepository
import com.example.aicompanionapp.ui.screens.HistoryScreenUiState // Import correct UiState
import com.example.aicompanionapp.util.MainCoroutineRule
import kotlinx.coroutines.ExperimentalCoroutinesApi
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
class HistoryViewModelTest {

    @get:Rule
    val mainCoroutineRule = MainCoroutineRule()

    @Mock
    private lateinit var mockChatRepository: ChatRepository

    @Mock
    private lateinit var mockApplication: Application // AndroidViewModel constructor needs Application

    private lateinit var viewModel: HistoryViewModel

    @Before
    fun setUp() {
        MockitoAnnotations.openMocks(this)
        // Mock any Application context calls if your ViewModel uses them directly (e.g., getString)
        // `when`(mockApplication.getString(anyInt())).thenReturn("Mocked String")
    }

    @Test
    fun `loadThreads success updates uiState with threads`() = runTest {
        val threads = listOf(ThreadEntity(localId = 1, openaiThreadId = "t1", title = "Thread 1", createdAt = 0, lastModifiedAt = 0))
        `when`(mockChatRepository.getAllThreads()).thenReturn(flowOf(threads))

        viewModel = HistoryViewModel(mockChatRepository, mockApplication)

        viewModel.uiState.test {
            // Initial state from ViewModel's constructor/init block
            assertEquals(HistoryScreenUiState(isLoading = true, threads = emptyList()), awaitItem())
            // State after collecting threads
            assertEquals(HistoryScreenUiState(isLoading = false, threads = threads), awaitItem())
            cancelAndIgnoreRemainingEvents()
        }
        verify(mockChatRepository).getAllThreads()
    }

    @Test
    fun `loadThreads failure updates uiState with error`() = runTest {
        val errorMessage = "Failed to load threads"
        `when`(mockChatRepository.getAllThreads()).thenReturn(kotlinx.coroutines.flow.flow { throw RuntimeException(errorMessage) })

        viewModel = HistoryViewModel(mockChatRepository, mockApplication)

        viewModel.uiState.test {
            assertEquals(HistoryScreenUiState(isLoading = true, threads = emptyList()), awaitItem())
            assertEquals(HistoryScreenUiState(isLoading = false, error = errorMessage, threads = emptyList()), awaitItem())
            cancelAndIgnoreRemainingEvents()
        }
        verify(mockChatRepository).getAllThreads()
    }

    @Test
    fun `createNewThread success emits navigateToThreadEvent and resets loading`() = runTest {
        val newThreadId = "new_thread_id_123"
        // Initial state of threads for the loadThreads() call in init {}
        `when`(mockChatRepository.getAllThreads()).thenReturn(flowOf(emptyList()))
        `when`(mockChatRepository.createNewThread(title = anyString(), initialMessage = eq(null))).thenReturn(ResultWrapper.Success(newThreadId))

        viewModel = HistoryViewModel(mockChatRepository, mockApplication)

        // Consume initial states from uiState if necessary before testing the event
        viewModel.uiState.test {
            awaitItem() // isLoading = true
            awaitItem() // isLoading = false, threads = emptyList()
        }


        viewModel.navigateToThreadEvent.test {
            viewModel.createNewThread("New Test Chat")
            assertEquals(newThreadId, awaitItem()) // Check navigation event
            cancelAndIgnoreRemainingEvents()
        }

        // Verify repository call
        verify(mockChatRepository).createNewThread(title = "New Test Chat", initialMessage = null)

        // Verify final UI state (isLoading should be false)
        assertEquals(false, viewModel.uiState.value.isLoading)
        assertEquals(null, viewModel.uiState.value.error) // No error should be present
    }

    @Test
    fun `createNewThread failure updates error state and resets loading`() = runTest {
        val errorMessage = "Failed to create thread"
        `when`(mockChatRepository.getAllThreads()).thenReturn(flowOf(emptyList())) // For init
        `when`(mockChatRepository.createNewThread(title = anyString(), initialMessage = eq(null))).thenReturn(ResultWrapper.Error(errorMessage))

        viewModel = HistoryViewModel(mockChatRepository, mockApplication)

        viewModel.uiState.test {
            assertEquals(HistoryScreenUiState(isLoading = true), awaitItem()) // Initial from init
            assertEquals(HistoryScreenUiState(isLoading = false, threads = emptyList()), awaitItem()) // After init load

            viewModel.createNewThread("Test Chat")

            // Expect state update for starting creation (isLoading = true)
            assertEquals(HistoryScreenUiState(isLoading = true, threads = emptyList()), awaitItem())
            // Expect state update for error
            assertEquals(HistoryScreenUiState(isLoading = false, threads = emptyList(), error = errorMessage), awaitItem())
            cancelAndIgnoreRemainingEvents()
        }
        verify(mockChatRepository).createNewThread(title = "Test Chat", initialMessage = null)
    }

    @Test
    fun `deleteThread success (list updates via flow)`() = runTest {
        val threadIdToDelete = "t1"
        `when`(mockChatRepository.getAllThreads()).thenReturn(flowOf(emptyList())) // For init
        `when`(mockChatRepository.deleteThread(threadIdToDelete)).thenReturn(ResultWrapper.Success(Unit))

        viewModel = HistoryViewModel(mockChatRepository, mockApplication)
        viewModel.uiState.test{ skipItems(2) } // Skip initial states

        viewModel.deleteThread(threadIdToDelete)

        verify(mockChatRepository).deleteThread(threadIdToDelete)
        // Assert that error state is not set
        assertNull(viewModel.uiState.value.error)
        // State of threads list itself is tested by observing getAllThreads elsewhere or by assuming it works.
    }

    @Test
    fun `deleteThread failure updates error state`() = runTest {
        val threadIdToDelete = "t1"
        val errorMessage = "Failed to delete"
        `when`(mockChatRepository.getAllThreads()).thenReturn(flowOf(emptyList())) // For init
        `when`(mockChatRepository.deleteThread(threadIdToDelete)).thenReturn(ResultWrapper.Error(errorMessage))

        viewModel = HistoryViewModel(mockChatRepository, mockApplication)
        viewModel.uiState.test{ skipItems(2) } // Skip initial states

        viewModel.deleteThread(threadIdToDelete)

        verify(mockChatRepository).deleteThread(threadIdToDelete)
        assertEquals(errorMessage, viewModel.uiState.value.error)
    }

    @Test
    fun `clearError nullifies error in uiState`() = runTest {
        // Setup initial state with an error
        `when`(mockChatRepository.getAllThreads()).thenReturn(flowOf(emptyList()))
        viewModel = HistoryViewModel(mockChatRepository, mockApplication)
        // Manually set an error state for testing clearError
        (viewModel.uiState as MutableStateFlow).value = HistoryScreenUiState(error = "Initial Error")

        viewModel.clearError()

        assertNull(viewModel.uiState.value.error)
    }
}
