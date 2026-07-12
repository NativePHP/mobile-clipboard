import Foundation
import UIKit

// MARK: - Clipboard Function Namespace

/// System clipboard access (plain text).
/// Namespace: "Clipboard.*"
enum ClipboardFunctions {

    // MARK: - Clipboard.WriteText

    /// Write plain text to the system clipboard.
    /// Parameters:
    ///   - text: string - Text to place on the clipboard
    class WriteText: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let text = parameters["text"] as? String else {
                return ["error": "text parameter is required"]
            }

            // UIPasteboard must be touched on the main thread; do it
            // synchronously so the caller can trust "copied" on return.
            if Thread.isMainThread {
                UIPasteboard.general.string = text
            } else {
                DispatchQueue.main.sync {
                    UIPasteboard.general.string = text
                }
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
