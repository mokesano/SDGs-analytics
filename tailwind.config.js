/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./public/**/*.php",
    "./pages/**/*.php",
    "./layouts/**/*.php",
    "./components/**/*.php",
    "./includes/**/*.php"
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Background & Neutral
        bg: {
          DEFAULT: '#FFFFFF',
          soft: '#F8FAFC',
          muted: '#F1F5F9',
          dark: '#0F172A',
          darkSoft: '#1E293B',
          darkMuted: '#334155'
        },
        text: {
          DEFAULT: '#1E293B',
          muted: '#64748B',
          dark: '#F8FAFC',
          darkMuted: '#94A3B8'
        },
        border: {
          DEFAULT: '#E2E8F0',
          dark: '#475569'
        },
        // Primary brand
        primary: {
          DEFAULT: '#1E40AF',
          light: '#3B82F6',
          dark: '#1E3A8A'
        },
        accent: '#7C3AED',
        // SDG Colors - Official UN Palette
        sdg1: '#E5243B',
        sdg2: '#DDA63A',
        sdg3: '#4C9F38',
        sdg4: '#C5192D',
        sdg5: '#FF3A21',
        sdg6: '#26BDE2',
        sdg7: '#FCC30B',
        sdg8: '#A21942',
        sdg9: '#FD6925',
        sdg10: '#DD1367',
        sdg11: '#FD9D24',
        sdg12: '#BF8B2E',
        sdg13: '#3F7E44',
        sdg14: '#0A97D9',
        sdg15: '#56C02B',
        sdg16: '#00689D',
        sdg17: '#19486A'
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        heading: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif']
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out',
        'slide-up': 'slideUp 0.5s ease-out',
        'scale-in': 'scaleIn 0.3s ease-out',
        'shimmer': 'shimmer 2s linear infinite'
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(0.95)' },
          '100%': { opacity: '1', transform: 'scale(1)' }
        },
        shimmer: {
          '0%': { backgroundPosition: '-1000px 0' },
          '100%': { backgroundPosition: '1000px 0' }
        }
      }
    }
  },
  plugins: []
}
