import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['Dosis', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50:  '#f5f0ff',
                    100: '#ede5ff',
                    200: '#ddd0ff',
                    300: '#c4abff',
                    400: '#a78bfa',
                    500: '#7c3aed',
                    600: '#6d28d9',
                    700: '#5b21b6',
                    800: '#4c1d95',
                    900: '#3b0764',
                    950: '#2e0552',
                },
                accent: {
                    rose: '#F472B6',
                    blue: '#00bbf9',
                    turquoise: '#00f5d4',
                    yellow: '#fee440',
                },
                surface: {
                    dark: '#0f172a',
                    card: '#1e293b',
                },
            },
        },
    },

    plugins: [forms, typography],
};
