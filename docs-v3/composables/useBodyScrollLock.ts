import type { Ref } from 'vue'
import { watch, onUnmounted } from 'vue'

export function useBodyScrollLock() {
    function lockScroll(): void {
        if (typeof window === 'undefined') return

        document.body.style.overflow = 'hidden'
        document.body.style.position = 'fixed'
        document.body.style.width = '100%'
    }

    function unlockScroll(): void {
        if (typeof window === 'undefined') return

        document.body.style.overflow = ''
        document.body.style.position = ''
        document.body.style.width = ''
    }

    function watchScrollLock(isLocked: Ref<boolean>): void {
        watch(isLocked, function onScrollLockChange(locked) {
            if (locked) {
                lockScroll()
            } else {
                unlockScroll()
            }
        })
    }

    onUnmounted(function cleanup() {
        unlockScroll()
    })

    return {
        lockScroll,
        unlockScroll,
        watchScrollLock
    }
}
