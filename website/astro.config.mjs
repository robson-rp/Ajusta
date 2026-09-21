import { defineConfig } from 'astro/config'
import sitemap from '@astrojs/sitemap'
import tailwindcss from '@tailwindcss/vite'

// Public marketing site for AJUSTA. Built and hosted separately from the app.
export default defineConfig({
  site: 'https://ajusta.ao',
  integrations: [sitemap()],
  vite: {
    plugins: [tailwindcss()],
  },
})
