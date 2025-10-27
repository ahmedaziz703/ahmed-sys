import preset from './vendor/filament/support/tailwind.config.preset'
const colors = require('tailwindcss/colors')

/** @type {import('tailwindcss').Config} */
module.exports = {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    '50':  '#ebf5ff',
                    '100': '#e1effe',
                    '200': '#c3ddfd',
                    '300': '#a4cafe',
                    '400': '#76a9fa',
                    '500': '#3f83f8',
                    '600': '#1c64f2',
                    '700': '#1a56db',
                    '800': '#1e429f',
                    '900': '#233876',
                  },
                  secondary: {
                    '50':  '#f9fafb',
                    '100': '#f4f5f7',
                    '200': '#e5e7eb',
                    '300': '#d2d6dc',
                    '400': '#9fa6b2',
                    '500': '#6b7280',
                    '600': '#4b5563',
                    '700': '#374151',
                    '800': '#252f3f',
                    '900': '#161e2e',
                  },
            },
            filament: {
                toggle: {
                    width: '2.5rem',
                    height: '1.25rem',
                    dotSize: '0.875rem',
                },
            },
        },
      },
    plugins: [
          require('flowbite/plugin'),
          require('@tailwindcss/forms'),
          require('tailwindcss-rtl'),
      ],
}
