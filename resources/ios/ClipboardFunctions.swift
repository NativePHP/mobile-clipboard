import Foundation
import UIKit
import UniformTypeIdentifiers

// MARK: - Clipboard Function Namespace

/// System clipboard access (plain text).
/// Namespace: "Clipboard.*"
enum ClipboardFunctions {

    // MARK: - Clipboard.WriteText

    /// Write plain text to the system clipboard.
    /// Parameters:
    ///   - text: string - Text to place on the clipboard
    ///   - sensitive: bool (optional) - keep the copy on this device
    ///     (UIPasteboard localOnly: not shared over Universal Clipboard)
    ///   - expires_in: int seconds (optional) - iOS removes the copy after this
    ///     long (UIPasteboard expirationDate). Only this copy expires: anything
    ///     the user copies afterwards replaces it and is not affected.
    class WriteText: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let text = parameters["text"] as? String else {
                return ["error": "text parameter is required"]
            }

            let sensitive = (parameters["sensitive"] as? Bool) ?? false
            let expiresIn = (parameters["expires_in"] as? NSNumber)?.doubleValue

            var options: [UIPasteboard.OptionsKey: Any] = [:]
            if sensitive {
                options[.localOnly] = true
            }
            if let expiresIn = expiresIn, expiresIn > 0 {
                options[.expirationDate] = Date().addingTimeInterval(expiresIn)
            }

            // UIPasteboard must be touched on the main thread; do it
            // synchronously so the caller can trust "copied" on return.
            let write = {
                if options.isEmpty {
                    UIPasteboard.general.string = text
                } else {
                    UIPasteboard.general.setItems(
                        [[UTType.utf8PlainText.identifier: text]],
                        options: options
                    )
                }
            }

            if Thread.isMainThread {
                write()
            } else {
                DispatchQueue.main.sync(execute: write)
            }

            return ["success": true]
        }
    }

    // MARK: - Clipboard.ReadText

    /// Read plain text from the system clipboard.
    /// Returns: { text: string } — empty string when the clipboard has no text.
    /// Note: on iOS 14+ reading may show the system "pasted from" banner.
    class ReadText: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            var text = ""
            if Thread.isMainThread {
                text = UIPasteboard.general.string ?? ""
            } else {
                DispatchQueue.main.sync {
                    text = UIPasteboard.general.string ?? ""
                }
            }

            return ["text": text]
        }
    }
}
