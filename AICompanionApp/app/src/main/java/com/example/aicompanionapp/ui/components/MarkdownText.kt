package com.example.aicompanionapp.ui.components

import android.graphics.Color as AndroidColor // Alias to avoid conflict with Compose Color
import android.text.util.Linkify
import android.widget.TextView
import androidx.compose.material3.LocalContentColor
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import io.noties.markwon.Markwon
import io.noties.markwon.html.HtmlPlugin
import io.noties.markwon.image.ImagesPlugin // If you added the image dependency

@Composable
fun MarkdownText(markdown: String, modifier: Modifier = Modifier) {
    val context = LocalContext.current
    // Get current text color from MaterialTheme to ensure consistency
    val currentTextColor = MaterialTheme.colorScheme.onSurface.hashCode() // Or onBackground, etc.

    val markwon = Markwon.builder(context)
        .usePlugin(HtmlPlugin.create()) // Enable HTML tags if needed
        // .usePlugin(ImagesPlugin.create(context)) // Enable images if you have an image loader and added the dependency
        // Add other Markwon plugins if you included their dependencies (e.g., TablesPlugin)
        .build()

    AndroidView(
        factory = { ctx ->
            TextView(ctx).apply {
                // Set text color from Compose theme
                setTextColor(currentTextColor)
                // For clickable links, Linkify can be used in conjunction or if Markwon doesn't handle all link types
                autoLinkMask = Linkify.WEB_URLS // Linkify web URLs
                linksClickable = true // This is important for Linkify
            }
        },
        modifier = modifier,
        update = { textView ->
            markwon.setMarkdown(textView, markdown)
            // Ensure links are clickable after Markwon sets the text.
            // Markwon typically handles its own link clicking if markdown contains proper links.
            // If Linkify is needed for plain text URLs not in markdown link format:
            // Linkify.addLinks(textView, Linkify.WEB_URLS)
        }
    )
}
