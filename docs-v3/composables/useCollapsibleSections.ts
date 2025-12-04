import { ref, readonly } from 'vue'

export function useCollapsibleSections(initialCollapsed: string[] = []) {
    const collapsedSections = ref<Set<string>>(new Set(initialCollapsed))

    function toggleSection(sectionTitle: string): void {
        if (collapsedSections.value.has(sectionTitle)) {
            collapsedSections.value.delete(sectionTitle)
            return
        }
        collapsedSections.value.add(sectionTitle)
    }

    function isSectionCollapsed(sectionTitle: string): boolean {
        return collapsedSections.value.has(sectionTitle)
    }

    function collapseAll(sections: string[]): void {
        collapsedSections.value = new Set(sections)
    }

    function expandAll(): void {
        collapsedSections.value.clear()
    }

    return {
        collapsedSections: readonly(collapsedSections),
        toggleSection,
        isSectionCollapsed,
        collapseAll,
        expandAll
    }
}
