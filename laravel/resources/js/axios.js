// resources/axios.js
import axios from "axios";

const api = axios.create({
  baseURL: "http://localhost:8000/api", // Adjust if using different backend port
  headers: {
    "Content-Type": "application/json",
  },
});

// Dynamically set the token before each request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("authToken");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;