package com.example.aicompanionapp.api

// It's important to ensure that the package name here matches exactly
// where Android Gradle Plugin generates BuildConfig.
// For this project, it should be: com.example.aicompanionapp.BuildConfig
import com.example.aicompanionapp.BuildConfig
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {

    // --- IMPORTANT SECURITY NOTE ---
    // Storing API keys directly in code is highly insecure and not recommended for production apps.
    // This is a placeholder for development convenience ONLY.
    // In a real application, consider using:
    // 1. Server-side proxy to make API calls (most secure).
    // 2. Storing the key in `local.properties` (ignored by Git) and accessing via BuildConfig fields.
    // 3. Using a secrets management service.
    // -------------------------------
    const val OPENAI_API_KEY = "sk-YOUR_OPENAI_API_KEY_HERE_REPLACE_ME" // << MUST BE REPLACED
    const val ASSISTANT_ID = "asst_YOUR_ASSISTANT_ID_HERE_REPLACE_ME" // << MUST BE REPLACED

    private const val BASE_URL = "https://api.openai.com/v1/"

    private val loggingInterceptor = HttpLoggingInterceptor().apply {
        // Use BuildConfig.DEBUG to toggle logging level.
        // This requires the Android Gradle Plugin to generate the BuildConfig file.
        // In a pure Kotlin module or if BuildConfig is not available, you might use a different flag.
        level = if (BuildConfig.DEBUG) {
            HttpLoggingInterceptor.Level.BODY
        } else {
            HttpLoggingInterceptor.Level.NONE
        }
    }

    private val okHttpClient = OkHttpClient.Builder()
        .addInterceptor(loggingInterceptor)
        .connectTimeout(30, TimeUnit.SECONDS) // Standard timeout
        .readTimeout(30, TimeUnit.SECONDS)    // Standard timeout
        .writeTimeout(30, TimeUnit.SECONDS)   // Standard timeout
        .build()

    val instance: OpenAIApiService by lazy {
        val retrofit = Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create()) // Using Gson for JSON parsing
            .build()
        retrofit.create(OpenAIApiService::class.java)
    }
}
