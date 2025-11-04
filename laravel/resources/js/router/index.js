import { createRouter, createWebHistory } from 'vue-router';
import Login from '../pages/views/Login.vue';
import Schedule from '../pages/views/admin/Schedule.vue';
import Create from '../pages/views/admin/Create.vue';
import Export from '../pages/views/admin/Export.vue';
import AdminAccount from '../pages/views/admin/AdminAccount.vue';
import ErrorLog from '../pages/views/admin/ErrorLog.vue';
import ModifySchedule from '../pages/views/admin/ModifySchedule.vue';
import Manage from '../pages/views/admin/Manage.vue';
import Dashboard from '../pages/views/admin/Dashboard.vue';
import FacultyDashboard from '../pages/views/faculty/Dashboard.vue';
import FacultyAccount from '../pages/views/faculty/FacultyAccount.vue';

const routes = [
  { path: '/login', component: Login },
  { path: '/schedule', component: Schedule },
  { path: '/create', component: Create },
  { path: '/export', component: Export },
  { path: '/admin-account', component: AdminAccount },
  { path: '/error-log', component: ErrorLog },
  { path: '/schedule/modify', component: ModifySchedule },
  { path: '/manage', component: Manage },
  { path: '/dashboard', component: Dashboard },
  { path: '/faculty/dashboard', component: FacultyDashboard },
  { path: '/faculty-account', component: FacultyAccount },
  { path: '/', redirect: '/dashboard' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Route guard
router.beforeEach((to, from, next) => {
  const publicPages = ['/login'];
  const authRequired = !publicPages.includes(to.path);
  const isAuthenticated = !!localStorage.getItem('authToken');

  if (authRequired && !isAuthenticated) {
    return next('/login');
  }
  next();
});

export default router;