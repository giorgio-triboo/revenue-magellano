import colors from './resources/js/config/colors.js';

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
};
