<template>
  <div class="faculty-account-container">
    <ConfirmModal
      :show="showConfirmModal"
      :title="confirmTitle"
      :message="confirmMessage"
      @confirm="confirmAction"
      @cancel="showConfirmModal = false"
    />
    <div class="main-content">
      <div class="left-column">
        <div class="user-card">
          <div class="avatar-section" style="display: flex; justify-content: center; align-items: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <h2 class="user-name">{{ user.name }} {{ user.last_name }}</h2>
          <p class="user-email">{{ user.email }}</p>
        </div>
      </div>
      <div class="right-column">
        <div class="form-card">
          <div class="form-header">
            <h3>Account Information</h3>
            <button v-if="!isEditingAccountInfo" @click="startEditingAccountInfo" class="btn-edit" type="button">Edit</button>
          </div>
          <form @submit.prevent="updateProfile">
            <div class="form-group">
              <label for="name">First Name</label>
              <input type="text" id="name" v-model="user.name" :disabled="!isEditingAccountInfo" required>
            </div>
            <div class="form-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" v-model="user.last_name" :disabled="!isEditingAccountInfo" required>
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" v-model="user.email" :disabled="!isEditingAccountInfo" required>
            </div>
            <div class="button-group" v-if="isEditingAccountInfo">
              <button type="submit" class="btn-submit">Update Credentials</button>
              <button type="button" @click="cancelEditingAccountInfo" class="btn-cancel">Cancel</button>
            </div>
          </form>
        </div>
        <div class="form-card">
          <h3 @click="showPasswordUpdate = !showPasswordUpdate" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
            <span>Security</span>
            <span v-if="!showPasswordUpdate">+</span>
            <span v-if="showPasswordUpdate">-</span>
          </h3>
          <form @submit.prevent="updatePassword" v-if="showPasswordUpdate">
            <div class="form-group">
              <label for="password">New Password</label>
              <input type="password" id="password" v-model="passwordForm.password" required>
            </div>
            <div class="form-group">
              <label for="password_confirmation">Confirm New Password</label>
              <input type="password" id="password_confirmation" v-model="passwordForm.password_confirmation" required>
            </div>
            <div class="button-group">
              <button type="submit" class="btn-submit">Update Password</button>
            </div>
          </form>
        </div>
        <div class="form-card">
          <div class="form-header">
            <h3>Professor Details</h3>
            <button v-if="!isEditingProfessorDetails" @click="startEditingProfessorDetails" class="btn-edit" type="button">Edit</button>
          </div>
          <form @submit.prevent="updateProfessorDetails">
            <fieldset :disabled="!isEditingProfessorDetails">
              <div class="form-group">
                <label for="specialization">Specialization</label>
                <select id="specialization" v-model="professor.specialization">
                  <option v-for="department in departments" :key="department" :value="department">{{ department }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="type">Type</label>
                <select id="type" v-model="professor.type">
                  <option value="Full-time">Full-time</option>
                  <option value="Part-time">Part-time</option>
                </select>
              </div>
              <div class="form-group">
                <label class="section-label">Time Unavailable:</label>
                <div class="day-selection">
                  <div class="day-grid">
                    <div
                      v-for="day in weekDays"
                      :key="day.value"
                      class="day-item"
                      :class="{
                        'is-selected': selectedDays.includes(day.value),
                        'is-whole-day': wholeDayUnavailable.includes(day.value),
                        'disabled': !isEditingProfessorDetails
                      }"
                      @click="toggleDay(day.value)"
                    >
                      <span class="day-name">{{ day.name }}</span>
                    </div>
                  </div>
                </div>
                <div v-if="selectedDays.length > 0" class="time-range-section">
                  <div class="time-range-header">
                    <span>Set time ranges per selected day (optional):</span>
                  </div>
                  <div class="unavailable-items">
                    <div
                      v-for="item in perDayItems"
                      :key="item.dayValue"
                      class="unavailable-item"
                    >
                      <div class="item-content">
                        <span class="day-name">{{ item.dayName }}</span>
                        <div class="checkbox-label">
                          <input type="checkbox" v-model="item.useSpecific" @change="setDayUseSpecific(item.dayValue, item.useSpecific)" />
                          <span>Use specific time range</span>
                        </div>
                        <div v-if="item.useSpecific" class="time-inputs">
                          <div class="time-input-group">
                            <label>From:</label>
                            <input type="time" class="time-input" v-model="item.start" @change="onDayTimeChanged()" />
                          </div>
                          <div class="time-input-group">
                            <label>To:</label>
                            <input type="time" class="time-input" v-model="item.end" @change="onDayTimeChanged()" />
                          </div>
                        </div>
                        <div class="time-info">Current: {{ item.isWholeDay ? 'Whole Day' : `${item.start} - ${item.end}` }}</div>
                      </div>
                      <button type="button" class="remove-btn" @click="removeUnavailableByDay(item.dayValue)">×</button>
                    </div>
                  </div>
                </div>
              </div>
            </fieldset>
            <div class="button-group" v-if="isEditingProfessorDetails">
              <button type="submit" class="btn-submit">Update Details</button>
              <button type="button" @click="cancelEditingProfessorDetails" class="btn-cancel">Cancel</button>
            </div>
          </form>
        </div>
     
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../../../axios';
import { useToast } from '../../../composables/useToast';
import emitter from '@/eventBus';
import ConfirmModal from "@/components/ConfirmModal.vue";

