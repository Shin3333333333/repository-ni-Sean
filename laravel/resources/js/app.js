import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios'; // <-- import axios

// Set Axios default Authorization header if token exists
const token = localStorage.getItem('authToken');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

const app = createApp(App);
app.use(router);
app.mount('#app');
