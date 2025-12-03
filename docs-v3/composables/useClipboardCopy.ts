import { ref, readonly } from 'vue'

const COPY_RESET_DELAY = 2000

export function useClipboardCopy() {
    const copied = ref(false)
    const { success, error: showError } = useAppToast()

    async function copyToClipboard(text: string): Promise<boolean> {
        if (await tryNavigatorClipboard(text)) {
            return handleSuccess()
        }

        if (tryExecCommand(text)) {
            return handleSuccess()
        }

        showError('Copy failed - please manually select and copy the text')
        return false
    }

    async function tryNavigatorClipboard(text: string): Promise<boolean> {
        if (!navigator.clipboard) return false

        try {
            await navigator.clipboard.writeText(text)
            return true
        } catch {
            return false
        }
    }

    function tryExecCommand(text: string): boolean {
        const textArea = document.createElement('textarea')
        textArea.value = text
        textArea.style.cssText = 'position:absolute;left:-999999px;top:-999999px'
        document.body.appendChild(textArea)
        textArea.focus()
        textArea.select()

        const successful = document.execCommand('copy')
        document.body.removeChild(textArea)
        return successful
    }

    function handleSuccess(): boolean {
        copied.value = true
        success('Copied to clipboard!')

        setTimeout(function resetCopied() {
            copied.value = false
        }, COPY_RESET_DELAY)

        return true
    }

    return {
        copied: readonly(copied),
        copyToClipboard
    }
}
