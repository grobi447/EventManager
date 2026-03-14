import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import HomeView from '@/views/home/HomeView.vue'
import MyEventsView from '@/views/events/MyEventsView.vue'
import JoinedEventsView from '@/views/events/JoinedEventsView.vue'
import HelpdeskView from '@/views/helpdesk/HelpdeskView.vue'
import SettingsView from '@/views/settings/SettingsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/login', name: 'login', component: LoginView, meta: { guest: true } },
    { path: '/register', name: 'register', component: RegisterView, meta: { guest: true } },
    { path: '/my-events', name: 'my-events', component: MyEventsView, meta: { requiresAuth: true } },
    { path: '/joined-events', name: 'joined-events', component: JoinedEventsView, meta: { requiresAuth: true } },
    { path: '/helpdesk', name: 'helpdesk', component: HelpdeskView, meta: { requiresAuth: true } },
    { path: '/settings', name: 'settings', component: SettingsView, meta: { requiresAuth: true } },
  ],
})

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router