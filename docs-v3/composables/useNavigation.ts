export const useNavigation = () => {
  const navigationSections = [
    {
      title: 'Getting Started',
      items: [
        { title: 'Introduction', path: '/' },
        { title: 'Quickstart', path: '/quickstart' }
      ]
    },
    {
      title: 'Authentication',
      items: [
        { title: 'Authentication', path: '/auth/authentication' },
        { title: 'Authorization', path: '/auth/authorization' },
        { title: 'Profile', path: '/auth/profile' }
      ]
    },
    {
      title: 'API',
      items: [
        { title: 'Repositories', path: '/api/repositories' },
        { title: 'Fields', path: '/api/fields' },
        { title: 'Actions', path: '/api/actions' },
        { title: 'Relations', path: '/api/relations' },
        { title: 'Getters', path: '/api/getters' },
        { title: 'Advanced Repositories', path: '/api/repositories-advanced' },
        { title: 'Repository Generation', path: '/api/repository-generation' },
        { title: 'REST Methods', path: '/api/rest-methods' },
        { title: 'Serializer', path: '/api/serializer' },
        { title: 'Validation Methods', path: '/api/validation-methods' }
      ]
    },
    {
      title: 'Search',
      items: [
        { title: 'Basic Filters', path: '/search/basic-filters' },
        { title: 'Advanced Filters', path: '/search/advanced-filters' },
        { title: 'Sorting', path: '/search/sorting' }
      ]
    },
    {
      title: 'Performance',
      items: [
        { title: 'Performance', path: '/performance/performance' },
        { title: 'Solutions', path: '/performance/solutions' }
      ]
    },
    {
      title: 'Advanced',
      items: [
        { title: 'GraphQL', path: '/graphql/graphql' },
        { title: 'GraphQL Generation', path: '/graphql/graphql-generation' },
        { title: 'MCP Server', path: '/mcp/mcp' },
        { title: 'Boost', path: '/boost/boost' },
        { title: 'Testing', path: '/testing/testing' }
      ]
    }
  ]

  return {
    navigationSections
  }
}