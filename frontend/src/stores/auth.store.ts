import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { http } from '@/api/http'
import type { AuthUser, LoginCredentials, MfaCredentials } from '@/types/auth.types'

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string | null>(localStorage.getItem('token'))
    const user = ref<AuthUser | null>(JSON.parse(localStorage.getItem('user') || 'null'))
    const mfaPending = ref<string | null>(null)

    const isAuthenticated = computed(() => !!token.value)
    const isAgent = computed(() => user.value?.role === 'helpdesk_agent')

    async function login(credentials: LoginCredentials) {
        const response = await http.post<any>('/auth/login', credentials)

        if (response.mfa_required) {
            mfaPending.value = response.email
            return { mfa_required: true }
        }

        setAuth(response.data.access_token, response.data.user)
        return { mfa_required: false }
    }

    async function loginWithMfa(credentials: MfaCredentials) {
        const response = await http.post<any>('/auth/login-mfa', credentials)
        setAuth(response.data.access_token, response.data.user)
    }

    async function logout() {
        await http.post('/auth/logout', {})
        clearAuth()
    }

    function setAuth(accessToken: string, authUser: AuthUser) {
        token.value = accessToken
        user.value = authUser
        localStorage.setItem('token', accessToken)
        localStorage.setItem('user', JSON.stringify(authUser))
    }

    function clearAuth() {
        token.value = null
        user.value = null
        mfaPending.value = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
    }

    return { token, user, mfaPending, isAuthenticated, isAgent, login, loginWithMfa, logout, clearAuth }
})