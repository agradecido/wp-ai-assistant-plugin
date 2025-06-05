package com.example.aicompanionapp.data.model

data class ThreadResponse(
    val id: String,
    val object: String, // Typically "thread"
    val created_at: Long,
    val metadata: Map<String, String>?
)

data class MessageResponse(
    val id: String,
    val object: String, // Typically "thread.message"
    val thread_id: String,
    val role: String, // "user" or "assistant"
    val content: List<ContentPart>,
    val created_at: Long,
    val assistant_id: String? = null,
    val run_id: String? = null,
    val metadata: Map<String, String>?
    // val attachments: List<Attachment>? = null // Added based on recent API, but keeping it simple for now
)

data class ContentPart(
    val type: String, // Typically "text"
    val text: TextContent?
)

data class TextContent(
    val value: String,
    val annotations: List<Any>? // Annotations can be complex (e.g., file_citation, file_path), using Any for broad compatibility
)

data class RunResponse(
    val id: String,
    val object: String, // Typically "thread.run"
    val thread_id: String,
    val assistant_id: String,
    val status: String, // e.g., "queued", "in_progress", "requires_action", "completed", "failed", "cancelled"
    val created_at: Long,
    val expires_at: Long?,
    val started_at: Long?,
    val completed_at: Long?,
    val cancelled_at: Long?,
    val failed_at: Long?,
    val last_error: ApiError?,
    val model: String?,
    val instructions: String?,
    val tools: List<Any>?, // List of tool configurations used by the run
    val metadata: Map<String, String>?
    // val required_action: RequiredAction? = null, // For function calling
    // val usage: UsageStats? = null // For token usage
)

data class ApiError(
    val code: String,
    val message: String
)

data class ThreadMessagesResponse(
    val object: String, // Typically "list"
    val data: List<MessageResponse>,
    val first_id: String?,
    val last_id: String?,
    val has_more: Boolean
)

// Example for Attachments if needed later, from OpenAI documentation for Message object
// data class Attachment(
//     val file_id: String,
//     val tools: List<ToolCodeInterpreter | ToolFileSearch> // Example, define these tool types
// )
// interface Tool
// data class ToolCodeInterpreter(val type: String = "code_interpreter"): Tool
// data class ToolFileSearch(val type: String = "file_search"): Tool

// Example for RequiredAction if needed later
// data class RequiredAction(
// val type: String, // e.g., "submit_tool_outputs"
// val submit_tool_outputs: SubmitToolOutputsAction?
// )
// data class SubmitToolOutputsAction(
// val tool_calls: List<ToolCall>
// )
// data class ToolCall(
// val id: String, // Tool call ID to be submitted
// val type: String, // "function"
// val function: FunctionCallData?
// )
// data class FunctionCallData(
// val name: String,
// val arguments: String // JSON string of arguments
// )

// Example for UsageStats if needed later
// data class UsageStats(
// val completion_tokens: Int,
// val prompt_tokens: Int,
// val total_tokens: Int
// )
