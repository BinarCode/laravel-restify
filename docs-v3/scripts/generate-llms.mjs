// Build-time generator for the Forge-style raw-markdown export pipeline.
//
// Emits, into the static output (dist/ during `nuxt generate`):
//   - a raw ".md" twin for every docs page (page route + ".md")
//   - /docs/llms.txt        (index of every page, grouped by sidebar section)
//   - /docs/llms-full.txt   (every page's full markdown concatenated in nav order)
//   - /llms.txt             (llmstxt.org root convention, identical to /docs/llms.txt)
//
// Wired into the build via a `nitro:build:public-assets` hook in nuxt.config.ts,
// and runnable standalone (see the CLI entrypoint at the bottom) for verification.

import { promises as fs } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const NUMERIC_PREFIX = /^\d+\./

function stripNumericPrefix(segment) {
    return segment.replace(NUMERIC_PREFIX, '')
}

function titleize(slug) {
    return slug
        .split('-')
        .map((word) => (word ? word[0].toUpperCase() + word.slice(1) : word))
        .join(' ')
}

function parseFrontmatter(raw) {
    if (!raw.startsWith('---')) {
        return { data: {}, body: raw }
    }

    const end = raw.indexOf('\n---', 3)
    if (end === -1) {
        return { data: {}, body: raw }
    }

    const block = raw.slice(3, end)
    const body = raw.slice(raw.indexOf('\n', end + 1) + 1)

    const data = {}
    for (const line of block.split('\n')) {
        const match = line.match(/^([A-Za-z0-9_-]+):\s*(.*)$/)
        if (!match) {
            continue
        }
        let value = match[2].trim()
        if (
            (value.startsWith('"') && value.endsWith('"')) ||
            (value.startsWith("'") && value.endsWith("'"))
        ) {
            value = value.slice(1, -1)
        }
        data[match[1]] = value
    }

    return { data, body: body.replace(/^\n+/, '') }
}

