import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import '../css/manage.css';
import api from './axios'; // <-- import axios

// Set Axios default Authorization header if token exists
const token = localStorage.getItem("authToken");
if (token) {
  api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
}
const app = createApp(App);
app.use(router);
app.mount('#app');
