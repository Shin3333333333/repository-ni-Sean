// resources/axios.js
import axios from "axios";

const api = axios.create({
  baseURL: "http://localhost:8000/api", // Adjust if using different backend port
  headers: {
    "Content-Type": "application/json",
  },
});

// If token exists in localStorage, attach it to every request
const token = localStorage.getItem("authToken");
if (token) {
  api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
}

export default api;
