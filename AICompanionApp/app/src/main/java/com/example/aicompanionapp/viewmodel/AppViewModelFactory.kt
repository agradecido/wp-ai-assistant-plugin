package com.example.aicompanionapp.viewmodel

import android.app.Application
import android.os.Bundle
import androidx.lifecycle.AbstractSavedStateViewModelFactory
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.savedstate.SavedStateRegistryOwner
import com.example.aicompanionapp.api.ApiClient
import com.example.aicompanionapp.data.db.AppDatabase
import com.example.aicompanionapp.data.repository.ChatRepository
import com.example.aicompanionapp.data.repository.ChatRepositoryImpl

class AppViewModelFactory(
    private val application: Application,
    owner: SavedStateRegistryOwner,
    defaultArgs: Bundle? = null
) : AbstractSavedStateViewModelFactory(owner, defaultArgs) {

    // Lazy initialization of the repository so it's created only when needed.
    private val chatRepository: ChatRepository by lazy {
        ChatRepositoryImpl(
            threadDao = AppDatabase.getDatabase(application).threadDao(),
            messageDao = AppDatabase.getDatabase(application).messageDao(),
            openAIApiService = ApiClient.instance
        )
    }

    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(
        key: String, // Unique key for the ViewModel instance
        modelClass: Class<T>,
        handle: SavedStateHandle // Provided by AbstractSavedStateViewModelFactory
    ): T {
        return when {
            modelClass.isAssignableFrom(HistoryViewModel::class.java) -> {
                // HistoryViewModel's constructor is (ChatRepository, Application)
                // It does not take SavedStateHandle as per its definition.
                HistoryViewModel(chatRepository, application) as T
            }
            modelClass.isAssignableFrom(ChatViewModel::class.java) -> {
                // ChatViewModel's constructor is (ChatRepository, Application, SavedStateHandle)
                ChatViewModel(chatRepository, application, handle) as T
            }
            else -> throw IllegalArgumentException("Unknown ViewModel class: ${modelClass.name}")
        }
    }
}
