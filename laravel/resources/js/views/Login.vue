<template>
  <div class="login-wrapper">
    <div class="login-card">
      <h1 class="brand">timetable</h1>
      <p class="subtitle">Enter Email & Password</p>

      <input
        v-model="email"
        type="email"
        class="password-input"
        placeholder="Email"
      />

      <input
        v-model="password"
        type="password"
        class="password-input"
        placeholder="••••••••"
        @keyup.enter="checkPassword"
      />

      <button class="login-btn" @click="checkPassword">Login</button>

      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "Login",
  data() {
    return {
      email: "",
      password: "",
      error: "",
    };
  },
  methods: {
    async checkPassword() {
      try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.post("/login", {
          email: this.email,
          password: this.password
        });
        localStorage.setItem('timetableAuth', 'true');

        // Save token and set default Authorization header
        localStorage.setItem("authToken", res.data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${res.data.token}`;

        this.$router.push("/schedule");
      } catch (err) {
        console.log(err.response); // <-- Add this line to debug
        this.error = "Incorrect email or password";
        this.password = "";
      }
    },
  },
};
</script>

<style scoped>
.login-wrapper {
  width: 100%;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f7f7f7;
}

.login-card {
  width: 290px;
  max-width: calc(100% - 40px);
  background: #efefef;
  border-radius: 22px;
  padding: 34px 28px;
  text-align: center;
  box-shadow: 0 8px 20px rgba(0,0,0,0.06);
}

.brand {
  font-size: 24px;
  margin-bottom: 20px;
  font-weight: 700;
}

.subtitle {
  margin-bottom: 20px;
  color: #333;
  font-size: 15px;
}

.password-input {
  width: 100%;
  padding: 14px;
  border-radius: 26px;
  background-color: #efefef;
  outline: none;
  text-align: center;
  font-size: 15px;
  margin-bottom: 18px;
}

.login-btn {
  width: 100%;
  padding: 14px;
  border-radius: 26px;
  border: none;
  background: #000;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.error {
  margin-top: 12px;
  color: #c0392b;
  font-weight: 600;
}
</style>
