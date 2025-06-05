package com.example.aicompanionapp.ui.screens

import android.app.Application
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.example.aicompanionapp.data.db.entity.MessageEntity
import com.example.aicompanionapp.ui.components.MarkdownText
import com.example.aicompanionapp.ui.theme.AICompanionAppTheme
import com.example.aicompanionapp.viewmodel.AppViewModelFactory
import com.example.aicompanionapp.viewmodel.ChatViewModel
import com.example.aicompanionapp.viewmodel.NEW_CHAT_THREAD_ID_MARKER
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)
@Composable
fun ChatScreen(
    navController: NavController?, // Nullable for preview
    viewModel: ChatViewModel
    // Removed threadId parameter, ViewModel gets it from SavedStateHandle
) {
    val uiState by viewModel.uiState.collectAsState()
    var currentMessageInput by remember { mutableStateOf(TextFieldValue("")) }
    val listState = rememberLazyListState()
    val coroutineScope = rememberCoroutineScope()
    val keyboardController = LocalSoftwareKeyboardController.current
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(
                message = it,
                duration = SnackbarDuration.Short
            )
            viewModel.clearError() // Clear error after showing
        }
    }

    // Scroll to bottom when new messages arrive or keyboard opens/closes
    LaunchedEffect(uiState.messages.size, WindowInsets.is obecnieVisible(WindowInsets.ime)) {
        if (uiState.messages.isNotEmpty()) {
            listState.animateScrollToItem(uiState.messages.lastIndex)
        }
    }

    // If currentThreadOpenaiId changes from NEW_CHAT_THREAD_ID_MARKER to a real ID,
    // update the navigation route to reflect the new threadId.
    // This is important if the user starts a new chat and then navigates away and back.
    // Or if the app is backgrounded and restored.
    LaunchedEffect(uiState.currentThreadOpenaiId) {
        val currentId = uiState.currentThreadOpenaiId
        if (currentId != null && currentId != NEW_CHAT_THREAD_ID_MARKER) {
            // This logic is a bit tricky with NavController.
            // If the ViewModel updates the SavedStateHandle, NavController should ideally pick it up.
            // For now, let's assume NavController's argument for threadId is the source of truth for navigation itself.
            // The ViewModel uses it for its internal logic.
            // If we want to update the URL in the NavController:
            // navController?.currentBackStackEntry?.arguments?.putString("threadId", currentId)
            // This is generally not how one updates nav args. Usually, you'd navigate to a new route.
            // The key in AppNavigation viewModel(key = "chat_vm_$threadId", factory = factory) handles VM recreation for different threads.
        }
    }


    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text(text = uiState.threadTitle, maxLines = 1) },
                navigationIcon = {
                    IconButton(onClick = { navController?.popBackStack() }) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        },
        bottomBar = {
            MessageInputBar(
                currentMessage = currentMessageInput,
                onMessageChange = { currentMessageInput = it },
                onSendMessage = {
                    if (currentMessageInput.text.isNotBlank()) {
                        viewModel.sendMessage(currentMessageInput.text)
                        currentMessageInput = TextFieldValue("") // Clear input
                        keyboardController?.hide() // Hide keyboard
                    }
                },
                isLoading = uiState.isSendingMessage // Use isSendingMessage for input bar
            )
        }
    ) { paddingValues ->
        Box(modifier = Modifier.fillMaxSize().padding(paddingValues)) {
            LazyColumn(
                state = listState,
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
                contentPadding = PaddingValues(vertical = 8.dp)
            ) {
                items(uiState.messages, key = { it.openaiMessageId }) { message ->
                    MessageListItem(message = message)
                }
            }
            // Full screen loading indicator for initial load
            if (uiState.isLoading && uiState.messages.isEmpty()) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            }
        }
    }
}

@Composable
fun MessageListItem(message: MessageEntity) {
    val isUser = message.role == "user"
    val alignment = if (isUser) Alignment.CenterEnd else Alignment.CenterStart
    val backgroundColor = if (isUser) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.secondaryContainer
    val dateFormat = remember { SimpleDateFormat("MMM dd, HH:mm", Locale.getDefault()) }

    Box(
        modifier = Modifier
            .fillMaxWidth()
            .padding(
                start = if (isUser) 32.dp else 0.dp,
                end = if (isUser) 0.dp else 32.dp
            ),
        contentAlignment = alignment
    ) {
        Column(
            modifier = Modifier
                .clip(RoundedCornerShape(12.dp))
                .background(backgroundColor)
                .padding(horizontal = 12.dp, vertical = 8.dp),
            horizontalAlignment = if (isUser) Alignment.End else Alignment.Start
        ) {
            MarkdownText(
                markdown = message.content,
                modifier = Modifier.padding(bottom = 4.dp)
            )
            Text(
                text = dateFormat.format(Date(message.createdAt)),
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f),
                modifier = Modifier.align(Alignment.End)
            )
        }
    }
}

