<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import './RegisterView.scss'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''

  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match'
    return
  }

  loading.value = true

  try {
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push('/')
  } catch (e: any) {
    error.value = e.message || 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="register__container">
    <Card class="register__card">
      <CardHeader>
        <CardTitle class="register__title">Create an account</CardTitle>
        <CardDescription class="register__subtitle">
          Join EventManager to create and join events
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form class="register__form" @submit.prevent="handleRegister">
          <div class="register__field">
            <Label for="name">Name</Label>
            <Input id="name" v-model="name" type="text" placeholder="John Doe" required />
          </div>

          <div class="register__field">
            <Label for="email">Email</Label>
            <Input id="email" v-model="email" type="email" placeholder="you@example.com" required />
          </div>

          <div class="register__field">
            <Label for="password">Password</Label>
            <Input id="password" v-model="password" type="password" placeholder="••••••••" required />
          </div>

          <div class="register__field">
            <Label for="password-confirm">Confirm Password</Label>
            <Input id="password-confirm" v-model="passwordConfirmation" type="password" placeholder="••••••••" required />
          </div>

          <p v-if="error" class="register__error">{{ error }}</p>

          <Button type="submit" class="register__submit" :disabled="loading">
            {{ loading ? 'Creating account...' : 'Create account' }}
          </Button>

          <p class="register__login">
            Already have an account? <RouterLink to="/login">Sign in</RouterLink>
          </p>
        </form>
      </CardContent>
    </Card>
  </div>
</template>