package com.example.aicompanionapp.data

sealed class ResultWrapper<out T> {
    data class Success<out T>(val data: T) : ResultWrapper<T>()
    data class Error(val message: String, val exception: Exception? = null) : ResultWrapper<Nothing>()
}