@Composable
fun MessageInputBar(
    currentMessage: TextFieldValue,
    onMessageChange: (TextFieldValue) -> Unit,
    onSendMessage: () -> Unit,
    isLoading: Boolean
) {
    Surface(shadowElevation = 8.dp) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 8.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            TextField(
                value = currentMessage,
                onValueChange = onMessageChange,
                modifier = Modifier.weight(1f),
                placeholder = { Text("Type a message...") },
                colors = TextFieldDefaults.colors(
                    focusedIndicatorColor = Color.Transparent,
                    unfocusedIndicatorColor = Color.Transparent,
                    disabledIndicatorColor = Color.Transparent,
                    cursorColor = MaterialTheme.colorScheme.primary
                ),
                shape = RoundedCornerShape(24.dp),
                enabled = !isLoading,
                maxLines = 5
            )
            Spacer(modifier = Modifier.width(8.dp))
            if (isLoading) {
                CircularProgressIndicator(modifier = Modifier.size(24.dp))
                Spacer(modifier = Modifier.width(12.dp)) // Keep send button space or hide it
            } else {
                 IconButton(
                    onClick = onSendMessage,
                    enabled = currentMessage.text.isNotBlank() && !isLoading
                ) {
                    Icon(
                        Icons.AutoMirrored.Filled.Send,
                        contentDescription = "Send Message",
                        tint = if (currentMessage.text.isNotBlank() && !isLoading) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurface.copy(alpha = 0.4f) // Using Material Design's ContentAlpha.disabled like alpha
                    )
                }
            }
        }
    }
}

@Preview(showBackground = true, name = "Chat Screen")
@Composable
fun ChatScreenPreview() {
    val context = LocalContext.current
    val app = context.applicationContext as Application
    // For preview, SavedStateHandle can be tricky. We often pass dummy data to UI state.
    // Or, use a fake ViewModel that doesn't rely on SavedStateHandle for preview.
    val factory = AppViewModelFactory(application = app, owner = LocalContext.current as androidx.savedstate.SavedStateRegistryOwner, defaultArgs = android.os.Bundle().apply { putString("threadId", "preview_thread_123")})
    val previewViewModel: ChatViewModel = viewModel(key="chat_prev_1", factory = factory)

    val previewMsgs = listOf(
        MessageEntity(localId = 1, threadId = "thread_1", openaiMessageId = "msg_1", role = "user", content = "Hello Assistant!", createdAt = Date().time - 100000),
        MessageEntity(localId = 2, threadId = "thread_1", openaiMessageId = "msg_2", role = "assistant", content = "Hello User! How can I help you today?", createdAt = Date().time - 90000)
    )
    (previewViewModel.uiState as MutableStateFlow).value = ChatScreenUiState(
        messages = previewMsgs,
        threadTitle = "Preview Chat",
        isLoading = false,
        isSendingMessage = false,
        currentThreadOpenaiId = "preview_thread_123"
    )

    AICompanionAppTheme {
        ChatScreen(
            navController = NavController(context),
            viewModel = previewViewModel
        )
    }
}

@Preview(showBackground = true, name = "Chat Screen Loading")
@Composable
fun ChatScreenLoadingPreview() {
    val context = LocalContext.current
    val app = context.applicationContext as Application
    val factory = AppViewModelFactory(application = app, owner = LocalContext.current as androidx.savedstate.SavedStateRegistryOwner, defaultArgs = android.os.Bundle().apply { putString("threadId", "loading_thread")})
    val previewViewModel: ChatViewModel = viewModel(key="chat_prev_2", factory = factory)

     (previewViewModel.uiState as MutableStateFlow).value = ChatScreenUiState(
        messages = emptyList(),
        threadTitle = "Loading Chat...",
        isLoading = true,
        isSendingMessage = false,
        currentThreadOpenaiId = "loading_thread"
    )

    AICompanionAppTheme {
        ChatScreen(
            navController = NavController(context),
            viewModel = previewViewModel
        )
    }
}

@Preview(showBackground = true, name = "Chat Screen Sending Message")
@Composable
fun ChatScreenSendingPreview() {
    val context = LocalContext.current
    val app = context.applicationContext as Application
     val factory = AppViewModelFactory(application = app, owner = LocalContext.current as androidx.savedstate.SavedStateRegistryOwner, defaultArgs = android.os.Bundle().apply { putString("threadId", "sending_thread")})
    val previewViewModel: ChatViewModel = viewModel(key="chat_prev_3", factory = factory)

    val previewMsgs = listOf(
        MessageEntity(localId = 1, threadId = "thread_1", openaiMessageId = "msg_1", role = "user", content = "Please do something.", createdAt = Date().time - 10000)
    )
    (previewViewModel.uiState as MutableStateFlow).value = ChatScreenUiState(
        messages = previewMsgs,
        threadTitle = "Processing...",
        isLoading = false,
        isSendingMessage = true,
        currentThreadOpenaiId = "sending_thread"
    )
    AICompanionAppTheme {
         ChatScreen(
            navController = NavController(context),
            viewModel = previewViewModel
        )
    }
}
