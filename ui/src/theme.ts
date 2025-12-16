import { darkTheme, type GlobalThemeOverrides } from 'naive-ui';

// Softer dark theme with better contrast
export const customDarkTheme: GlobalThemeOverrides = {
    common: {
        primaryColor: '#667eea',
        primaryColorHover: '#5568d3',
        primaryColorPressed: '#4c5fd9',
        primaryColorSuppl: '#7c8aff',

        // Lighter background colors - less harsh black
        bodyColor: '#1a1f2e',
        cardColor: '#242938',
        modalColor: '#2a2f3e',
        popoverColor: '#2a2f3e',
        tableColor: '#1a1f2e',

        // Better text contrast
        textColorBase: '#e5e7eb',
        textColor1: '#f9fafb',
        textColor2: '#d1d5db',
        textColor3: '#9ca3af',

        // Borders
        borderColor: '#374151',
        dividerColor: '#374151',

        // Input backgrounds
        inputColor: '#1f2937',
        inputColorDisabled: '#111827',
    },
    Button: {
        colorHover: 'rgba(255, 255, 255, 0.09)',
        colorPressed: 'rgba(255, 255, 255, 0.13)',
    },
    Input: {
        color: '#1f2937',
        colorFocus: '#1f2937',
    },
    Select: {
        peers: {
            InternalSelection: {
                color: '#1f2937',
            },
        },
    },
    Tabs: {
        tabTextColorActiveBar: '#667eea',
        tabTextColorHoverBar: '#7c8aff',
        barColor: '#667eea',
    }
};

// Light theme overrides
export const customLightTheme: GlobalThemeOverrides = {
    common: {
        primaryColor: '#667eea',
        primaryColorHover: '#5568d3',
        primaryColorPressed: '#4c5fd9',
    }
};

export { darkTheme };
