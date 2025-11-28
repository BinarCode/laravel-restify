import { defineCollection, defineContentConfig } from '@nuxt/content'

export default defineContentConfig({
    collections: {
        content: defineCollection({
            // Type 'page' means there's a 1-to-1 relationship between content files and pages
            type: 'page',
            // Load all markdown files from the content directory
            source: '**/*.md'
        })
    }
})
