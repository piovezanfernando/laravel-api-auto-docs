import { createApp } from 'vue';
import App from './App.vue';
import { createPinia } from 'pinia';

// Global CSS
import './assets/main.css';

const app = createApp(App);

app.use(createPinia());

app.mount('#app');
