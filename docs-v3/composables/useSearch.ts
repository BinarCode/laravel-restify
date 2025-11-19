export interface SearchResult {
  path: string
  title: string
  description?: string
  category?: string
  excerpt?: string
}

export const useSearch = () => {
  const searchContent = async (query: string): Promise<SearchResult[]> => {
    if (!query.trim()) return []

    try {
      // Search through all content files
      const results = await queryContent()
        .where({
          $or: [
            { title: { $icontains: query } },
            { description: { $icontains: query } }
          ]
        })
        .only(['_path', 'title', 'description', 'category'])
        .limit(15)
        .find()

      return results.map((result: any) => ({
        path: result._path,
        title: result.title || 'Untitled',
        description: result.description,
        category: result.category || 'Documentation',
        excerpt: result.description
      }))
    } catch (error) {
      console.error('Search error:', error)
      return []
    }
  }

  const highlightSearchTerm = (text: string, searchTerm: string): string => {
    if (!searchTerm || !text) return text
    
    const regex = new RegExp(`(${searchTerm})`, 'gi')
    return text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800">$1</mark>')
  }

  return {
    searchContent,
    highlightSearchTerm
  }
}