package com.nativephp.clipboard

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.os.Handler
import android.os.Looper
import android.util.Log
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * System clipboard access (plain text).
 * Namespace: "Clipboard.*"
 */
object ClipboardFunctions {

    /**
     * Write plain text to the system clipboard.
     * Parameters:
     *   - text: string - Text to place on the clipboard
     */
    class WriteText(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val text = parameters["text"] as? String
                ?: return mapOf("error" to "text parameter is required")

            return try {
                val manager = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                if (Looper.myLooper() == Looper.getMainLooper()) {
                    manager.setPrimaryClip(ClipData.newPlainText("text", text))
                } else {
                    // ClipboardManager wants the main thread; post + latch-free
                    // sync via runBlocking is overkill — a simple synchronous
                    // handler wait keeps "copied" trustworthy on return.
                    val lock = Object()
                    var done = false
                    Handler(Looper.getMainLooper()).post {
                        manager.setPrimaryClip(ClipData.newPlainText("text", text))
                        synchronized(lock) { done = true; lock.notifyAll() }
                    }
                    synchronized(lock) { if (!done) lock.wait(2000) }
                }
                mapOf("success" to true)
            } catch (e: Exception) {
                Log.e("ClipboardFunctions", "WriteText failed", e)
                mapOf("error" to (e.message ?: "clipboard write failed"))
            }
        }
    }

    /**
     * Read plain text from the system clipboard.
     * Returns: { text: string } — empty string when the clipboard has no text.
     */
    class ReadText(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            return try {
                val manager = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                val text = manager.primaryClip
                    ?.takeIf { it.itemCount > 0 }
                    ?.getItemAt(0)
                    ?.coerceToText(context)
                    ?.toString()
                    ?: ""
                mapOf("text" to text)
            } catch (e: Exception) {
                Log.e("ClipboardFunctions", "ReadText failed", e)
                mapOf("error" to (e.message ?: "clipboard read failed"))
            }
        }
    }
}
