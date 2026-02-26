import { createApp } from 'vue';
import { Quasar, Notify, Dialog } from 'quasar';
import axios from 'axios';
import router from './router';

// Import icon libraries
import '@quasar/extras/material-icons/material-icons.css';

// Import Quasar css
import 'quasar/dist/quasar.css';

// Your main app component
import App from './components/App.vue';

// Configure axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

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
        Notify,
        Dialog
    }
});

app.use(router);

app.mount('#app');