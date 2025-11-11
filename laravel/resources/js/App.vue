<template>
  <!-- unauthenticated -->

  <div v-if="!isAuthenticated" class="login-full">
    <router-view />
    <LoadingModal /> 
  </div>

  <!-- authenticated -->
  <div v-else class="app-layout">

    <!-- sidebar -->
    <aside class="sidebar" :class="{ open: sidebarOpen }">

      <h1 class="logo side-logo">timetable</h1>
      <nav class="side-nav-list">
        <!-- Admin & Superadmin Links -->
        <template v-if="userType === 'admin' || userType === 'superadmin'">
          <router-link to="/dashboard" class="side-link" active-class="active">Dashboard</router-link>
          <router-link to="/manage" class="side-link" active-class="active">Manage</router-link>
          <router-link to="/schedule" class="side-link" active-class="active">View Schedule</router-link>
        </template>

        <!-- Faculty Links -->
        <template v-if="userType === 'faculty'">
          <router-link to="/faculty/dashboard" class="side-link" active-class="active">Dashboard</router-link>
          <router-link to="/faculty/calendar" class="side-link" active-class="active">Calendar</router-link>
          <router-link to="/faculty/history" class="side-link" active-class="active">Schedule History</router-link>
          <router-link to="/faculty-account" class="side-link" active-class="active">Account</router-link>
          <!-- You can add more faculty-specific links here -->
        </template>
      </nav>

      <!-- Admin & Superadmin Buttons -->
      <template v-if="userType === 'admin' || userType === 'superadmin'">
        <router-link to="/create" class="generate-btn" active-class="active">Generate</router-link>
      </template>
      
    </aside>

    <!-- for mobile -->

    <div v-if="sidebarOpen" class="overlay" @click="closeSidebar"></div>

    <!-- main -->
    <div class="main-area">
      <header class="top-bar">
        <h1 class="logo top-logo" @click="toggleSidebar">timetable</h1>
        <div class="top-actions">
          <router-link v-if="userType !== 'faculty'" :to="userType === 'faculty' ? '/faculty-account' : '/admin-account'" class="profile-wrap" aria-label="Account">
            <img :src="profilepic" alt="Profile" class="profile-icon" />
          </router-link>
          <button class="logout-btn" @click="logout">Logout</button>
        </div>
      </header>

      <div class="content-wrapper">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
          <div class="banner-content">
            <div class="welcome-section">
              <svg class="icon" width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
              </svg>
              <span class="welcome-text">Welcome, {{ currentUserName || 'User' }}!</span>
            </div>
            <div class="schedule-section">
              <svg class="icon calendar-icon" width="32" height="32" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <line x1="7" y1="2" x2="7" y2="6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <line x1="17" y1="2" x2="17" y2="6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="1"/>
                <circle cx="8" cy="13" r="2" fill="#4285f4"/>
                <circle cx="12" cy="13" r="2" fill="#ea4335"/>
                <circle cx="16" cy="13" r="2" fill="#fbbc04"/>
                <circle cx="8" cy="17" r="2" fill="#34a853"/>
                <circle cx="12" cy="17" r="2" fill="#ff6d01"/>
              </svg>
              <span class="schedule-label">Active Schedule:</span>
              <span class="schedule-value">{{ activeScheduleDisplay || 'No active schedule' }}</span>
            </div>
          </div>
        </div>

        <main :class="{ 'content-card': !isPanel }">
          <router-view />
        </main>
      </div>
    </div>
  </div>
  <ToastContainer />
</template>

<script>
import emitter from './eventBus';
import profilepic from './assets/profilepic.png';
import ToastContainer from './components/ToastContainer.vue';
import LoadingModal from './components/LoadingModal.vue';
import axios from './axios';

