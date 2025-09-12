import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';


export default defineConfig(({ mode }) => {
    // Load env variables based on the current mode
    const env = loadEnv(mode, process.cwd(), '');

    // Determine if HTTPS should be used
    const useHttps = env.VITE_DEV_SERVER_HTTPS === 'true';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            // Conditionally add the mkcert plugin
            ...(useHttps ? [mkcert()] : []),
        ],
        server: {
            host: 'localhost',
            // The https option is automatically managed by the mkcert plugin
        },
    };
});
