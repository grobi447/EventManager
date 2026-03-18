<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { http } from '@/api/http'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import './LoginView.scss'

const router = useRouter()
const route = useRoute()

const token = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const success = ref('')
const loading = ref(false)

onMounted(() => {
  token.value = route.query.token as string || ''
  email.value = route.query.email as string || ''
})

async function handleReset() {
  error.value = ''
  success.value = ''

  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match'
    return
  }

  loading.value = true
  try {
    await http.post('/auth/reset-password', {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    success.value = 'Password reset successfully. Redirecting to login...'
    setTimeout(() => router.push('/login'), 2000)
  } catch (e: any) {
    error.value = e.message || 'Failed to reset password'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login__container">
    <Card class="login__card">
      <CardHeader>
        <CardTitle class="login__title">Reset Password</CardTitle>
        <CardDescription class="login__subtitle">Enter your new password</CardDescription>
      </CardHeader>
      <CardContent>
        <form class="login__form" @submit.prevent="handleReset">
          <div class="login__field">
            <Label for="password">New Password</Label>
            <Input id="password" v-model="password" type="password" placeholder="••••••••" required />
          </div>
          <div class="login__field">
            <Label for="confirm">Confirm Password</Label>
            <Input id="confirm" v-model="passwordConfirmation" type="password" placeholder="••••••••" required />
          </div>
          <p v-if="error" class="login__error">{{ error }}</p>
          <p v-if="success" class="settings-form__success">{{ success }}</p>
          <Button type="submit" class="login__submit" :disabled="loading">
            {{ loading ? 'Resetting...' : 'Reset Password' }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </div>
</template>