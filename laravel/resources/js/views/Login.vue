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
import api from "../axios"; // ✅ uses shared axios setup

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
      this.error = "";
      try {
        // ✅ Make login request to Laravel backend
        const res = await api.post("/login", {
          email: this.email,
          password: this.password,
        });

        console.log("✅ Login successful:", res.data);

        // ✅ Save token & set default header for future requests
        localStorage.setItem("authToken", res.data.token);
        // Save user name to localStorage
        if (res.data.user && res.data.user.name) {
          localStorage.setItem("userName", res.data.user.name);
        }
        api.defaults.headers.common["Authorization"] = `Bearer ${res.data.token}`;

        // ✅ Redirect to dashboard
        this.$router.push("/dashboard");
      } catch (err) {
        console.log("❌ Login error:", err.response);

        if (err.response && err.response.status === 429) {
          this.error = "Too many login attempts. Please wait a few seconds and try again.";
        } else if (err.response && err.response.status === 401) {
          this.error = "Incorrect email or password.";
        } else {
          this.error = "Something went wrong. Please try again.";
        }

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
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
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
