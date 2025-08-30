# Deployment Guide - Laravel Restify Docs v3

## Quick Start

The documentation theme is now **working properly** with navigation fixed!

### Prerequisites
- Node.js 18.17+ or 20+ (required for native bindings)
- npm or yarn

### Development

```bash
# Ensure correct Node version
source ~/.nvm/nvm.sh && nvm use --lts

# Install dependencies (if not already installed)
npm install

# Start development server
npm run dev
```

**Server will start on:** `http://localhost:3000` or `http://localhost:3001`

## ✅ Fixed Issues

### 1. **Navigation Fixed**
- ✅ Content moved from `/en/` to root level
- ✅ Routes now work: `/quickstart`, `/api/actions`, `/api/fields`, etc.
- ✅ Sidebar navigation auto-generates from content structure

### 2. **Custom Components Added**
- ✅ `<alert type="info|warning|success|error">` components work
- ✅ `<list :items="[...]">` components work  
- ✅ All markdown content renders properly

### 3. **Dependencies Fixed**
- ✅ Added missing `@nuxt/kit` dependency
- ✅ Removed incompatible GTM package
- ✅ All modules load correctly

## 🎨 Theme Features

### Design
- **Modern responsive design** with Tailwind CSS
- **Dark/light mode toggle** with system preference detection
- **Mobile-first navigation** with collapsible menu
- **Professional typography** (Inter + JetBrains Mono)

### Navigation
- **Auto-generated sidebar** from content structure
- **Table of contents** on right sidebar for long pages
- **Search functionality** across all documentation
- **Breadcrumb navigation** and prev/next page links

### Content
- **Markdown with frontmatter** support
- **Syntax highlighting** for code blocks
- **Custom alert components** (info, warning, success, error)
- **Custom list components** with bullet points
- **SEO optimized** with proper meta tags

## 🚀 Deployment Options

### Static Generation
```bash
# Generate static site
npm run generate

# Files will be in .output/public/
# Deploy to any static hosting (Vercel, Netlify, etc.)
```

### Server-Side Rendering
```bash
# Build for production
npm run build

# Preview production build
npm run preview
```

## 📁 Content Structure

```
content/
├── index.md           # Homepage content
├── quickstart.md      # Getting started guide
├── api/
│   ├── actions.md     # API Actions
│   ├── fields.md      # Field types
│   └── ...           # Other API docs
├── auth/
│   ├── authentication.md
│   └── authorization.md
└── ...
```

### Adding Content

1. Create markdown files with proper frontmatter:
```markdown
---
title: "Page Title"
description: "Page description for SEO"
category: "API"
---

# Content goes here
```

2. Use custom components:
```markdown
<alert type="info">
This is an info alert
</alert>

<list :items="['Item 1', 'Item 2', 'Item 3']" />
```

## 🔧 Configuration

### Theme Colors
Edit `tailwind.config.js` to customize colors:
```js
primary: {
  400: '#787af6',  // Main brand color
  // ... other shades
}
```

### Site Settings
Edit `nuxt.config.ts` for site configuration:
- Title and meta tags
- Analytics integration
- Module configuration

## 📊 Analytics & Integrations

### Google Analytics
Add Google Tag Manager back by:
1. Installing `@nuxtjs/gtm`
2. Adding to `nuxt.config.ts` modules
3. Configuring with your GTM ID

### Chat Widget
Tidio chat is configured in `nuxt.config.ts` head scripts.

## ✅ Testing

The theme has been tested and is working with:
- ✅ All navigation links functional
- ✅ Content rendering with custom components
- ✅ Responsive design on mobile/desktop
- ✅ Dark/light mode switching
- ✅ Search functionality
- ✅ Table of contents generation

## 🎯 Performance

- **Static generation ready** for maximum performance
- **Code splitting** for efficient loading
- **Image optimization** support
- **SEO optimized** with proper meta tags and structure

---

**The documentation theme is now fully functional and ready for production use!** 🚀