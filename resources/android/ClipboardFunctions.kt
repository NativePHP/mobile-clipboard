package com.nativephp.clipboard

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.os.Build
import android.os.Handler
import android.os.Looper
import android.os.PersistableBundle
import android.util.Log
import com.nativephp.mobile.bridge.BridgeFunction
import java.util.UUID

/**
 * System clipboard access (plain text).
 * Namespace: "Clipboard.*"
 */
object ClipboardFunctions {

    /** Same key as ClipDescription.EXTRA_IS_SENSITIVE (API 33), usable on older releases. */
    private const val EXTRA_IS_SENSITIVE = "android.content.extra.IS_SENSITIVE"

    /** Marks an expiring copy so a later clipboard change can be told apart from it. */
    private const val EXTRA_COPY_ID = "com.nativephp.clipboard.COPY_ID"

    private val mainHandler = Handler(Looper.getMainLooper())

    // Main thread only.
    private var expiringCopyId: String? = null
    private var pendingClear: Runnable? = null
    private var listening = false

    /**
     * Puts [text] on the clipboard. Must run on the main thread.
     *
     * Any earlier pending expiry is cancelled first, so a plain copy made
     * after an expiring one is never cleared by the old timer.
     */
    private fun write(context: Context, text: String, sensitive: Boolean, expiresIn: Long?) {
        val manager = context.applicationContext
            .getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager

        cancelExpiry()

        val clip = ClipData.newPlainText("text", text)
        val copyId = expiresIn?.let { UUID.randomUUID().toString() }

        if (sensitive || copyId != null) {
            clip.description.extras = PersistableBundle().apply {
                if (sensitive) putBoolean(EXTRA_IS_SENSITIVE, true)
                if (copyId != null) putString(EXTRA_COPY_ID, copyId)
            }
        }

        if (copyId != null) {
            listen(manager)
            // Set before the write: the change listener also fires for this copy.
            expiringCopyId = copyId
        }

        manager.setPrimaryClip(clip)

        if (copyId != null && expiresIn != null) {
            val clear = Runnable { clearIfStillOurs(manager, copyId) }
            pendingClear = clear
            mainHandler.postDelayed(clear, expiresIn * 1000)
        }
    }

    /**
     * Apps can't read the clipboard while in the background, so instead of
     * comparing contents at expiry time, watch for changes: any new clip that
     * isn't our expiring copy (or that we can't inspect) means the user copied
     * something else, and the expiry is dropped. Leaving a secret for the
     * system to clear is better than wiping what the user copied.
     */
    private fun listen(manager: ClipboardManager) {
        if (listening) return
        listening = true

        manager.addPrimaryClipChangedListener {
            val id = try {
                manager.primaryClipDescription?.extras?.getString(EXTRA_COPY_ID)
            } catch (_: Exception) {
                null
            }

            if (id == null || id != expiringCopyId) {
                cancelExpiry()
            }
        }
    }

    private fun clearIfStillOurs(manager: ClipboardManager, copyId: String) {
        if (expiringCopyId != copyId) return

        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                manager.clearPrimaryClip()
            } else {
                manager.setPrimaryClip(ClipData.newPlainText("", ""))
            }
        } catch (e: Exception) {
            Log.w("ClipboardFunctions", "Could not clear the expired copy", e)
        } finally {
            cancelExpiry()
        }
    }

    private fun cancelExpiry() {
        pendingClear?.let { mainHandler.removeCallbacks(it) }
        pendingClear = null
        expiringCopyId = null
    }

    /**
     * Write plain text to the system clipboard.
     * Parameters:
     *   - text: string - Text to place on the clipboard
     *   - sensitive: bool (optional) - mark the clip sensitive
     *     (ClipDescription.EXTRA_IS_SENSITIVE): hidden in the Android 13+
     *     copy preview and not offered as a keyboard suggestion
     *   - expires_in: int seconds (optional) - clear the clip after this long,
     *     only if the clipboard still holds this copy. Needs the app process
     *     to be alive; Android 13+ also clears the clipboard on its own later.
     */
    class WriteText(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val text = parameters["text"] as? String
                ?: return mapOf("error" to "text parameter is required")

            val sensitive = parameters["sensitive"] as? Boolean ?: false
            val expiresIn = (parameters["expires_in"] as? Number)?.toLong()?.takeIf { it > 0 }

            return try {
                if (Looper.myLooper() == Looper.getMainLooper()) {
                    write(context, text, sensitive, expiresIn)
                } else {
                    // ClipboardManager wants the main thread; post + latch-free
                    // sync via runBlocking is overkill — a simple synchronous
                    // handler wait keeps "copied" trustworthy on return.
                    val lock = Object()
                    var done = false
                    var error: Exception? = null
                    mainHandler.post {
                        try {
                            write(context, text, sensitive, expiresIn)
                        } catch (e: Exception) {
                            error = e
                        }
                        synchronized(lock) { done = true; lock.notifyAll() }
                    }
                    synchronized(lock) { if (!done) lock.wait(2000) }
                    error?.let { throw it }
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
