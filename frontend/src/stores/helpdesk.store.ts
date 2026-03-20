import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/api/http'
import type { Chat, Message } from '@/types/helpdesk.types'

export const useHelpdeskStore = defineStore('helpdesk', () => {
    const currentChat = ref<Chat | null>(null)
    const messages = ref<Message[]>([])
    const agentChats = ref<Chat[]>([])
    const selectedChat = ref<Chat | null>(null)
    const selectedChatMessages = ref<Message[]>([])

    async function startChat() {
        const response = await http.post<any>('/helpdesk/chats', {})
        currentChat.value = response.data
        messages.value = []
        return response.data
    }

    async function sendMessageRaw(content: string) {
        if (!currentChat.value) return
        const response = await http.post<any>(
            `/helpdesk/chats/${currentChat.value.id}/messages`,
            { content }
        )
        if (response.data.transferred && currentChat.value) {
            currentChat.value.status = 'pending'
        }
        return response.data
    }

    async function transferToAgent() {
        if (!currentChat.value) return
        const response = await http.post<any>(
            `/helpdesk/chats/${currentChat.value.id}/transfer`,
            {}
        )
        currentChat.value = response.data
    }

    async function fetchMyChats() {
        const response = await http.get<any>('/helpdesk/chats/my')
        return response.data
    }

    async function fetchAgentChats() {
        const response = await http.get<any>('/helpdesk/chats')
        agentChats.value = response.data
    }

    async function fetchChatMessages(chatId: number) {
        const response = await http.get<any>(`/helpdesk/chats/${chatId}/messages`)
        selectedChatMessages.value = response.data
        return response.data
    }

    async function agentRespond(chatId: number, content: string) {
        const response = await http.post<any>(
            `/helpdesk/chats/${chatId}/respond`,
            { content }
        )
        return response.data
    }

    async function deleteChat(chatId: number) {
        await http.delete(`/helpdesk/chats/${chatId}`)
    }

    async function loadMessages(chatId: number) {
        const response = await http.get<any>(`/helpdesk/chats/${chatId}/messages`)
        messages.value = response.data
    }

    async function refreshChatStatus(chatId: number) {
        const response = await http.get<any>('/helpdesk/chats/my')
        const chat = response.data.find((c: any) => c.id === chatId)
        if (chat && currentChat.value) {
            currentChat.value.status = chat.status
            currentChat.value.agent_id = chat.agent_id
        }
    }

    return {
        currentChat, messages, agentChats, selectedChat, selectedChatMessages,
        startChat, sendMessageRaw, transferToAgent, fetchMyChats,
        fetchAgentChats, fetchChatMessages, agentRespond, deleteChat,
        loadMessages, refreshChatStatus,
    }
})