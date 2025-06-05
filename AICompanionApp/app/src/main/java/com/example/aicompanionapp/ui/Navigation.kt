package com.example.aicompanionapp.ui

import android.app.Application
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.viewmodel.compose.viewModel // For viewModel() composable
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.example.aicompanionapp.ui.screens.ChatScreen
import com.example.aicompanionapp.ui.screens.HistoryScreen
import com.example.aicompanionapp.viewmodel.AppViewModelFactory
import com.example.aicompanionapp.viewmodel.ChatViewModel
import com.example.aicompanionapp.viewmodel.HistoryViewModel
import com.example.aicompanionapp.viewmodel.NEW_CHAT_THREAD_ID_MARKER // Import the marker

sealed class Screen(val route: String) {
    object History : Screen("history")
    object Chat : Screen("chat/{threadId}") { // Argument name is "threadId"
        fun createRoute(threadId: String) = "chat/$threadId"
    }
    // Removed NewChat object, as "new" is handled as a special threadId string.
}

@Composable
fun AppNavigation(navController: NavHostController) {
    val application = LocalContext.current.applicationContext as Application

    NavHost(navController = navController, startDestination = Screen.History.route) {
        composable(Screen.History.route) { backStackEntry ->
            // Pass the NavBackStackEntry as the SavedStateRegistryOwner
            val factory = AppViewModelFactory(application, backStackEntry)
            val historyViewModel: HistoryViewModel = viewModel(factory = factory)
            HistoryScreen(navController = navController, viewModel = historyViewModel)
        }
        composable(
            route = Screen.Chat.route, // "chat/{threadId}"
            arguments = listOf(navArgument("threadId") {
                type = NavType.StringType
                // Nullable true if we want to allow navigation to "chat" without an ID,
                // but our route demands it. A "new" string will be used for new chats.
                // Default value can be set if needed, e.g., defaultValue = NEW_CHAT_THREAD_ID_MARKER
            })
        ) { backStackEntry ->
            // Pass NavBackStackEntry as SavedStateRegistryOwner and arguments as defaultArgs
            val factory = AppViewModelFactory(application, backStackEntry, backStackEntry.arguments)

            // The key ensures that if we navigate from ChatScreen (threadA) to ChatScreen (threadB),
            // a new ViewModel instance is created if the threadId is different.
            // SavedStateHandle will automatically get arguments from backStackEntry.arguments.
            val threadId = backStackEntry.arguments?.getString("threadId") ?: NEW_CHAT_THREAD_ID_MARKER
            val chatViewModel: ChatViewModel = viewModel(key = "chat_vm_$threadId", factory = factory)

            ChatScreen(navController = navController, viewModel = chatViewModel /*, threadId = threadId */)
            // threadId can be passed to ChatScreen if needed for UI logic not covered by VM state,
            // but ViewModel should primarily drive its state from SavedStateHandle.
        }
    }
}
