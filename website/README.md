# AJUSTA — website

Public landing page, built and hosted separately from the app (Astro + Tailwind).

```bash
cd website
pnpm install
pnpm dev       # http://localhost:4321
pnpm build     # static output in dist/
```

Before launch, fill in `src/site.config.ts` (domain, app URL, contact email/WhatsApp),
add real screenshots to `public/screenshots/` (and reference them in `src/pages/index.astro`),
and replace the draft Privacy/Terms pages.
