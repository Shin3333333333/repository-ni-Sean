<template>

  <!-- unauthenticated -->

  <div v-if="!isAuthenticated" class="login-full">
    <router-view />
  </div>

  <!-- authenticated -->
  <div v-else class="app-layout">

    <!-- sidebar -->
    <aside class="sidebar" :class="{ open: sidebarOpen }">

      <h1 class="logo side-logo">timetable</h1>
      <nav class="side-nav-list">
        <router-link to="/dashboard" class="side-link" active-class="active">Dashboard</router-link>
        <router-link to="/manage" class="side-link" active-class="active">Manage</router-link>
        <router-link to="/schedule" class="side-link" active-class="active">View Schedule</router-link>
        </nav>

        <router-link to="/error-log" class="error-loog-btn" active-class="active">Error Log</router-link>
        <router-link to="/create" class="generate-btn" active-class="active">Generate</router-link>
      
    </aside>

    <!-- for mobile -->

    <div v-if="sidebarOpen" class="overlay" @click="closeSidebar"></div>

    <!-- main -->

    <div class="main-area">
      <header class="top-bar">

        <h1 class="logo top-logo" @click="toggleSidebar">timetable</h1>

        <div class="top-actions">
          <router-link to="/admin-account" class="profile-wrap" aria-label="Admin account">
            <img :src="profilepic" alt="Profile" class="profile-icon" />
          </router-link>
          <button class="logout-btn" @click="logout">Logout</button>
        </div>
      </header>

      <main class="content-card">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import profilepic from './assets/profilepic.png';

export default {
  name: 'App',
  data() {
    return {
      profilepic,
      isAuthenticated: false,
      sidebarOpen: false,
    };
  },
  created() {
    this.isAuthenticated = localStorage.getItem('timetableAuth') === 'true';
  },
  watch: {
    $route() {
      this.isAuthenticated = localStorage.getItem('timetableAuth') === 'true';
      this.sidebarOpen = false;
    },
  },
  methods: {
    logout() {
      localStorage.removeItem('timetableAuth');
      this.isAuthenticated = false;
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

.generate-btn,
.error-loog-btn {
  width: 100%;
  margin-top: 40px;
  padding: 18px;
  border-radius: 18px;
  background: #000;
  color: #fff;
  border: none;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  text-decoration: none;
  text-align: center;
  display: block;
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
}

.top-bar {
  display: flex;
  align-items: center;
  padding: 6px 12px;
  background: transparent;
  position: relative;
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