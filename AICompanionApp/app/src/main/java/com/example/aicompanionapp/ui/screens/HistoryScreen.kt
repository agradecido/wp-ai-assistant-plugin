package com.example.aicompanionapp.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.example.aicompanionapp.data.db.entity.ThreadEntity
import com.example.aicompanionapp.ui.Screen
import com.example.aicompanionapp.ui.theme.AICompanionAppTheme
import com.example.aicompanionapp.viewmodel.AppViewModelFactory
import com.example.aicompanionapp.viewmodel.HistoryViewModel
import com.example.aicompanionapp.viewmodel.NEW_CHAT_THREAD_ID_MARKER
import kotlinx.coroutines.flow.collectLatest
import java.text.SimpleDateFormat
import java.util.*
import android.app.Application

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HistoryScreen(
    navController: NavController?, // Nullable for preview
    viewModel: HistoryViewModel
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val context = LocalContext.current

    LaunchedEffect(Unit) {
        viewModel.navigateToThreadEvent.collectLatest { threadId ->
            navController?.navigate(Screen.Chat.createRoute(threadId))
        }
    }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(
                message = it,
                duration = SnackbarDuration.Short
            )
            viewModel.clearError() // Clear error after showing
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Conversations") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        },
        floatingActionButton = {
            FloatingActionButton(
                onClick = { viewModel.createNewThread("New Conversation") },
                containerColor = MaterialTheme.colorScheme.tertiaryContainer,
                contentColor = MaterialTheme.colorScheme.onTertiaryContainer
            ) {
                Icon(Icons.Filled.Add, contentDescription = "New Chat")
            }
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            if (uiState.isLoading && uiState.threads.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (!uiState.isLoading && uiState.threads.isEmpty()) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        "No conversations yet. Tap '+' to start!",
                        style = MaterialTheme.typography.bodyLarge,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize()
                ) {
                    items(uiState.threads, key = { it.openaiThreadId }) { thread ->
                        ThreadListItem(
                            thread = thread,
                            onClick = {
                                navController?.navigate(Screen.Chat.createRoute(thread.openaiThreadId))
                            },
                            onDelete = { viewModel.deleteThread(thread.openaiThreadId) }
                        )
                        Divider(thickness = 0.5.dp, color = MaterialTheme.colorScheme.outline.copy(alpha = 0.5f))
                    }
                }
            }
        }
    }
}

@Composable
fun ThreadListItem(thread: ThreadEntity, onClick: () -> Unit, onDelete: () -> Unit) {
    val dateFormat = remember { SimpleDateFormat("MMM dd, yyyy HH:mm", Locale.getDefault()) }
    var showDeleteDialog by remember { mutableStateOf(false) }

    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text("Delete Conversation?") },
            text = { Text("Are you sure you want to delete the conversation \"${thread.title}\"? This action cannot be undone.") },
            confirmButton = {
                Button(
                    onClick = {
                        onDelete()
                        showDeleteDialog = false
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Delete")
                }
            },
            dismissButton = {
                Button(onClick = { showDeleteDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(16.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Column(modifier = Modifier.weight(1f).padding(end = 16.dp)) {
            Text(
                text = thread.title,
                style = MaterialTheme.typography.titleMedium,
                color = MaterialTheme.colorScheme.onSurface
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = "Last activity: ${dateFormat.format(Date(thread.lastModifiedAt))}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        IconButton(onClick = { showDeleteDialog = true }) {
            Icon(
                Icons.Filled.Delete,
                contentDescription = "Delete Thread",
                tint = MaterialTheme.colorScheme.error
            )
        }
    }
}

// Preview needs a NavController and a ViewModel instance.
// For simplicity, we can mock them or use a fake implementation for preview.
@Preview(showBackground = true, name = "History Screen with Threads")
@Composable
fun HistoryScreenPreview() {
    val context = LocalContext.current
    val factory = AppViewModelFactory(context.applicationContext as Application, LocalContext.current as androidx.savedstate.SavedStateRegistryOwner)
    val previewViewModel: HistoryViewModel = viewModel(factory = factory)

    // Manually set some data in the ViewModel's StateFlow for preview
    // This is a bit of a hack for previewing state.
    // In a real app with Hilt, this is easier.
    val previewThreads = listOf(
        ThreadEntity(localId = 1, openaiThreadId = "thread_1", title = "First Chat", createdAt = Date().time - 1000000, lastModifiedAt = Date().time - 500000),
        ThreadEntity(localId = 2, openaiThreadId = "thread_2", title = "Ideas for new recipe", createdAt = Date().time - 2000000, lastModifiedAt = Date().time - 600000)
    )
    previewViewModel.uiState.value.let {
        (previewViewModel.uiState as MutableStateFlow).value = it.copy(threads = previewThreads, isLoading = false)
    }


    AICompanionAppTheme {
        HistoryScreen(navController = NavController(context), viewModel = previewViewModel)
    }
}

@Preview(showBackground = true, name = "History Screen Empty")
@Composable
fun HistoryScreenEmptyPreview() {
    val context = LocalContext.current
    val factory = AppViewModelFactory(context.applicationContext as Application, LocalContext.current as androidx.savedstate.SavedStateRegistryOwner)
    val previewViewModel: HistoryViewModel = viewModel(factory = factory)
    (previewViewModel.uiState as MutableStateFlow).value = HistoryScreenUiState(threads = emptyList(), isLoading = false)


    AICompanionAppTheme {
        HistoryScreen(navController = NavController(context), viewModel = previewViewModel)
    }
}

@Preview(showBackground = true, name = "History Screen Loading")
@Composable
fun HistoryScreenLoadingPreview() {
     val context = LocalContext.current
    val factory = AppViewModelFactory(context.applicationContext as Application, LocalContext.current as androidx.savedstate.SavedStateRegistryOwner)
    val previewViewModel: HistoryViewModel = viewModel(factory = factory)
    (previewViewModel.uiState as MutableStateFlow).value = HistoryScreenUiState(threads = emptyList(), isLoading = true)

    AICompanionAppTheme {
       HistoryScreen(navController = NavController(context), viewModel = previewViewModel)
    }
}