function firstParagraph(body) {
    for (const rawLine of body.split('\n')) {
        const line = rawLine.trim()
        if (!line) {
            continue
        }
        if (line.startsWith('#') || line.startsWith('```') || line.startsWith('::') || line.startsWith('>')) {
            continue
        }
        const plain = line
            .replace(/\*\*/g, '')
            .replace(/[*_`]/g, '')
            .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
        return plain.length > 200 ? `${plain.slice(0, 197).trimEnd()}...` : plain
    }
    return ''
}

async function walkMarkdown(dir) {
    const entries = await fs.readdir(dir, { withFileTypes: true })
    const files = []

    for (const entry of entries) {
        const full = path.join(dir, entry.name)
        if (entry.isDirectory()) {
            files.push(...(await walkMarkdown(full)))
        } else if (entry.isFile() && entry.name.endsWith('.md')) {
            files.push(full)
        }
    }

    return files
}

async function readSectionTitle(contentDir, sectionDir) {
    try {
        const raw = await fs.readFile(path.join(contentDir, sectionDir, '.navigation.yml'), 'utf8')
        const match = raw.match(/^title:\s*(.*)$/m)
        if (match) {
            return match[1].trim().replace(/^["']|["']$/g, '')
        }
    } catch {
        // No navigation file — fall back to a titleized slug.
    }
    return titleize(stripNumericPrefix(sectionDir))
}

function toPageMeta(contentDir, filePath) {
    const relative = path.relative(contentDir, filePath)
    const segments = relative.split(path.sep)

    const numericFile = segments[segments.length - 1]
    const numericSection = segments.length > 1 ? segments[0] : null

    const cleaned = segments.map((segment) => stripNumericPrefix(segment))
    cleaned[cleaned.length - 1] = cleaned[cleaned.length - 1].replace(/\.md$/, '')

    if (cleaned[cleaned.length - 1] === 'index') {
        cleaned.pop()
    }

    const routePath = ['/docs', ...cleaned].join('/').replace(/\/$/, '') || '/docs'
    const mdRelative = routePath === '/docs' ? 'docs.md' : `${routePath.replace(/^\//, '')}.md`

    return {
        numericSection,
        numericFile,
        section: numericSection,
        routePath,
        mdRelative
    }
}

export async function generateLlms({ contentDir, outputDir, siteUrl }) {
    const base = siteUrl.replace(/\/$/, '')
    const files = await walkMarkdown(contentDir)

    const pages = []
    for (const filePath of files) {
        const raw = await fs.readFile(filePath, 'utf8')
        const { data, body } = parseFrontmatter(raw)
        const meta = toPageMeta(contentDir, filePath)

        const title = data.title || titleize(stripNumericPrefix(meta.numericFile.replace(/\.md$/, '')))
        const description = data.description || firstParagraph(body)

        pages.push({ ...meta, title, description, body })
    }

    pages.sort((a, b) => {
        const sa = a.numericSection || ''
        const sb = b.numericSection || ''
        if (sa !== sb) {
            return sa.localeCompare(sb, undefined, { numeric: true })
        }
        return a.numericFile.localeCompare(b.numericFile, undefined, { numeric: true })
    })

    const sectionTitles = new Map()
    for (const page of pages) {
        if (page.section && !sectionTitles.has(page.section)) {
            sectionTitles.set(page.section, await readSectionTitle(contentDir, page.section))
        }
    }

    await writeMarkdownTwins(pages, outputDir, base)
    const llmsTxt = buildLlmsTxt(pages, sectionTitles, base)
    const llmsFullTxt = buildLlmsFullTxt(pages, base)

    await writeFile(path.join(outputDir, 'docs', 'llms.txt'), llmsTxt)
    await writeFile(path.join(outputDir, 'docs', 'llms-full.txt'), llmsFullTxt)
    await writeFile(path.join(outputDir, 'llms.txt'), llmsTxt)

    return { pages: pages.length }
}

function buildBanner(base) {
    return [
        '> ## Documentation Index',
        `> Fetch the complete documentation index at: ${base}/docs/llms.txt`,
        '> Use this file to discover all available pages before exploring further.'
    ].join('\n')
}

async function writeMarkdownTwins(pages, outputDir, base) {
    const banner = buildBanner(base)

    for (const page of pages) {
        const parts = [banner, '', `# ${page.title}`]
        if (page.description) {
            parts.push('', `> ${page.description}`)
        }
        parts.push('', page.body.trimEnd(), '')

        await writeFile(path.join(outputDir, page.mdRelative), parts.join('\n'))
    }
}

function buildLlmsTxt(pages, sectionTitles, base) {
    const intro = pages.find((page) => page.routePath === '/docs')
    const lines = ['# Laravel Restify', '']
    lines.push(
        intro?.description ||
            'Transform Laravel Eloquent models into JSON:API endpoints and MCP servers automatically.'
    )
    lines.push('')

    if (intro) {
        lines.push(`- [${intro.title}](${base}/${intro.mdRelative}): ${intro.description}`)
    }

    let currentSection = null
    for (const page of pages) {
        if (page === intro) {
            continue
        }
        if (page.section !== currentSection) {
            currentSection = page.section
            lines.push('')
            lines.push(`## ${sectionTitles.get(currentSection) || titleize(stripNumericPrefix(currentSection))}`)
            lines.push('')
        }
        const suffix = page.description ? `: ${page.description}` : ''
        lines.push(`- [${page.title}](${base}/${page.mdRelative})${suffix}`)
    }

    lines.push('')
    return lines.join('\n')
}

function buildLlmsFullTxt(pages, base) {
    const blocks = pages.map((page) => {
        const parts = [`# ${page.title}`, '', `Source: ${base}${page.routePath}`]
        if (page.description) {
            parts.push('', `> ${page.description}`)
        }
        parts.push('', page.body.trimEnd())
        return parts.join('\n')
    })

    return `${blocks.join('\n\n---\n\n')}\n`
}

async function writeFile(filePath, contents) {
    await fs.mkdir(path.dirname(filePath), { recursive: true })
    await fs.writeFile(filePath, contents, 'utf8')
}

const isCli = process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1])

if (isCli) {
    const contentDir = process.argv[2] || path.resolve(process.cwd(), 'content/docs')
    const outputDir = process.argv[3] || path.resolve(process.cwd(), 'dist')
    const siteUrl = process.env.SITE_URL || 'https://laravel-restify.com'

    generateLlms({ contentDir, outputDir, siteUrl })
        .then((result) => {
            process.stdout.write(`[generate-llms] wrote ${result.pages} pages into ${outputDir}\n`)
        })
        .catch((error) => {
            console.error('[generate-llms] failed:', error)
            process.exit(1)
        })
}
