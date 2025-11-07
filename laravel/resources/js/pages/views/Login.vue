<template>
  <div class="login-wrapper">
    <div class="login-card">
      <h1 class="brand">timetable</h1>
      <p class="subtitle">Enter Email & Password</p>

      <input
        v-model="email"
        type="email"
        class="input-field"
        placeholder="Email"
      />

      <div class="password-container">
        <input
          v-model="password"
          :type="passwordFieldType"
          class="input-field"
          placeholder="••••••••"
          @keyup.enter="checkPassword"
        />
        <i 
          class="fa-regular eye-icon"
          :class="[passwordFieldType === 'password' ? 'fa-eye-slash' : 'fa-eye']"
          @click="togglePasswordVisibility"
        ></i>
      </div>

      <button class="login-btn" @click="checkPassword">Login</button>

      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </div>
</template>

<script>
import api from "../../axios"; // ✅ uses shared axios setup

export default {
  name: "Login",
  data() {
    return {
      email: "",
      password: "",
      error: "",
      passwordFieldType: "password",
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
        console.log("User data:", res.data.user);

        // ✅ Save token & set default header for future requests
        localStorage.setItem("authToken", res.data.token);
        // Save user name and type to localStorage
        if (res.data.user) {
          localStorage.setItem("userName", res.data.user.name);
          localStorage.setItem("userType", res.data.user.user_type);
          localStorage.setItem("is_temporary", res.data.user.is_temporary);
          localStorage.setItem("user_id", res.data.user.id);
        }

        // Redirect based on user type and temporary status
        if (res.data.user) {
          if (res.data.user.user_type === 'faculty' && res.data.user.is_temporary) {
            this.$router.push('/faculty-account');
          } else if (res.data.user.user_type === 'faculty') {
            this.$router.push('/faculty/dashboard');
          } else {
            this.$router.push('/dashboard');
          }
        }
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
    togglePasswordVisibility() {
      this.passwordFieldType = this.passwordFieldType === "password" ? "text" : "password";
    },
  },
};
</script>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css');

.login-wrapper {
  width: 100%;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f7f7f7;
}

.login-card {
  width: 350px; /* Bigger */
  min-height: 450px; /* Taller */
  display: flex;
  flex-direction: column;
  justify-content: center;
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

.input-field {
  width: 100%;
  padding: 14px;
  border-radius: 26px;
  background-color: #efefef;
  outline: none;
  text-align: center;
  font-size: 15px;
  border: 1px solid #ddd;
  margin-bottom: 18px;
}

.password-container {
  position: relative;
  width: 100%;
  margin-bottom: 18px;
}

.password-container .input-field {
  margin-bottom: 0;
}

.eye-icon {
  position: absolute;
  top: 50%;
  right: 20px;
  transform: translateY(-50%);
  cursor: pointer;
  color: #888;
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