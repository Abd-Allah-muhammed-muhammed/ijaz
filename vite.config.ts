import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import {defineConfig} from 'vite';
import {run} from "vite-plugin-run";

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      ssr: 'resources/js/ssr.tsx',
      refresh: true,
    }),
    react(),
    tailwindcss(),
    run([
      {
        name: "wayfinder",
        run: ["php", "artisan", "wayfinder:generate"],
        pattern: ["routes/**/*.php", "app/**/Http/**/*.php"],
      },
      {
        name: "js-enums",
        run: ["php", "artisan", "make:js-enums"],
        pattern: ["app/Enums/**/*Enum.php"],
      },
      {
        name: "js-translations",
        run: ["php", "artisan", "make:js-translations"],
        pattern: ["lang/**/*.php", 'lang/**/*.json'],
      },
      {
        name: "optimize:clear",
        run: ["php", "artisan", "optimize:clear"],
        pattern: ["lang/**/*.php", 'lang/**/*.json'],
      }
    ]),
  ],
});