export default {
  name: 'App',
  components: { ToastContainer, LoadingModal },
  data() {
    return {
      profilepic,
      isAuthenticated: false,
      sidebarOpen: false,
      currentUserName: 'User',
      userType: null, // Add userType to data
      activeAcademicYear: '',
      activeSemester: '',
      scheduleUpdateInterval: null,
    };
  },
  computed: {
    isPanel() {
      const panelRoutes = ['/create', '/schedule/modify'];
      return panelRoutes.includes(this.$route.path);
    },
    activeScheduleDisplay() {
      if (this.activeAcademicYear && this.activeSemester) {
        return `${this.activeAcademicYear} - ${this.activeSemester}`;
      }
      return null;
    },
  },
  created() {
    const token = localStorage.getItem('authToken');
    this.isAuthenticated = token && token !== 'undefined' && token !== 'null';
    this.userType = localStorage.getItem('userType'); // Get user type
    this.fetchUserInfo();
    if (this.isAuthenticated) {
      this.fetchActiveScheduleInfo();
    }
  },
  watch: {
    $route() {
      const token = localStorage.getItem('authToken');
      this.isAuthenticated = token && token !== 'undefined' && token !== 'null';
      this.userType = localStorage.getItem('userType'); // refetch userType
      this.sidebarOpen = false;
      // Refresh active schedule when navigating
      if (this.isAuthenticated) {
        this.fetchActiveScheduleInfo();
      }
    },
    isAuthenticated(newVal) {
      if (newVal) {
        this.fetchUserInfo();
      }
    },
  },
  mounted() {
    // Listen for schedule update events
    emitter.on('schedule-updated', this.fetchActiveScheduleInfo);
    emitter.on('user-updated', this.fetchUserInfo);
  },
  beforeUnmount() {
    // Clean up listener
    emitter.off('schedule-updated', this.fetchActiveScheduleInfo);
    emitter.off('user-updated', this.fetchUserInfo);
  },
  methods: {
    fetchUserInfo() {
      try {
        const userName = localStorage.getItem('userName') || 'User';
        this.currentUserName = userName;
      } catch (err) {
        console.error('Failed to fetch user', err);
      }
    },
    async fetchActiveScheduleInfo() {
      try {
        const res = await fetch(`/api/active-schedule`);
        if (!res.ok) return;
        const data = await res.json();
        this.activeAcademicYear = data?.academicYear || '';
        this.activeSemester = data?.semester || '';
      } catch (e) {
        console.error('Failed to fetch active schedule', e);
      }
    },
    logout() {
      localStorage.removeItem('authToken');
      localStorage.removeItem('userName');
      localStorage.removeItem('userType'); // Clear user type
      this.isAuthenticated = false;
      this.userType = null; // Reset userType
      this.$router.push('/login').catch(() => {});
    },
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },
    closeSidebar() {
      this.sidebarOpen = false;
    },
  },
};
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

* {
  font-family: 'Roboto', sans-serif;
  box-sizing: border-box;
}

html,
body {
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  width: 100%;
}

/* login screen */
.login-full {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f7f7f7;
}

/* authenticated layout */
.app-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 24px;
  padding: 24px;
  min-height: 100vh;
  background: #f7f7f7;
  box-sizing: border-box;
  overflow-x: hidden;
}

/* sidebar */
.sidebar {
  background: #f2f2f2;
  border-radius: 24px;
  padding: 28px 18px;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 6px 18px rgba(0,0,0,0.04);
  height: calc(100vh - 48px);
  transition: transform 0.3s ease-in-out;
  z-index: 2100;
  max-width: 260px;
  width: 100%;
  position: -webkit-sticky; /* For Safari support */
  position: sticky;
  top: 24px; /* Adjust this value as needed to control where it sticks */
  align-self: start; /* Ensures it aligns to the start of the grid cell */
}

.side-logo {
  font-weight: 700;
  font-size: 22px;
  margin-bottom: 24px;
  text-align: center;
}

.side-nav-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.side-link {
  display: block;
  padding: 14px 12px;
  text-decoration: none;
  color: #333;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  transition: background .15s ease, color .15s ease;
}

.side-link:hover {
  background: #eaeaea;
}

.side-link.active {
  background: #ffffff;
  color: #111;
}

.sidebar .generate-btn,
.error-loog-btn {
  width: 100%;
  margin-top: 40px;
  padding: 0 18px; /* horizontal padding */
  height: 48px;    /* fixed height */
  border-radius: 18px;
  background: #000;
  color: #fff;
  border: none;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  text-decoration: none;
  text-align: center;
  display: flex;           /* use flex to center content */
  justify-content: center;
  align-items: center;
  font-size: 16px;
}


.error-loog-btn {
  margin-top: auto;
}

/* overlay */

.overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  z-index: 2000;
}

/* main */

.main-area {
  display: flex;
  flex-direction: column;
  gap: 24px;
  width: 100%;
  height: calc(100vh - 48px);
}

.content-wrapper {
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
  flex-grow: 1;
}

/* sidebar */
.sidebar {
  background: #f2f2f2;
  border-radius: 24px;
  padding: 28px 18px;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 6px 18px rgba(0,0,0,0.04);
  height: calc(100vh - 48px);
  transition: transform 0.3s ease-in-out;
  z-index: 2100;
  max-width: 260px;
  width: 100%;
  position: -webkit-sticky; /* For Safari support */
  position: sticky;
  top: 24px; /* Adjust this value as needed to control where it sticks */
  align-self: start; /* Ensures it aligns to the start of the grid cell */
}

.side-logo {
  font-weight: 700;
  font-size: 22px;
  margin-bottom: 24px;
  text-align: center;
}

