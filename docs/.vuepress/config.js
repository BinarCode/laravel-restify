module.exports = {
    title: 'Laravel Restify',
    description: 'A package to start the REST API',
    serviceWorker: true,
    base: '/laravel-restify/',
    themeConfig: {
        logo: '/assets/img/logo.svg',
        displayAllHeaders: true,
        sidebarDepth: 2,

        nav: [
            { text: 'Home', link: '/' },
            { text: 'Guide', link: '/docs/' },
            { text: 'About us', link: 'https://binarcode.com', target: '_blank' }
        ],

        sidebar: [
            {
                title: 'Quick Start',
                path: '/docs/'
            },
            {
                title: 'Repository',
                path: '/docs/repository-pattern/repository-pattern',
            },
            {
                title: 'Field',
                path: '/docs/repository-pattern/field',
            },
            {
                title: 'Authentication',
                path: '/docs/auth/auth',
            },
            {
                title: 'Rest Controller',
                path: '/docs/rest-methods/rest-methods',
            },
            {
                title: 'Error handler',
                path: '/docs/exception-handler/exception-handler',
            },
            {
                title: 'Auth service',
                path: '/docs/auth/auth',
            },
        ]
    },
    plugins: [
        '@vuepress/pwa',
    ],
    head: [
        // Used for PWA
        [
            "link",
            {
                rel: 'manifest',
                href: '/manifest.json'
            }
        ],
        [
            "link",
            {
                rel: 'icon',
                href: '/icon.png'
            }
        ]
    ]
};
