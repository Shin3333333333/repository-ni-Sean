import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Schedule from '../views/Schedule.vue';
import Create from '../views/Create.vue';
import Export from '../views/Export.vue';
import AdminAccount from '../views/AdminAccount.vue';
import ErrorLog from '../views/ErrorLog.vue';
import ModifySchedule from '../views/ModifySchedule.vue';
import Manage from '../views/Manage.vue';
import Dashboard from '../views/Dashboard.vue';

const routes = [
  { path: '/login', component: Login },
  { path: '/', redirect: '/schedule' },
  { path: '/schedule', component: Schedule },
  { path: '/create', component: Create },
  { path: '/export', component: Export },
  { path: '/admin-account', component: AdminAccount },
  { path: '/error-log', component: ErrorLog },
  { path: '/schedule/modify', component: ModifySchedule },
  { path: '/manage', component: Manage },
  { path: '/dashboard', component: Dashboard },
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