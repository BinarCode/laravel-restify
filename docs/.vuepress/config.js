module.exports = {
    title: 'Laravel Restify',
    description: 'A package to start the REST API',
    serviceWorker: true,
    themeConfig: {
        logo: '/assets/img/logo.svg',
        displayAllHeaders: true,
        sidebarDepth: 2,

        nav: [
            { text: 'Home', link: '/' },
            { text: 'Guide', link: '/docs/' },
            { text: 'About us', link: 'https://binarcode.com', target: '_blank' }
        ],
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