export default {
  components: {
    ConfirmModal,
  },
  setup() {
    const { success, error } = useToast();
    return { success, error };
  },
  data() {
    return {
      showConfirmModal: false,
      confirmTitle: "",
      confirmMessage: "",
      confirmAction: () => {},
      user: {},
      passwordForm: {
        password: '',
        password_confirmation: '',
      },
      professor: {
        specialization: '',
        type: '',
        time_unavailable: ''
      },
      departments: [],
      showPasswordUpdate: false,
      isEditingAccountInfo: false,
      isEditingProfessorDetails: false,
      originalUser: null,
      originalProfessor: null,
      isTemporary: false, // Add this line
      weekDays: [
        { name: "Mon", value: 1 },
        { name: "Tue", value: 2 },
        { name: "Wed", value: 3 },
        { name: "Thu", value: 4 },
        { name: "Fri", value: 5 },
        { name: "Sat", value: 6 }
      ],
      selectedDays: [],
      wholeDayUnavailable: [],
      perDayItems: []
    };
  },
  watch: {
    professor: {
      deep: true,
      handler(newVal) {
        if (newVal.time_unavailable) {
          this.parseTimeUnavailable(newVal.time_unavailable);
        }
      }
    }
  },
  async mounted() {
    this.isTemporary = localStorage.getItem('is_temporary') === 'true';
    await this.fetchDepartments();
    this.fetchUser();
    this.fetchProfessorDetails();
  },
  beforeRouteLeave(to, from, next) {
    if (this.isTemporary && !this.areAllFieldsFilled()) {
      this.error('Please fill in all required fields before leaving this page.');
      next(false);
    } else {
      next();
    }
  },
  methods: {
    fetchDepartments() {
      return new Promise((resolve, reject) => {
        axios.get('/departments')
          .then(response => {
            this.departments = Object.values(response.data).map(dept => dept.name);
            resolve();
          })
          .catch(error => {
            console.error('Error fetching departments:', error);
            reject(error);
          });
      });
    },
    fetchUser() {
      axios.get('/user')
        .then(response => {
          if (response.data) {
            this.user = response.data;
            const nameParts = this.user.name.split(' ');
            this.user.last_name = nameParts.length > 1 ? nameParts.pop() : '';
            this.user.name = nameParts.join(' ');
          }
        })
        .catch(error => {
          console.error('Error fetching user data:', error);
        });
    },
    fetchProfessorDetails() {
      axios.get('/professor/details')
        .then(response => {
          const { department, ...details } = response.data;
          this.professor = { ...details, specialization: department };
        })
        .catch(error => {
          console.error('Error fetching professor details:', error);
        });
    },
    updateProfile() {
      this.showConfirm(
        "Confirm Profile Update",
        "Are you sure you want to update your account information?",
        this.executeProfileUpdate
      );
    },
    executeProfileUpdate() {
      this.showConfirmModal = false;
      const userToUpdate = { name: `${this.user.name} ${this.user.last_name}`.trim(), email: this.user.email };
      axios.put('/user', userToUpdate)
        .then(() => {
          this.success('Profile updated successfully!');
          this.isEditingAccountInfo = false;
          this.originalUser = null;
          this.fetchUser();
          localStorage.setItem('userName', `${this.user.name} ${this.user.last_name}`);
          emitter.emit('user-updated');
        })
        .catch(error => {
          if (error.response && error.response.data && error.response.data.errors) {
            console.error('Validation errors:', error.response.data.errors);
            this.error('Please correct the validation errors.');
          } else {
            console.error('Error updating profile:', error);
            this.error('Error updating profile. Please check the console for details.');
          }
        });
    },
    updatePassword() {
      this.showConfirm(
        "Confirm Password Update",
        "Are you sure you want to update your password?",
        this.executePasswordUpdate
      );
    },
    executePasswordUpdate() {
      this.showConfirmModal = false;
      axios.put('/user/password', this.passwordForm)
        .then(response => {
          this.success('Password updated successfully!');
          this.passwordForm = {
            password: '',
            password_confirmation: ''
          };
          this.showPasswordUpdate = false;
          localStorage.removeItem('is_temporary');
          this.isTemporary = false;
        })
        .catch(error => {
          console.error('Error updating password:', error);
          this.error('Error updating password. Please check the console for details.');
        });
    },
    updateProfessorDetails() {
      this.showConfirm(
        "Confirm Details Update",
        "Are you sure you want to update your professor details?",
        this.executeProfessorDetailsUpdate
      );
    },
    executeProfessorDetailsUpdate() {
      this.showConfirmModal = false;
      const payload = {
        name: `${this.user.name} ${this.user.last_name}`.trim(),
        type: this.professor.type,
        specialization: this.professor.specialization,
        time_unavailable: this.professor.time_unavailable,
        status: 'Active',
      };

      axios.put('/professor/details', payload)
        .then(response => {
          this.success('Professor details updated successfully!');
          this.isEditingProfessorDetails = false;
          this.fetchProfessorDetails();
          this.fetchUser();
        })
        .catch(error => {
          console.error('Error updating professor details:', error);
          this.error('Error updating professor details. Please check the console for details.');
        });
    },
    startEditingAccountInfo() {
      this.originalUser = JSON.parse(JSON.stringify(this.user));
      this.isEditingAccountInfo = true;
    },
    cancelEditingAccountInfo() {
      this.user = this.originalUser;
      this.isEditingAccountInfo = false;
      this.originalUser = null;
    },
    startEditingProfessorDetails() {
      this.isEditingProfessorDetails = true;
    },
    cancelEditingProfessorDetails() {
      this.fetchProfessorDetails(); // Re-fetch to discard changes
      this.isEditingProfessorDetails = false;
    },
    areAllFieldsFilled() {
      return this.professor.specialization && this.professor.type && this.user.name && this.user.last_name;
    },
    showConfirm(title, message, action) {
      this.confirmTitle = title;
      this.confirmMessage = message;
      this.confirmAction = action;
      this.showConfirmModal = true;
    },
    parseTimeUnavailable(timeString) {
        if (!timeString || typeof timeString !== 'string') {
            this.selectedDays = [];
            this.wholeDayUnavailable = [];
            this.updatePerDayItems();
            return;
        }

        const parts = timeString.split(',').map(p => p.trim());
        const selected = [];
        const wholeDay = [];
        const perDay = [];

        const dayMap = { Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };

        parts.forEach(part => {
            const [dayStr, timeRange] = part.split(' ');
            const dayValue = dayMap[dayStr];
            if (dayValue) {
                selected.push(dayValue);
                const isWholeDay = timeRange === '01:00-24:00';
                if (isWholeDay) {
                    wholeDay.push(dayValue);
                }
                perDay.push({
                    dayValue,
                    dayName: dayStr,
                    useSpecific: !isWholeDay,
                    start: isWholeDay ? '09:00' : timeRange.split('-')[0],
                    end: isWholeDay ? '17:00' : timeRange.split('-')[1],
                    isWholeDay
                });
            }
        });

        this.selectedDays = [...new Set(selected)];
        this.wholeDayUnavailable = [...new Set(wholeDay)];
        
        this.updatePerDayItems();
        this.perDayItems.forEach(item => {
            const parsedItem = perDay.find(p => p.dayValue === item.dayValue);
            if (parsedItem) {
                item.useSpecific = parsedItem.useSpecific;
                item.start = parsedItem.start;
                item.end = parsedItem.end;
                item.isWholeDay = parsedItem.isWholeDay;
            }
        });
    },

    toggleDay(dayValue) {
      if (!this.isEditingProfessorDetails) return;
      const index = this.selectedDays.indexOf(dayValue);
      if (index > -1) {
        this.selectedDays.splice(index, 1);
        this.wholeDayUnavailable = this.wholeDayUnavailable.filter(d => d !== dayValue);
      } else {
        this.selectedDays.push(dayValue);
        this.wholeDayUnavailable.push(dayValue);
      }
      this.updatePerDayItems();
      this.updateProfessorTimeUnavailable();
    },

    setDayUseSpecific(dayValue, useSpecific) {
      if (!this.isEditingProfessorDetails) return;
      const item = this.perDayItems.find(i => i.dayValue === dayValue);
      if (item) {
        item.useSpecific = useSpecific;
        if (useSpecific) {
          this.wholeDayUnavailable = this.wholeDayUnavailable.filter(d => d !== dayValue);
          item.isWholeDay = false;
        } else {
          if (!this.wholeDayUnavailable.includes(dayValue)) {
            this.wholeDayUnavailable.push(dayValue);
          }
          item.isWholeDay = true;
        }
      }
      this.updateProfessorTimeUnavailable();
    },

    onDayTimeChanged() {
      if (!this.isEditingProfessorDetails) return;
      this.updateProfessorTimeUnavailable();
    },

    removeUnavailableByDay(dayValue) {
      if (!this.isEditingProfessorDetails) return;
      this.selectedDays = this.selectedDays.filter(d => d !== dayValue);
      this.wholeDayUnavailable = this.wholeDayUnavailable.filter(d => d !== dayValue);
      this.updatePerDayItems();
      this.updateProfessorTimeUnavailable();
    },

    updatePerDayItems() {
      this.perDayItems = this.selectedDays.map(dayValue => {
        const existingItem = this.perDayItems.find(i => i.dayValue === dayValue);
        const dayName = this.weekDays.find(d => d.value === dayValue).name;
        const isWholeDay = this.wholeDayUnavailable.includes(dayValue);

        return {
          dayValue,
          dayName,
          useSpecific: existingItem ? existingItem.useSpecific : !isWholeDay,
          start: existingItem ? existingItem.start : '09:00',
          end: existingItem ? existingItem.end : '17:00',
          isWholeDay
        };
      });
    },

    updateProfessorTimeUnavailable() {
      const unavailable = [];
      this.perDayItems.forEach(item => {
        const dayName = item.dayName;
        if (this.selectedDays.includes(item.dayValue)) {
            if (item.useSpecific) {
                unavailable.push(`${dayName} ${item.start}-${item.end}`);
            } else {
                unavailable.push(`${dayName} 01:00-24:00`);
            }
        }
      });
      this.professor.time_unavailable = unavailable.join(', ');
    }
  }
};
</script>

