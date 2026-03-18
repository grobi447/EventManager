<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { http } from '@/api/http'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import './SettingsView.scss'

const authStore = useAuthStore()

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const passwordError = ref('')
const passwordSuccess = ref('')
const passwordLoading = ref(false)

const mfaEnabled = computed(() => authStore.user?.mfa_enabled || false)
const mfaSetupData = ref<{ secret: string } | null>(null)
const mfaOtp = ref('')
const mfaDisableOtp = ref('')
const mfaError = ref('')
const mfaSuccess = ref('')
const mfaLoading = ref(false)
const backupCodes = ref<string[]>([])
const showDisableForm = ref(false)

async function handlePasswordChange() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (newPassword.value !== confirmPassword.value) {
    passwordError.value = 'Passwords do not match'
    return
  }

  passwordLoading.value = true
  try {
    await http.post('/auth/change-password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    passwordSuccess.value = 'Password changed successfully'
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (e: any) {
    passwordError.value = e.message || 'Failed to change password'
  } finally {
    passwordLoading.value = false
  }
}

async function handleMfaToggle() {
  if (mfaEnabled.value) {
    showDisableForm.value = true
  } else {
    mfaLoading.value = true
    mfaError.value = ''
    try {
      const response = await http.get<any>('/mfa/setup')
      mfaSetupData.value = response.data
    } catch (e: any) {
      mfaError.value = e.message || 'Failed to setup MFA'
    } finally {
      mfaLoading.value = false
    }
  }
}

async function handleMfaEnable() {
  mfaError.value = ''
  mfaLoading.value = true
  try {
    const response = await http.post<any>('/mfa/enable', {
      secret: mfaSetupData.value?.secret,
      otp: mfaOtp.value,
    })
    backupCodes.value = response.data.backup_codes
    mfaSetupData.value = null
    mfaOtp.value = ''
    mfaSuccess.value = 'MFA enabled successfully'
    if (authStore.user) authStore.user.mfa_enabled = true
  } catch (e: any) {
    mfaError.value = e.message || 'Invalid OTP'
  } finally {
    mfaLoading.value = false
  }
}

async function handleMfaDisable() {
  mfaError.value = ''
  mfaLoading.value = true
  try {
    await http.post<any>('/mfa/disable', { otp: mfaDisableOtp.value })
    mfaSuccess.value = 'MFA disabled successfully'
    showDisableForm.value = false
    mfaDisableOtp.value = ''
    if (authStore.user) authStore.user.mfa_enabled = false
  } catch (e: any) {
    mfaError.value = e.message || 'Invalid OTP'
  } finally {
    mfaLoading.value = false
  }
}
</script>

<template>
  <div class="settings">
    <h1 class="settings__title">Settings</h1>
    <p class="settings__subtitle">Manage your account security</p>

    <div class="settings__section">
      <h2 class="settings__section-title">Change Password</h2>
      <Card>
        <CardContent class="pt-6">
          <form @submit.prevent="handlePasswordChange">
            <div class="settings-form__field">
              <Label for="current-password">Current Password</Label>
              <Input id="current-password" v-model="currentPassword" type="password" required />
            </div>
            <div class="settings-form__field">
              <Label for="new-password">New Password</Label>
              <Input id="new-password" v-model="newPassword" type="password" required />
            </div>
            <div class="settings-form__field">
              <Label for="confirm-password">Confirm New Password</Label>
              <Input id="confirm-password" v-model="confirmPassword" type="password" required />
            </div>
            <p v-if="passwordError" class="settings-form__error">{{ passwordError }}</p>
            <p v-if="passwordSuccess" class="settings-form__success">{{ passwordSuccess }}</p>
            <div class="settings-form__footer">
              <Button type="submit" :disabled="passwordLoading">
                {{ passwordLoading ? 'Saving...' : 'Change Password' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <div class="settings__section">
      <h2 class="settings__section-title">Two-Factor Authentication</h2>
      <Card>
        <CardContent class="pt-6">
          <div class="settings-form__mfa-row">
            <div class="settings-form__mfa-info">
              <span class="settings-form__mfa-label">Authenticator App</span>
              <span class="settings-form__mfa-desc">
                {{ mfaEnabled ? 'MFA is currently enabled' : 'Add extra security to your account' }}
              </span>
            </div>
            <Switch :checked="mfaEnabled" @update:checked="handleMfaToggle" />
          </div>

          <div v-if="mfaSetupData" class="settings-form__mfa-setup">
            <p class="text-sm text-muted-foreground">
              Enter this secret key in your authenticator app (Google Authenticator, Authy):
            </p>
            <div class="settings-form__secret">{{ mfaSetupData.secret }}</div>
            <div class="settings-form__field">
              <Label for="mfa-otp">Enter the 6-digit code to confirm</Label>
              <Input id="mfa-otp" v-model="mfaOtp" placeholder="000000" maxlength="6" />
            </div>
            <p v-if="mfaError" class="settings-form__error">{{ mfaError }}</p>
            <div class="settings-form__footer">
              <Button @click="handleMfaEnable" :disabled="mfaLoading">
                {{ mfaLoading ? 'Verifying...' : 'Enable MFA' }}
              </Button>
            </div>
          </div>

          <div v-if="backupCodes.length > 0" class="settings-form__mfa-setup">
            <p class="text-sm font-medium">Save these backup codes in a safe place:</p>
            <div class="settings-form__backup-codes">
              <div v-for="code in backupCodes" :key="code" class="settings-form__backup-code">
                {{ code }}
              </div>
            </div>
          </div>

          <div v-if="showDisableForm" class="settings-form__mfa-setup">
            <div class="settings-form__field">
              <Label for="disable-otp">Enter your 6-digit code to disable MFA</Label>
              <Input id="disable-otp" v-model="mfaDisableOtp" placeholder="000000" maxlength="6" />
            </div>
            <p v-if="mfaError" class="settings-form__error">{{ mfaError }}</p>
            <div class="settings-form__footer">
              <Button variant="outline" @click="showDisableForm = false">Cancel</Button>
              <Button variant="destructive" @click="handleMfaDisable" :disabled="mfaLoading">
                {{ mfaLoading ? 'Disabling...' : 'Disable MFA' }}
              </Button>
            </div>
          </div>

          <p v-if="mfaSuccess" class="settings-form__success mt-2">{{ mfaSuccess }}</p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>