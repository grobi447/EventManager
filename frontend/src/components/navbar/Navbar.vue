<script setup lang="ts">
import { useAuthStore } from '@/stores/auth.store'
import { useRouter, useRoute } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import './Navbar.scss'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

function isActive(path: string) {
  return route.path === path
}

async function handleLogout() {
  try {
    await authStore.logout()
  } catch {
    authStore.clearAuth()
  }
  router.push('/login')
}

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<template>
  <nav class="navbar">
    <div class="navbar__inner">
      <RouterLink to="/" class="navbar__brand">EventManager</RouterLink>

      <div class="navbar__links">
        <RouterLink
          to="/"
          class="navbar__link"
          :class="{ 'navbar__link--active': isActive('/') }"
        >
          Home
        </RouterLink>

        <template v-if="authStore.isAuthenticated">
          <RouterLink
            to="/my-events"
            class="navbar__link"
            :class="{ 'navbar__link--active': isActive('/my-events') }"
          >
            My Events
          </RouterLink>

          <RouterLink
            to="/joined-events"
            class="navbar__link"
            :class="{ 'navbar__link--active': isActive('/joined-events') }"
          >
            Joined Events
          </RouterLink>

          <RouterLink
            to="/helpdesk"
            class="navbar__link"
            :class="{ 'navbar__link--active': isActive('/helpdesk') }"
          >
            {{ authStore.isAgent ? 'Agent Panel' : 'Support' }}
          </RouterLink>
        </template>
      </div>

      <div class="navbar__actions">
        <template v-if="!authStore.isAuthenticated">
          <RouterLink to="/login">
            <Button variant="ghost" size="sm">Sign in</Button>
          </RouterLink>
          <RouterLink to="/register">
            <Button size="sm">Register</Button>
          </RouterLink>
        </template>

        <template v-else>
          <DropdownMenu>
            <DropdownMenuTrigger>
              <Avatar class="cursor-pointer">
                <AvatarFallback>
                  {{ getInitials(authStore.user?.name || 'U') }}
                </AvatarFallback>
              </Avatar>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <div class="px-2 py-1.5">
                <p class="text-sm font-medium">{{ authStore.user?.name }}</p>
                <p class="text-xs text-muted-foreground">{{ authStore.user?.email }}</p>
              </div>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="router.push('/settings')">
                Settings
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="handleLogout" class="text-destructive">
                Sign out
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </template>
      </div>
    </div>
  </nav>
</template>