import path from 'node:path';
import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { run } from 'vite-plugin-run';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.tsx'],
      ssr: 'resources/js/ssr.tsx',
      refresh: true,
    }),
    react(),
    run([
      {
        name: 'wayfinder',
        // Dev/HMR only — running during `vite build` races and deletes actions mid-bundle.
        build: false,
        run: ['php', 'artisan', 'wayfinder:generate'],
        pattern: [
          'routes/**/*.php',
          'app/**/Http/**/*.php',
          'Modules/**/Routes/**/*.php',
          'Modules/**/Http/Controllers/**/*.php',
        ],
      },
      {
        name: 'js-enums',
        build: false,
        run: ['php', 'artisan', 'make:js-enums'],
        pattern: ['app/Enums/**/*Enum.php'],
      },
      {
        name: 'js-translations',
        // Production builds run this in npm `prebuild` so Vite does not regenerate
        // resources/js/lang/*.json mid-bundle (that race corrupts JSON parses).
        // Keep pattern-based HMR generation for `vite`/`npm run dev`.
        build: false,
        run: ['php', 'artisan', 'make:js-translations'],
        pattern: ['lang/**/*.php', 'lang/**/*.json'],
      },
      {
        name: 'optimize:clear',
        build: false,
        run: ['php', 'artisan', 'optimize:clear'],
        pattern: ['lang/**/*.php', 'lang/**/*.json'],
      },
    ]),
  ],
  resolve: {
    // Explicit `@/` alias — previously provided implicitly by @tailwindcss/vite via tsconfig paths.
    alias: [
      {
        find: '@/',
        replacement: `${path.resolve(__dirname, 'resources/js')}/`,
      },
    ],
  },
});
