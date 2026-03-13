export interface LoginCredentials {
    email: string
    password: string
}

export interface MfaCredentials extends LoginCredentials {
    otp: string
}

export interface AuthUser {
    id: number
    name: string
    email: string
    role: 'user' | 'helpdesk_agent'
    mfa_enabled: boolean
}

export interface AuthResponse {
    success: boolean
    data?: {
        access_token: string
        token_type: string
        expires_in: number
        user: AuthUser
    }
    mfa_required?: boolean
    email?: string
    message: string
}