var versions = ["1.0", "2.0", "3.0", "4.0", "5.0"];

module.exports = {
    title: 'BinarCode Technical Docs',
    description: 'A package to start the REST API',
    serviceWorker: true,
    base: '/',
    theme: "craftdocs",
    themeConfig: {
        logo: '/assets/img/icon.png',
        displayAllHeaders: true,
        smallerSidebarHeadings: true,
        editLinks: true,
        nextLinks: true,
        prevLinks: true,
        searchMaxSuggestions: 10,
        sidebarDepth: 4,
        docSets: [
            {
                defaultVersion: "5.0",
                primarySet: true,
                baseDir: "docs",
                title: 'Laravel Restify',
                icon: "/assets/img/logo.png",
                sidebarDepth: 4,
                sidebar: {
                    '5.0': require('./laravel-restify/5.0'),
                    '4.0': require('./laravel-restify/4.0'),
                    '3.0': require('./laravel-restify/3.0'),
                    '2.0': require('./laravel-restify/2.0'),
                    '1.0': require('./laravel-restify/1.0'),
                },
                versions: [
                    ["5.0", { label: "5.x" }],
                    ["4.0", { label: "4.x" }],
                    ["3.0", { label: "3.x" }],
                    ["2.0", { label: "2.x" }],
                    ["1.0", { label: "1.x" }]
                ],
            },
            {
                defaultVersion: "1.0",
                primarySet: true,
                baseDir: "docs",
                title: "Code Guidelines",
                icon: "https://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Code.svg/1280px-Code.svg.png",
                sidebarDepth: 4,
                sidebar: {
                    //.. sidebar menus for Code Guidelines
                },
                versions: [
                    ["5.0", { label: "5.x" }],
                    ["4.0", { label: "4.x" }],
                    ["3.0", { label: "3.x" }],
                    ["2.0", { label: "2.x" }],
                    ["1.0", { label: "1.x" }]
                ],
            },
            {
                defaultVersion: "5.0",
                primarySet: true,
                baseDir: "docs",
                title: "Accessibility Guidelines",
                icon: "https://cdn.worldvectorlogo.com/logos/accessibility.svg",
                sidebarDepth: 4,
                sidebar: {
                    '5.0': require('./5.0'),
                    '4.0': require('./4.0'),
                    '3.0': require('./3.0'),
                    '2.0': require('./2.0'),
                    '1.0': require('./1.0'),
                },
                versions: [
                    ["5.0", { label: "5.x" }],
                    ["4.0", { label: "4.x" }],
                    ["3.0", { label: "3.x" }],
                    ["2.0", { label: "2.x" }],
                    ["1.0", { label: "1.x" }]
                ],
            },
            {
                defaultVersion: "5.0",
                primarySet: true,
                baseDir: "docs",
                title: "Vue.js Examples",
                icon: "https://upload.wikimedia.org/wikipedia/commons/thumb/9/95/Vue.js_Logo_2.svg/1184px-Vue.js_Logo_2.svg.png",
                sidebarDepth: 4,
                sidebar: {
                    '5.0': require('./5.0'),
                    '4.0': require('./4.0'),
                    '3.0': require('./3.0'),
                    '2.0': require('./2.0'),
                    '1.0': require('./1.0'),
                },
                versions: [
                    ["5.0", { label: "5.x" }],
                    ["4.0", { label: "4.x" }],
                    ["3.0", { label: "3.x" }],
                    ["2.0", { label: "2.x" }],
                    ["1.0", { label: "1.x" }]
                ],
            }
        ],
        codeLanguages: {
            twig: "Twig",
            php: "PHP",
            graphql: "GraphQL",
            js: "JavaScript",
            json: "JSON",
            xml: "XML",
            treeview: "Folder",
            csv: "CSV"
        },
        feedback: {
            helpful: "Was this page helpful?",
            thanks: "Thanks for your feedback.",
            more: "Give More Feedback →"
        },
        nav: [
            { text: 'Docs', link: '/docs/5.0/' },
            {
                text: "Version",
                link: "/",
                items: [
                    { text: "1.0", link: "/docs/1.0/" },
                    { text: "2.0", link: "/docs/2.0/" },
                    { text: "3.0", link: "/docs/3.0/" },
                    { text: "4.0", link: "/docs/4.0/" },
                    { text: "5.0", link: "/docs/5.0/" },
                ]
            },
            { text: 'Git', link: 'https://github.com/BinarCode/laravel-restify', target: '_blank' },
            { text: 'About us', link: 'https://binarcode.com', target: '_blank' }
        ],

    },
    markdown: {
        extractHeaders: ['h2', 'h3', 'h4', 'h5'],
        anchor: {
            level: [2, 3, 4]
        },
        toc: {
            format(content) {
                return content.replace(/[_`]/g, "");
            }
        },
        extendMarkdown(md) {
            // provide our own highlight.js to customize Prism setup
            md.options.highlight = require("./theme/highlight");
            // add markdown extensions
            md.use(require("./theme/markup"))
                .use(require("markdown-it-deflist"))
                .use(require("markdown-it-imsize"));
        },
    },
    plugins: [
        '@vuepress/pwa',
        (options = {}, context) => ({
            extendPageData($page) {
                const { regularPath, frontmatter } = $page;

                frontmatter.meta = [];

                versions.forEach(function (version) {
                    if ($page.regularPath.includes("/" + version + "/")) {
                        frontmatter.meta.push({
                            name: "docsearch:version",
                            content: version + ".0"
                        });
                    }
                });
            }
        }),
        ['vuepress-plugin-code-copy', true],
        ["vuepress-plugin-container", { type: "tip", defaultTitle: "" }],
        ["vuepress-plugin-container", { type: "warning", defaultTitle: "" }],
        ["vuepress-plugin-container", { type: "danger", defaultTitle: "" }],
        [
            "vuepress-plugin-container",
            {
                type: "details",
                before: info =>
                    `<details class="custom-block details">${
                        info ? `<summary>${info}</summary>` : ""
                    }\n`,
                after: () => "</details>\n"
            }
        ]
    ],
    head: [
        [
            "link",
            {
                href:
                    "https://fonts.googleapis.com/css?family=Montserrat:200,200i,300,300i,400,400i,600,600i,800,800i,900,900i",
                rel: "stylesheet",
                type: "text/css"
            }
        ],
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
    ],
    postcss: {
        plugins: require("../postcss.config.js").plugins
    }
};
