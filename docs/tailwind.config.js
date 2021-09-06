module.exports = {
    purge: {
        enabled: process.env.MY_ENV_VAR === "production" ? true : false,
        content: ["./docs/.vuepress/theme/**/*.vue", "./docs/**/*.md"]
    },
    theme: {
        extend: {
            fontFamily: {
                sans: ["GT Eesti", "Futura Std", "Futura", "Helvetica", "sans-serif"]
            },
            fontSize: {
                '2.5xl': '1.5625rem',
            },
            colors: {
                slate: "#2d3748",
                soft: "#f1f5fd",
                softer: "#fafbfe",
                blue: "#4a7cf6",
                red: "#da5a47",
                cinder: "#131119",
                green: "#27AB83",
                "light-slate": "#718096"
            },
            width: {
                80: "20rem"
            },
            screens: {
                xxl: "1408px"
            }
        }
    },
    variants: {},
    plugins: []
};
