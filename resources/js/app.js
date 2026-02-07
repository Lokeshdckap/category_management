import { createApp } from 'vue';
import { Quasar, Notify } from 'quasar';
import axios from 'axios';
import router from './router';

// Import icon libraries
import '@quasar/extras/material-icons/material-icons.css';

// Import Quasar css
import 'quasar/dist/quasar.css';

// Your main app component
import App from './components/App.vue';

// Configure axios - use relative URL since we're on same domain
axios.defaults.baseURL = '/api';  // Changed from http://localhost:8000/api

// Add token from localStorage if exists
const token = localStorage.getItem('auth_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Make axios available globally
const app = createApp(App);

app.config.globalProperties.$axios = axios;

app.use(Quasar, {
    plugins: {
        Notify
    }
});

app.use(router);

app.mount('#app');