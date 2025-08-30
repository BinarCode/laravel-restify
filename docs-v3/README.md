# Laravel Restify Docs v3

Modern documentation theme for Laravel Restify built with Nuxt 3, Tailwind CSS, and @nuxt/content v2.

## Features

✨ **Modern Design**
- Clean, responsive design with Tailwind CSS
- Dark/light mode toggle
- Mobile-first responsive navigation
- Professional typography with Inter and JetBrains Mono fonts

🚀 **Performance**
- Built on Nuxt 3 for optimal performance
- Static site generation ready
- Efficient search functionality
- Optimized images and assets

📖 **Content Management**
- Powered by @nuxt/content v2
- Markdown-based content
- Syntax highlighting for code blocks
- Auto-generated table of contents
- Navigation between pages

🔍 **User Experience**
- Global search across all documentation
- Responsive mobile menu
- Breadcrumb navigation
- Previous/next page navigation
- Smooth transitions and animations

## Development

### Prerequisites
- Node.js 18.17+ (due to dependencies)
- npm or yarn

### Setup

```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Generate static site
npm run generate

# Preview production build
npm run preview
```

## Project Structure

```
docs-v3/
├── assets/css/           # Tailwind CSS and custom styles
├── components/           # Vue components
│   ├── TheHeader.vue     # Main navigation header
│   ├── TheSidebar.vue    # Documentation sidebar
│   ├── TheTableOfContents.vue  # Right sidebar TOC
│   ├── ThemeToggle.vue   # Dark/light mode toggle
│   └── SearchInput.vue   # Search functionality
├── content/              # Markdown documentation files
├── layouts/              # Page layouts
├── pages/                # Route pages
├── public/               # Static assets
├── nuxt.config.ts        # Nuxt configuration
├── tailwind.config.js    # Tailwind CSS configuration
└── tsconfig.json         # TypeScript configuration
```

## Configuration

### Theme Colors

The theme uses a custom purple color palette defined in `tailwind.config.js`:

```js
primary: {
  400: '#787af6',  // Main brand color
  500: '#5b5ce0',
  600: '#4c4db8',
  // ... other shades
}
```

### Content Structure

Content should be organized in the `content/` directory with proper frontmatter:

```markdown
---
title: "Page Title"
description: "Page description for SEO"
category: "api"
---

# Content goes here
```

### Navigation

Navigation is automatically generated from the content structure. The sidebar will show all markdown files organized by their directory structure.

## Deployment

### Static Generation

Generate a static site for deployment:

```bash
npm run generate
```

The generated files will be in the `dist/` directory and can be deployed to any static hosting service like:

- Vercel
- Netlify
- GitHub Pages
- AWS S3 + CloudFront

### Server-Side Rendering

For SSR deployment:

```bash
npm run build
npm run preview
```

## Migration from v2

The content from the old docs-v2 has been migrated to the new structure. Key differences:

1. **Content Location**: Moved from `docs-v2/content/` to `docs-v3/content/`
2. **Navigation**: Now auto-generated from directory structure
3. **Components**: All custom components rebuilt for Nuxt 3
4. **Styling**: Migrated from old theme to custom Tailwind CSS
5. **Search**: New implementation with better performance

## Analytics & Integrations

### Google Tag Manager

GTM integration can be added back by:

1. Installing `@nuxtjs/gtm`
2. Adding to `nuxt.config.ts` modules
3. Configuring with your GTM ID

### Tidio Chat

Chat widget is configured in `nuxt.config.ts` app head scripts.

## Troubleshooting

### Node Version Issues

If you encounter native binding errors, ensure you're using Node.js 18.17+ or 20+. The project uses modern dependencies that require recent Node versions.

### Development Server Issues

If the dev server fails to start:

1. Delete `node_modules` and `package-lock.json`
2. Run `npm install` again
3. Try `npm run generate` for static generation

### Content Not Loading

Ensure your markdown files have proper frontmatter and are in the correct directory structure within `content/`.

## Contributing

1. Create feature branches from main
2. Test your changes with `npm run generate`
3. Ensure responsive design works on all screen sizes
4. Follow the existing code style and component patterns

## License

This documentation theme is part of the Laravel Restify project.