<style scoped>
.faculty-account-container {
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

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-edit {
  background-color: transparent;
  border: 1px solid #007bff;
  color: #007bff;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
}

.btn-edit:hover {
  background-color: #007bff;
  color: white;
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

.form-group input, .form-group select {
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

.form-group input:focus, .form-group select:focus {
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

.btn-cancel {
  background-color: #6c757d;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
  font-weight: 500;
}

.btn-cancel:hover {
  background-color: #5a6268;
}

@media (max-width: 768px) {
  .main-content {
    flex-direction: column;
  }
}

.section-label {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #343a40;
}

.day-selection {
  margin-bottom: 1rem;
}
.day-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 0.75rem;
}
.day-item {
  padding: 0.75rem 0.5rem;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  background-color: #fff;
  font-weight: 500;
}
.day-item.disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.day-item:not(.disabled):hover {
  border-color: #007bff;
  color: #007bff;
}
.day-item.is-selected {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}
.day-item.is-whole-day {
  background-color: #28a745;
  color: white;
  border-color: #28a745;
}
.time-range-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}
.unavailable-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.unavailable-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}
.item-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}
.day-name {
  font-weight: 600;
  min-width: 40px;
}
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.time-inputs {
  display: flex;
  gap: 1rem;
  align-items: center;
}
.time-input-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.time-input {
  border: 1px solid #ced4da;
  border-radius: 4px;
  padding: 0.375rem 0.75rem;
  width: 120px;
}
.remove-btn {
  background: none;
  border: none;
  color: #dc3545;
  font-size: 1.5rem;
  cursor: pointer;
  line-height: 1;
}
</style>