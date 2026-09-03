import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

/**
 * Single source of truth for the app's look and feel.
 *
 * The whole system runs on one dark "black" theme — components should
 * reference theme roles (background / surface / primary / success / ...)
 * via Vuetify's color props and utility classes instead of hardcoding
 * hex values in <style> blocks.
 */
const black = {
    dark: true,
    colors: {
        background: '#000000',
        surface: '#121212',
        'surface-bright': '#1c1c1c',
        'surface-variant': '#2a2a2a',
        'on-surface-variant': '#a3a3a3',
        primary: '#3b82f6',
        secondary: '#8b8b8b',
        success: '#22c55e',
        warning: '#f59e0b',
        error: '#ef4444',
        info: '#0ea5e9',
    },
    variables: {
        'border-color': '#2a2a2a',
        'border-opacity': 1,
    },
}

export default createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
    },
    theme: {
        defaultTheme: 'black',
        themes: { black },
    },
})
