import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'node:path';

// The web root of this project is the repository root (index.php lives there),
// not core/public. Built assets are therefore written to <repo>/build so the
// web server can serve them directly as /build/... — see App\Support\Vite for
// the PHP side that reads the manifest from the same place.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/dashboard.css', 'resources/js/app.tsx'],
            publicDirectory: '..',
            buildDirectory: 'build',
            hotFile: 'public/hot',
            refresh: ['resources/views/inertia/**', 'resources/lang/**'],
        }),
        react(),
    ],
    build: {
        // <repo>/build only ever contains Vite output, so it is safe to clear.
        emptyOutDir: true,
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
