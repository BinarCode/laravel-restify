import { ref, readonly } from 'vue'

export function useMobileMenu() {
    const isOpen = ref(false)
    const { watchScrollLock } = useBodyScrollLock()

    function toggle(): void {
        isOpen.value = !isOpen.value
    }

    function close(): void {
        isOpen.value = false
    }

    function open(): void {
        isOpen.value = true
    }

    watchScrollLock(isOpen)

    return {
        isOpen: readonly(isOpen),
        toggle,
        close,
        open
    }
}
