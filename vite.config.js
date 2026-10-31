import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [laravel({ input: ['resources/css/frontend/app.css', 'resources/css/admin/app.css', 'resources/js/frontend/app.js', 'resources/js/admin/app.js'], refresh: true })],
});
