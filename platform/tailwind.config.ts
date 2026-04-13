import type { Config } from 'tailwindcss'

const config: Config = {
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        green: '#00D084',
        'green-dark': '#00A86B',
        cyan: '#00B4D8',
        'cyan-dark': '#0090AF',
        yellow: '#F0FF00',
        'yellow-dark': '#C8D400',
        notte: '#1A1A2E',
        'notte-lt': '#2D2D48',
        terracotta: '#C4622D',
        ocra: '#D4A855',
        bosco: '#2D5A27',
        cielo: '#4A90C4',
        surface: '#FAFAF8',
        'surface-alt': '#F0EDE8',
        border: '#E2DDD6',
        nebbia: '#F5F5F0',
      },
      textColor: {
        primary: '#1C1917',
        secondary: '#57534E',
        muted: '#A8A29E',
      },
      fontFamily: {
        playfair: ['var(--font-playfair)'],
        inter: ['var(--font-inter)'],
      },
      animation: {
        float: 'float 3s ease-in-out infinite',
        glow: 'glow 2s ease-in-out infinite',
        'pulse-ring': 'pulse-ring 1.5s infinite',
      },
      keyframes: {
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-20px)' },
        },
        glow: {
          '0%, 100%': { boxShadow: '0 0 20px rgba(0, 208, 132, 0.3)' },
          '50%': { boxShadow: '0 0 40px rgba(0, 180, 216, 0.5)' },
        },
        'pulse-ring': {
          '0%': { boxShadow: '0 0 0 0 rgba(0, 208, 132, 0.7)' },
          '70%': { boxShadow: '0 0 0 30px rgba(0, 208, 132, 0)' },
          '100%': { boxShadow: '0 0 0 0 rgba(0, 208, 132, 0)' },
        },
      },
    },
  },
  plugins: [],
}
export default config
