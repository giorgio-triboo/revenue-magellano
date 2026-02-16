import colors from './resources/js/config/colors.js';
import forms from '@tailwindcss/forms';

export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: colors,
            fontFamily: {
                'sans': ['Poppins', 'sans-serif'],
            },
        },
    },
    plugins: [
        forms,
    ],
};
