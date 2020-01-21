var versions = ["1.0", "2.0"];

module.exports = {
    title: 'Laravel Restify',
    description: 'A package to start the REST API',
    serviceWorker: true,
    base: '/',
    themeConfig: {
        logo: '/assets/img/icon.png',
        displayAllHeaders: true,
        sidebarDepth: 2,

        nav: [
            { text: 'Docs', link: '/docs/2.0/' },
            {
                text: "Version",
                link: "/",
                items: [{ text: "1.0", link: "/docs/1.0/" }, { text: "2.0", link: "/docs/2.0/" }]
            },
            { text: 'About us', link: 'https://binarcode.com', target: '_blank' }
        ],

        sidebar: {
            "/docs/1.0/": require("./1.0"),
            "/docs/2.0/": require("./2.0")
        },
    },
    plugins: [
        '@vuepress/pwa',
        (options = {}, context) => ({
            extendPageData($page) {
                const { regularPath, frontmatter } = $page;

                frontmatter.meta = [];

                versions.forEach(function(version) {
                    if ($page.regularPath.includes("/" + version + "/")) {
                        frontmatter.meta.push({
                            name: "docsearch:version",
                            content: version + ".0"
                        });
                    }
                });
            }
        })
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
