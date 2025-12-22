export default defineAppConfig({
    ui: {
        accordion: {
            slots: {
                item: 'border-b border-gray-200 dark:border-gray-700/50 last:border-b-0',
                trigger: 'py-4 text-left font-semibold text-gray-900 dark:text-white hover:text-blue-500 dark:hover:text-blue-400 transition-colors',
                content: 'text-gray-600 dark:text-gray-300 leading-relaxed',
                body: 'pb-4'
            }
        }
    }
})
