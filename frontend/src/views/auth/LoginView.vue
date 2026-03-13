<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import './LoginView.scss'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const otp = ref('')
const error = ref('')
const loading = ref(false)
const showMfa = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true

  try {
    const result = await authStore.login({ email: email.value, password: password.value })

    if (result.mfa_required) {
      showMfa.value = true
    } else {
      redirectAfterLogin()
    }
  } catch (e: any) {
    error.value = e.message || 'Invalid credentials'
  } finally {
    loading.value = false
  }
}

async function handleMfaLogin() {
  error.value = ''
  loading.value = true

  try {
    await authStore.loginWithMfa({
      email: email.value,
      password: password.value,
      otp: otp.value,
    })
    redirectAfterLogin()
  } catch (e: any) {
    error.value = e.message || 'Invalid OTP'
  } finally {
    loading.value = false
  }
}

function redirectAfterLogin() {
  if (authStore.isAgent) {
    router.push('/helpdesk')
  } else {
    router.push('/events')
  }
}
</script>

<template>
  <div class="login__container">
    <Card class="login__card">
      <CardHeader>
        <CardTitle class="login__title">EventManager</CardTitle>
        <CardDescription class="login__subtitle">
          {{ showMfa ? 'Enter your authentication code' : 'Sign in to your account' }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form class="login__form" @submit.prevent="showMfa ? handleMfaLogin() : handleLogin()">

          <template v-if="!showMfa">
            <div class="login__field">
              <Label for="email">Email</Label>
              <Input
                id="email"
                v-model="email"
                type="email"
                placeholder="you@example.com"
                required
              />
            </div>

            <div class="login__field">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <Label for="password">Password</Label>
                <a href="/forgot-password" class="login__forgot">Forgot password?</a>
              </div>
              <Input
                id="password"
                v-model="password"
                type="password"
                placeholder="••••••••"
                required
              />
            </div>
          </template>

          <template v-else>
            <p class="login__mfa-info">
              Open your authenticator app and enter the 6-digit code.
            </p>
            <div class="login__field">
              <Label for="otp">Authentication Code</Label>
              <Input
                id="otp"
                v-model="otp"
                type="text"
                placeholder="000000"
                maxlength="6"
                required
              />
            </div>
          </template>

          <p v-if="error" class="login__error">{{ error }}</p>

          <Button type="submit" class="login__submit" :disabled="loading">
            {{ loading ? 'Signing in...' : showMfa ? 'Verify' : 'Sign in' }}
          </Button>

        </form>
      </CardContent>
    </Card>
  </div>
</template>