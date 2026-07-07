export default defineAppConfig({
    ui: {
        // Docs search palette: highlighted match state + consistent grouped/footer styling.
        // `<mark>` tags are injected by @nuxt/ui's fuse highlighter (includeMatches) into
        // the heading (itemLabelBase) and body-content (itemLabelSuffix) of each result.
        commandPalette: {
            slots: {
                label: 'text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500',
                itemLabelBase: '[&_mark]:bg-blue-100 [&_mark]:dark:bg-blue-500/25 [&_mark]:text-blue-700 [&_mark]:dark:text-blue-300 [&_mark]:rounded [&_mark]:px-0.5 [&_mark]:font-semibold',
                itemLabelSuffix: '[&_mark]:bg-blue-100/70 [&_mark]:dark:bg-blue-500/20 [&_mark]:text-blue-700 [&_mark]:dark:text-blue-300 [&_mark]:rounded [&_mark]:px-0.5',
                footer: 'px-3 py-2.5 border-t border-gray-200 dark:border-gray-800'
            }
        },
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
