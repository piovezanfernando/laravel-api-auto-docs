import { createApp } from 'vue';
import App from './App.vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import Lara from '@primevue/themes/lara'; // Import a theme preset

// Global CSS (if any, for now let's create a minimal one)
import './assets/main.css';
import 'primeicons/primeicons.css';                         // PrimeIcons
import 'primeflex/primeflex.css';                       // PrimeFlex (for utility classes like flex, p-3, etc.)

const app = createApp(App);

app.use(createPinia());
app.use(PrimeVue, { ripple: true, theme: { preset: Lara, options: { dark: true, primary: 'blue' } } }); // Use Lara theme preset in dark mode with a blue primary color

app.mount('#app');
