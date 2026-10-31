/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        ink: '#202724',
        text: '#272d29',
        muted: '#5b625c',
        background: '#faf8f4',
        paper: '#fffdfa',
        surface: '#f0ede7',
        border: '#d6d2c8',
        accent: '#9d4c32',
        'accent-soft': '#f3e2d8',
        success: '#2f6548',
      },
      fontFamily: {
        sans: ['Segoe UI', 'Helvetica Neue', 'Arial', 'sans-serif'],
        display: ['Iowan Old Style', 'Baskerville', 'Georgia', 'serif'],
      },
      maxWidth: { container: '1320px' },
      borderRadius: { editorial: '0.5rem' },
      boxShadow: { soft: '0 0.8rem 2.4rem rgba(32, 39, 36, 0.08)' },
    },
  },
  plugins: [],
};
