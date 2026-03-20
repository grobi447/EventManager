export interface Message {
    id: number
    chat_id: number
    sender_type: 'user' | 'ai' | 'agent'
    sender_id: number | null
    content: string
    created_at: string
}

export interface Chat {
    id: number
    user_id: number
    agent_id: number | null
    status: 'ai' | 'pending' | 'active' | 'closed'
    created_at: string
    updated_at: string
    user?: { id: number; name: string; email: string }
    messages?: Message[]
}