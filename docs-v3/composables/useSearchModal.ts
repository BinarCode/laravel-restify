// Global search modal state using reactive
const searchModalState = reactive({
  isOpen: false
})

export const useSearchModal = () => {
  const openModal = () => {
    searchModalState.isOpen = true
  }

  const closeModal = () => {
    searchModalState.isOpen = false
  }

  const toggleModal = () => {
    searchModalState.isOpen = !searchModalState.isOpen
  }

  return {
    isOpen: readonly(toRef(searchModalState, 'isOpen')),
    openModal,
    closeModal,
    toggleModal
  }
}