/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './components/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './nuxt.config.{js,ts}',
    './app.vue',
    './content/**/*.md'
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
          950: '#172554'
        },
        gray: {
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827',
          950: '#0f0f23'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'Monaco', 'Consolas', 'monospace']
      },
      typography: (theme) => ({
        DEFAULT: {
          css: {
            maxWidth: 'none',
            color: theme('colors.gray.700'),
            a: {
              color: theme('colors.primary.500'),
              textDecoration: 'none',
              fontWeight: '500',
              '&:hover': {
                color: theme('colors.primary.600'),
                textDecoration: 'underline'
              }
            },
            'h1, h2, h3, h4': {
              color: theme('colors.gray.900'),
              fontWeight: '600'
            },
            code: {
              color: theme('colors.blue.900'),
              backgroundColor: theme('colors.blue.200'),
              padding: '0.375rem 0.5rem',
              borderRadius: '0.375rem',
              fontSize: '0.875em',
              fontWeight: '500'
            },
            'code::before': {
              content: '""'
            },
            'code::after': {
              content: '""'
            },
            pre: {
              backgroundColor: theme('colors.gray.900'),
              color: theme('colors.gray.100')
            },
            'pre code': {
              backgroundColor: 'transparent',
              color: 'inherit',
              padding: '0',
              borderRadius: '0'
            }
          }
        },
        dark: {
          css: {
            color: theme('colors.gray.300'),
            a: {
              color: theme('colors.primary.400'),
              '&:hover': {
                color: theme('colors.primary.300')
              }
            },
            'h1, h2, h3, h4': {
              color: theme('colors.gray.100')
            },
            code: {
              color: theme('colors.blue.300'),
              backgroundColor: theme('colors.gray.700')
            },
            pre: {
              backgroundColor: theme('colors.gray.800')
            },
            blockquote: {
              borderLeftColor: theme('colors.gray.600'),
              color: theme('colors.gray.300')
            },
            hr: {
              borderColor: theme('colors.gray.700')
            },
            strong: {
              color: theme('colors.gray.200')
            }
          }
        }
      })
    }
  },
  plugins: [
    require('@tailwindcss/typography')
  ]
}