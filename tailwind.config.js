import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                blue: {
                    50: '#f2f4ff',
                    100: '#d9dff2',
                    200: '#b3c0e6',
                    300: '#8da0d9',
                    400: '#4d64b3',
                    500: '#1f3a99',
                    600: '#000080',
                    700: '#000066',
                    800: '#00004d',
                    900: '#000033',
                },
            },
        },
    },

    plugins: [forms],
};