.side-nav-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.side-link {
  display: block;
  padding: 14px 12px;
  text-decoration: none;
  color: #333;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  transition: background .15s ease, color .15s ease;
}

.side-link:hover {
  background: #eaeaea;
}

.side-link.active {
  background: #ffffff;
  color: #111;
}

.sidebar .generate-btn,
.error-loog-btn {
  width: 100%;
  margin-top: 40px;
  padding: 0 18px; /* horizontal padding */
  height: 48px;    /* fixed height */
  border-radius: 18px;
  background: #000;
  color: #fff;
  border: none;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  text-decoration: none;
  text-align: center;
  display: flex;           /* use flex to center content */
  justify-content: center;
  align-items: center;
  font-size: 16px;
}


.error-loog-btn {
  margin-top: auto;
}

/* overlay */

.overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  z-index: 2000;
}

/* main */

.main-area {
  display: flex;
  flex-direction: column;
  gap: 18px;
  width: 100%;
  max-width: 100%;
  overflow-y: auto;
}

.top-bar {
  display: flex;
  align-items: center;
  padding: 6px 12px;
  background: transparent;
  position: sticky;
  top: 0;
  z-index: 1000;
  width: 100%;
  box-sizing: border-box;
}

.top-logo {
  font-weight: 700;
  font-size: 20px;
  letter-spacing: -0.5px;
  cursor: pointer;
  display: none;
  margin: 0;
  flex-shrink: 0;
}

.top-actions {
  margin-left: auto;
  display: flex;
  gap: 12px;
  align-items: center;
}

.profile-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  cursor: pointer;
  border: 2px solid rgba(0,0,0,0.05);
  background: #fff;
}

.logout-btn {
  padding: 8px 14px;
  border-radius: 12px;
  border: none;
  background: #dc3545;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

/* Welcome Banner Styles */
.welcome-banner {
  background: transparent;
  padding: 16px 0;
  margin-bottom: 0;
  width: 100%;
  box-sizing: border-box;
}

.banner-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 32px;
  flex-wrap: wrap;
}

.welcome-section, .schedule-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.welcome-section .icon {
  color: #6BC4E2;
  flex-shrink: 0;
}

.schedule-section .icon {
  color: #7B8FC1;
  flex-shrink: 0;
}

.welcome-text {
  font-size: 26px;
  font-weight: 700;
  color: #333;
}

.schedule-label {
  font-size: 16px;
  color: #666;
  font-weight: 500;
}

.schedule-value {
  font-size: 18px;
  font-weight: 700;
  color: #000;
}

@media (max-width: 768px) {
  .welcome-banner {
    padding: 12px 0;
  }
  
  .banner-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  
  .icon {
    width: 24px;
    height: 24px;
  }
  
  .welcome-text {
    font-size: 22px;
  }
  
  .schedule-label {
    font-size: 14px;
  }
  
  .schedule-value {
    font-size: 16px;
  }
}

.content-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 22px;
  box-shadow: 0 10px 26px rgba(0,0,0,0.04);
  overflow: auto;
  min-height: calc(100vh - 160px);
  width: 100%;
  max-width: 100%;
  overflow-x: auto; /* Allow horizontal scroll only if content overflows, but prevent body scroll */
}

/* min screen */

@media (max-width: 900px) {
  .app-layout {
    grid-template-columns: 1fr;
    padding: 16px;
    gap: 0;
  }

  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 240px;
    max-width: 80vw; /* Prevent sidebar from being too wide on very small screens */
    height: 100vh;
    transform: translateX(-100%);
    padding: 28px 18px;
    box-sizing: border-box;
  }

  .sidebar.open {
    transform: translateX(0);
  }

  .top-logo {
    display: block;
  }

  .side-logo {
    display: none;
  }

  .main-area {
    padding-left: 0;
    padding-right: 0;
  }

  .top-bar {
    padding: 12px 16px;
  }

  .content-card {
    padding: 16px;
    margin: 0;
    border-radius: 16px;
    min-height: calc(100vh - 120px);
  }

  /* Ensure no horizontal overflow on mobile */
  .app-layout,
  .main-area,
  .content-card {
    overflow-x: hidden;
  }
}

/* Extra small screens */
@media (max-width: 480px) {
  .app-layout {
    padding: 8px;
  }

  .top-bar {
    padding: 8px 12px;
  }

  .content-card {
    padding: 12px;
  }

  .sidebar {
    width: 280px;
    max-width: 90vw;
  }

  .top-actions {
    gap: 8px;
  }

  .profile-icon {
    width: 36px;
    height: 36px;
  }

  .logout-btn {
    padding: 6px 10px;
    font-size: 14px;
  }
}
</style>