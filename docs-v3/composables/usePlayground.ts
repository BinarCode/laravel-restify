/**
 * Playground configuration and utilities
 */

// API Configuration
export const PLAYGROUND_API_URL = 'https://api.laravel-restify.com'
export const PLAYGROUND_API_DOMAIN = 'api.laravel-restify.com'

export function usePlayground() {
    const apiUrl = PLAYGROUND_API_URL
    const apiDomain = PLAYGROUND_API_DOMAIN

    /**
     * Build full URL for an endpoint
     */
    function buildUrl(endpoint: string): string {
        const cleanEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint
        return `${apiUrl}/${cleanEndpoint}`
    }

    return {
        apiUrl,
        apiDomain,
        buildUrl
    }
}
