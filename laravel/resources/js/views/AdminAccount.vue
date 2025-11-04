<template>
  <div class="admin-account-container">
    <div class="main-content">
      <div class="left-column">
        <div class="user-card">
          <div class="avatar-section" style="display: flex; justify-content: center; align-items: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <h2 class="user-name">{{ user.name }}</h2>
          <p class="user-email">{{ user.email }}</p>
        </div>
      </div>
      <div class="right-column">
        <div class="form-card">
          <h3>Account Information</h3>
          <form @submit.prevent="updateProfile">
            <div class="form-group">
              <label for="name">Name</label>
              <input type="text" id="name" v-model="user.name" :disabled="!isEditingCredentials">
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" v-model="user.email" :disabled="!isEditingCredentials">
            </div>
            <div class="button-group">
              <button type="button" class="btn-secondary" @click="toggleEditCredentials">{{ isEditingCredentials ? 'Cancel' : 'Edit Credentials' }}</button>
              <button type="submit" class="btn-submit" v-if="isEditingCredentials">Update Credentials</button>
            </div>
          </form>
        </div>
        <div class="form-card">
          <h3>Security</h3>
          <div v-if="!isChangingPassword" class="button-group">
            <button type="button" class="btn-secondary" @click="toggleChangePassword">Change Password</button>
          </div>
          <form v-if="isChangingPassword" @submit.prevent="updatePassword">
            <div class="form-group">
              <label for="current_password">Current Password</label>
              <input type="password" id="current_password" v-model="passwordForm.current_password" required>
            </div>
            <div class="form-group">
              <label for="password">New Password</label>
              <input type="password" id="password" v-model="passwordForm.password" required>
            </div>
            <div class="form-group">
              <label for="password_confirmation">Confirm New Password</label>
              <input type="password" id="password_confirmation" v-model="passwordForm.password_confirmation" required>
            </div>
            <div class="button-group">
              <button type="button" class="btn-secondary" @click="toggleChangePassword">Cancel</button>
              <button type="submit" class="btn-submit">Update Password</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios';

export default {
  data() {
    return {
      user: {},
      passwordForm: {
        current_password: '',
        password: '',
        password_confirmation: ''
      },
      isEditingCredentials: false,
      isChangingPassword: false
    };
  },
  mounted() {
    this.fetchUser();
  },
  methods: {
    fetchUser() {
      axios.get('/user')
        .then(response => {
          this.user = response.data;
        })
        .catch(error => {
          console.error('Error fetching user data:', error);
        });
    },
    toggleEditCredentials() {
      this.isEditingCredentials = !this.isEditingCredentials;
    },
    toggleChangePassword() {
      this.isChangingPassword = !this.isChangingPassword;
    },
    updateProfile() {
      axios.put('/user', this.user)
        .then(response => {
          alert('Profile updated successfully!');
          this.isEditingCredentials = false;
        })
        .catch(error => {
          console.error('Error updating profile:', error);
          alert('Error updating profile. Please check the console for details.');
        });
    },
    updatePassword() {
      axios.put('/user/password', this.passwordForm)
        .then(response => {
          alert('Password updated successfully!');
          this.isChangingPassword = false;
          this.passwordForm = {
            current_password: '',
            password: '',
            password_confirmation: ''
          };
        })
        .catch(error => {
          console.error('Error updating password:', error);
          alert('Error updating password. Please check the console for details.');
        });
    }
  }
};
</script>

<style scoped>
.admin-account-container {
  padding: 2rem;
  background-color: #f0f2f5;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
}

.main-content {
  display: flex;
  gap: 2rem;
  width: 100%;
  margin: 0 auto;
}

.left-column {
  flex: 1;
}

.right-column {
  flex: 2;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.user-card {
  background-color: #fff;
  border-radius: 15px;
  box-shadow: 0 6px 12px rgba(0,0,0,0.08);
  padding: 2rem;
  text-align: center;
}

.avatar-section {
  margin-bottom: 1rem;
}

.avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 4px solid #fff;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.user-name {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0.5rem 0;
}

.user-email {
  font-size: 1rem;
  color: #6c757d;
}

.form-card {
  background-color: #fff;
  border-radius: 15px;
  box-shadow: 0 6px 12px rgba(0,0,0,0.08);
  padding: 2rem;
}

.form-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 1rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #495057;
}

.form-group input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #ced4da;
  border-radius: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;
  background-color: #f8f9fa;
}

.form-group input:disabled {
  background-color: #e9ecef;
  cursor: not-allowed;
}

.form-group input:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
  outline: none;
  background-color: #fff;
}

.button-group {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.btn-submit {
  background: linear-gradient(45deg, #007bff, #0056b3);
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  font-weight: 500;
}

.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-secondary:hover {
  background-color: #5a6268;
}

@media (max-width: 768px) {
  .main-content {
    flex-direction: column;
  }
}
</style>