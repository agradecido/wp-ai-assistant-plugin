package com.example.aicompanionapp.api

import com.example.aicompanionapp.data.model.*
import retrofit2.Response
import retrofit2.http.*

interface OpenAIApiService {
    companion object {
        // Per OpenAI documentation, v2 of Assistants API requires this header.
        // https://platform.openai.com/docs/assistants/migration/what-has-changed
        // "The v1 API (assistants=v1) will be shut down on July 29, 2024."
        // Using v2 as it's the latest and recommended.
        const val OPENAI_BETA_HEADER = "assistants=v2"
    }

    @POST("threads")
    suspend fun createThread(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Body requestBody: CreateThreadRequest = CreateThreadRequest() // Default empty body as per API
    ): Response<ThreadResponse>

    @POST("threads/{thread_id}/messages")
    suspend fun addMessageToThread(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String,
        @Body messageRequest: AddMessageRequest
    ): Response<MessageResponse>

    @POST("threads/{thread_id}/runs")
    suspend fun runAssistant(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String,
        @Body runRequest: RunRequest
    ): Response<RunResponse>

    @GET("threads/{thread_id}/runs/{run_id}")
    suspend fun getRunStatus(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String,
        @Path("run_id") runId: String
    ): Response<RunResponse>

    @GET("threads/{thread_id}/messages")
    suspend fun getThreadMessages(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String,
        @Query("limit") limit: Int = 20, // Default limit
        @Query("order") order: String = "desc", // Default to descending order (latest first)
        @Query("after") after: String? = null, // For pagination
        @Query("before") before: String? = null // For pagination
    ): Response<ThreadMessagesResponse>

    // Optional: If you plan to allow cancelling runs
    @POST("threads/{thread_id}/runs/{run_id}/cancel")
    suspend fun cancelRun(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String,
        @Path("run_id") runId: String
    ): Response<RunResponse>

    // Optional: If you plan to retrieve a specific message
    @GET("threads/{thread_id}/messages/{message_id}")
    suspend fun getMessage(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String,
        @Path("message_id") messageId: String
    ): Response<MessageResponse>

    // Optional: If you plan to modify a thread
    @POST("threads/{thread_id}")
    suspend fun modifyThread(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String,
        @Body metadata: Map<String, String> // Example: Only metadata can be modified
    ): Response<ThreadResponse>

    // Optional: Deleting a thread
    @DELETE("threads/{thread_id}")
    suspend fun deleteThread(
        @Header("Authorization") apiKey: String,
        @Header("OpenAI-Beta") betaHeader: String = OPENAI_BETA_HEADER,
        @Path("thread_id") threadId: String
    ): Response<DeleteResponse> // Define DeleteResponse: data class DeleteResponse(val id: String, val object: String, val deleted: Boolean)

}

// A generic response for delete operations, often useful.
data class DeleteResponse(val id: String, val object: String, val deleted: Boolean)
