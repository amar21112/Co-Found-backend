// tailwind.config.js
export default {
    content: ['./resources/views/**/*.blade.php'],
    theme: {
        extend: {
            colors: {
                'cf-primary':       '#6C63FF',
                'cf-primary-dark':  '#4F46E5',
                'cf-primary-light': '#EEEDFF',
                'cf-accent':        '#00D4AA',
                'cf-accent-dark':   '#00A884',
                'cf-bg':            '#0B0B2E',
                'cf-surface':       '#1A1A3E',
                'cf-card':          '#1E1E35',
                'cf-border':        '#2D2D4E',
                'cf-success':       '#22C55E',
                'cf-warning':       '#F59E0B',
                'cf-error':         '#EF4444',
                'cf-info':          '#3B82F6',
            },
            fontFamily: {
                sans: ['"Helvetica Neue"', 'Helvetica', 'Arial', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
