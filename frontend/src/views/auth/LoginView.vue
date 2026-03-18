<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { http } from '@/api/http'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import './LoginView.scss'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const otp = ref('')
const error = ref('')
const loading = ref(false)
const showMfa = ref(false)

const showForgotDialog = ref(false)
const forgotEmail = ref('')
const forgotError = ref('')
const forgotSuccess = ref('')
const forgotLoading = ref(false)

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
    await authStore.loginWithMfa({ email: email.value, password: password.value, otp: otp.value })
    redirectAfterLogin()
  } catch (e: any) {
    error.value = e.message || 'Invalid OTP'
  } finally {
    loading.value = false
  }
}

async function handleForgotPassword() {
  forgotError.value = ''
  forgotSuccess.value = ''
  forgotLoading.value = true
  try {
    await http.post('/auth/forgot-password', { email: forgotEmail.value })
    forgotSuccess.value = 'Password reset link sent to your email.'
    forgotEmail.value = ''
  } catch (e: any) {
    forgotError.value = e.message || 'Failed to send reset link'
  } finally {
    forgotLoading.value = false
  }
}

function redirectAfterLogin() {
  if (authStore.isAgent) {
    router.push('/helpdesk')
  } else {
    router.push('/')
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
              <Input id="email" v-model="email" type="email" placeholder="you@example.com" required />
            </div>
            <div class="login__field">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <Label for="password">Password</Label>
                <button
                  type="button"
                  class="login__forgot"
                  @click="showForgotDialog = true"
                >
                  Forgot password?
                </button>
              </div>
              <Input id="password" v-model="password" type="password" placeholder="••••••••" required />
            </div>
          </template>

          <template v-else>
            <p class="login__mfa-info">Open your authenticator app and enter the 6-digit code.</p>
            <div class="login__field">
              <Label for="otp">Authentication Code</Label>
              <Input id="otp" v-model="otp" type="text" placeholder="000000" maxlength="6" required />
            </div>
          </template>

          <p v-if="error" class="login__error">{{ error }}</p>

          <Button type="submit" class="login__submit" :disabled="loading">
            {{ loading ? 'Signing in...' : showMfa ? 'Verify' : 'Sign in' }}
          </Button>
        </form>
      </CardContent>
    </Card>

    <Dialog v-model:open="showForgotDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reset Password</DialogTitle>
        </DialogHeader>
        <div class="login__form">
          <div class="login__field">
            <Label for="forgot-email">Email</Label>
            <Input id="forgot-email" v-model="forgotEmail" type="email" placeholder="you@example.com" />
          </div>
          <p v-if="forgotError" class="login__error">{{ forgotError }}</p>
          <p v-if="forgotSuccess" style="color: #4ade80; font-size: 0.875rem;">{{ forgotSuccess }}</p>
          <Button @click="handleForgotPassword" :disabled="forgotLoading" class="login__submit">
            {{ forgotLoading ? 'Sending...' : 'Send Reset Link' }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>