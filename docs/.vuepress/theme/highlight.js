/**
 * Customized highlight.js to mess with Prism setup. Original here:
 * https://github.com/vuejs/vuepress/blob/master/packages/@vuepress/markdown/lib/highlight.js
 */
const prism = require('prismjs')

// use Treeview plugin
require('prismjs/plugins/treeview/prism-treeview.js')

// handle our custom placeholders
require('./prism-placeholders')

const loadLanguages = require('prismjs/components/index')
const { logger, chalk, escapeHtml } = require('@vuepress/shared-utils')

// required to make embedded highlighting work...
loadLanguages(['markup', 'css', 'javascript'])

function wrap (code, lang) {
    if (lang === 'text') {
        code = escapeHtml(code)
    }
    return `<pre v-pre class="language-${lang}"><code>${code}</code></pre>`
}

function getLangCodeFromExtension (extension) {
    const extensionMap = {
        vue: 'markup',
        html: 'markup',
        md: 'markdown',
        rb: 'ruby',
        ts: 'typescript',
        py: 'python',
        sh: 'bash',
        yml: 'yaml',
        styl: 'stylus',
        kt: 'kotlin',
        rs: 'rust'
    }

    return extensionMap[extension] || extension
}

module.exports = (str, lang) => {
    if (!lang) {
        return wrap(str, 'text')
    }

    lang = lang.toLowerCase()
    const rawLang = lang

    lang = getLangCodeFromExtension(lang)

    if (!prism.languages[lang]) {
        try {
            loadLanguages([lang])
        } catch (e) {
            logger.warn(chalk.yellow(`[vuepress] Syntax highlight for language "${lang}" is not supported.`))
        }
    }

    if (prism.languages[lang]) {
        const code = prism.highlight(str, prism.languages[lang], lang)
        return wrap(code, rawLang)
    }

    return wrap(str, 'text')
}
