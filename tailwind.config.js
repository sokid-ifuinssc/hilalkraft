import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                hilal: {
                    50:  '#ECFDF5',
                    100: '#D1FAE5',
                    500: '#10B981', // Emerald Primary
                    600: '#059669', // Green Deep Gradient
                    700: '#047857',
                    900: '#064E3B',
                    gold: '#F59E0B', // Aksen gagang tas dari logo hilalkraf
                },
                surface: '#F8FAFC',
            },
            backgroundImage: {
                'hilal-gradient': 'linear-gradient(135deg, #10B981 0%, #059669 100%)',
                'hilal-soft': 'linear-gradient(180deg, #ECFDF5 0%, #FFFFFF 100%)',
            },
            boxShadow: {
                'card': '0 10px 30px -5px rgba(16, 185, 129, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04)',
                'emerald-glow': '0 8px 20px -4px rgba(16, 185, 129, 0.35)',
            },
        },
    },
    plugins: [],
};
