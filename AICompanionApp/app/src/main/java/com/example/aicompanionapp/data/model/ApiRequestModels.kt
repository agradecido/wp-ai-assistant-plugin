package com.example.aicompanionapp.data.model

data class CreateThreadRequest(val metadata: Map<String, String>? = null)

data class AddMessageRequest(val role: String, val content: String)

data class RunRequest(val assistant_id: String, val instructions: String? = null)
