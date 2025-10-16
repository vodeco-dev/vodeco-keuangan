import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';


export default defineConfig(({ mode }) => {
    // Load env variables based on the current mode
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: 'localhost',
            https: false,
            // The https option is automatically managed by the mkcert plugin
        },
    };
});
