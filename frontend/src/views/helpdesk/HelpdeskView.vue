<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useHelpdeskStore } from '@/stores/helpdesk.store'
import { Button } from '@/components/ui/button'
import type { Message } from '@/types/helpdesk.types'
import './HelpdeskView.scss'

const authStore = useAuthStore()
const helpdeskStore = useHelpdeskStore()

const messageInput = ref('')
const agentInput = ref('')
const chatBox = ref<HTMLElement | null>(null)
const agentMessagesBox = ref<HTMLElement | null>(null)
const myChats = ref<any[]>([])
const isThinking = ref(false)
const chatTitle = ref('')
const isRecording = ref(false)
let pollInterval: ReturnType<typeof setInterval> | null = null
let recognition: any = null

watch(
  () => helpdeskStore.messages.length,
  async () => {
    await nextTick()
    if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight
  }
)

watch(
  () => helpdeskStore.selectedChatMessages.length,
  async () => {
    await nextTick()
    if (agentMessagesBox.value) agentMessagesBox.value.scrollTop = agentMessagesBox.value.scrollHeight
  }
)

function setupVoice() {
  const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
  if (!SpeechRecognition) return
  recognition = new SpeechRecognition()
  recognition.lang = 'en-EN'
  recognition.continuous = false
  recognition.interimResults = false
  recognition.onresult = (event: any) => {
    messageInput.value = event.results[0][0].transcript
  }
  recognition.onend = () => { isRecording.value = false }
  recognition.onerror = () => { isRecording.value = false }
}

function toggleVoice() {
  if (!recognition) return
  if (isRecording.value) {
    recognition.stop()
  } else {
    recognition.start()
    isRecording.value = true
  }
}

const voiceSupported = !!(
  (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
)

onMounted(async () => {
  setupVoice()
  if (authStore.isAgent) {
    await helpdeskStore.fetchAgentChats()
    startAgentPolling()
  } else {
    const chats = await helpdeskStore.fetchMyChats()
    myChats.value = chats
  }
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
  if (recognition) recognition.stop()
})

function startAgentPolling() {
  if (pollInterval) clearInterval(pollInterval)
  pollInterval = setInterval(async () => {
    await helpdeskStore.fetchAgentChats()
    if (helpdeskStore.selectedChat) {
      await helpdeskStore.fetchChatMessages(helpdeskStore.selectedChat.id)
    }
  }, 3000)
}

function startUserPolling() {
  if (pollInterval) clearInterval(pollInterval)
  pollInterval = setInterval(async () => {
    if (isThinking.value || !helpdeskStore.currentChat) return
    if (helpdeskStore.currentChat.status === 'closed') return
    await helpdeskStore.refreshChatStatus(helpdeskStore.currentChat.id)
    if (
      helpdeskStore.currentChat.status === 'active' ||
      helpdeskStore.currentChat.status === 'pending'
    ) {
      await helpdeskStore.loadMessages(helpdeskStore.currentChat.id)
    }
  }, 3000)
}

async function handleStartNewChat() {
  if (pollInterval) clearInterval(pollInterval)
  await helpdeskStore.startChat()
  chatTitle.value = 'New Conversation'
  myChats.value.unshift({ ...helpdeskStore.currentChat, messages: [] })
  startUserPolling()
}

async function handleSelectChat(chat: any) {
  helpdeskStore.currentChat = chat
  await helpdeskStore.loadMessages(chat.id)
  chatTitle.value = getChatTitle(chat)
  startUserPolling()
}

async function handleSendMessage() {
  if (!messageInput.value.trim() || isThinking.value) return
  const content = messageInput.value
  messageInput.value = ''

  if (helpdeskStore.messages.length === 0) {
    chatTitle.value = content.slice(0, 30)
    const sidebarChat = myChats.value.find((c: any) => c.id === helpdeskStore.currentChat?.id)
    if (sidebarChat) sidebarChat.messages = [{ content }]
  }

  const tempId = Date.now()
  helpdeskStore.messages.push({
    id: tempId,
    chat_id: helpdeskStore.currentChat?.id ?? 0,
    sender_type: 'user' as const,
    sender_id: null,
    content,
    created_at: new Date().toISOString(),
  })

  isThinking.value = true
  if (pollInterval) clearInterval(pollInterval)

  try {
    const response = await helpdeskStore.sendMessageRaw(content)

    const idx = helpdeskStore.messages.findIndex(m => m.id === tempId)
    if (idx !== -1 && response?.user_message) {
      helpdeskStore.messages[idx] = response.user_message
    }
    if (response?.ai_message) {
      helpdeskStore.messages.push(response.ai_message)
    }
    if (response?.transferred) {
      helpdeskStore.currentChat!.status = 'pending'
      const chat = myChats.value.find((c: any) => c.id === helpdeskStore.currentChat!.id)
      if (chat) chat.status = 'pending'
    }
  } finally {
    isThinking.value = false
    startUserPolling()
  }
}

async function handleTransfer() {
  helpdeskStore.messages.push({
    id: Date.now(),
    chat_id: helpdeskStore.currentChat?.id ?? 0,
    sender_type: 'ai' as const,
    sender_id: null,
    content: 'Your request has been transferred to a human agent. Please wait.',
    created_at: new Date().toISOString(),
  })
  helpdeskStore.currentChat!.status = 'pending'
  const sidebarChat = myChats.value.find((c: any) => c.id === helpdeskStore.currentChat?.id)
  if (sidebarChat) sidebarChat.status = 'pending'
  await helpdeskStore.transferToAgent()
}

async function handleDeleteChat(chatId: number) {
  await helpdeskStore.deleteChat(chatId)
  myChats.value = myChats.value.filter((c: any) => c.id !== chatId)
  if (helpdeskStore.currentChat?.id === chatId) {
    helpdeskStore.currentChat = null
    helpdeskStore.messages = []
    chatTitle.value = ''
    if (pollInterval) clearInterval(pollInterval)
  }
}

async function handleAgentSelectChat(chat: any) {
  helpdeskStore.selectedChat = chat
  await helpdeskStore.fetchChatMessages(chat.id)
}

async function handleAgentRespond() {
  if (!agentInput.value.trim() || !helpdeskStore.selectedChat) return
  const content = agentInput.value
  agentInput.value = ''

  helpdeskStore.selectedChatMessages.push({
    id: Date.now(),
    chat_id: helpdeskStore.selectedChat.id,
    sender_type: 'agent' as const,
    sender_id: null,
    content,
    created_at: new Date().toISOString(),
  })

  await helpdeskStore.agentRespond(helpdeskStore.selectedChat.id, content)
}

function getChatTitle(chat: any) {
  const msg = chat.messages?.[0]?.content
  if (!msg) return 'New Conversation'
  return msg.length > 30 ? msg.slice(0, 30) + '...' : msg
}

function getSenderLabel(senderType: string) {
  if (senderType === 'ai') return 'AI Assistant'
  if (senderType === 'agent') return 'Support Agent'
  return 'You'
}

function getAgentSenderLabel(senderType: string) {
  if (senderType === 'agent') return 'You'
  if (senderType === 'ai') return 'AI Assistant'
  return helpdeskStore.selectedChat?.user?.name || 'User'
}
</script>

<template>
  <div class="helpdesk">

    <!-- AGENT VIEW -->
    <template v-if="authStore.isAgent">
      <div class="hd-sidebar">
        <div class="hd-sidebar__header">
          <span class="hd-sidebar__title">Agent Panel</span>
        </div>
        <div class="hd-sidebar__divider" />
        <div class="hd-sidebar__list">
          <div
            v-for="chat in helpdeskStore.agentChats"
            :key="chat.id"
            class="conv-item"
            :class="{ 'conv-item--active': helpdeskStore.selectedChat?.id === chat.id }"
            @click="handleAgentSelectChat(chat)"
          >
            <span class="conv-item__title">
              {{ chat.user?.name || 'User #' + chat.user_id }}
            </span>
          </div>
          <p v-if="helpdeskStore.agentChats.length === 0" class="hd-sidebar__empty">
            No active chats
          </p>
        </div>
      </div>

      <div class="hd-chat">
        <template v-if="helpdeskStore.selectedChat">
          <div class="hd-chat__header">
            <h2>{{ helpdeskStore.selectedChat.user?.name || 'User #' + helpdeskStore.selectedChat.user_id }}</h2>
          </div>

          <div class="hd-chat__messages" ref="agentMessagesBox">
            <p v-if="helpdeskStore.selectedChatMessages.length === 0" class="hd-chat__no-chat">
              No messages yet
            </p>
            <div
              v-for="msg in helpdeskStore.selectedChatMessages"
              :key="msg.id"
              class="msg-bubble"
              :class="{
                'msg-bubble--user': msg.sender_type === 'agent',
                'msg-bubble--agent': msg.sender_type === 'user',
                'msg-bubble--ai': msg.sender_type === 'ai',
              }"
            >
              <div class="msg-bubble__wrapper">
                <span class="msg-bubble__sender">{{ getAgentSenderLabel(msg.sender_type) }}</span>
                <div class="msg-bubble__content">{{ msg.content }}</div>
              </div>
            </div>
          </div>

          <div class="hd-chat__footer">
            <div v-if="helpdeskStore.selectedChat.status !== 'closed'" class="hd-chat__input-row">
              <input
                class="hd-chat__input"
                v-model="agentInput"
                placeholder="Type your response..."
                @keyup.enter="handleAgentRespond"
              />
              <button class="hd-chat__send-btn" @click="handleAgentRespond">Send</button>
            </div>
            <div v-else class="hd-chat__closed">This chat is closed.</div>
          </div>
        </template>

        <div v-else class="hd-chat__empty">
          <span>💬</span>
          <p>Select a chat to start responding</p>
        </div>
      </div>
    </template>

    <!-- USER VIEW -->
    <template v-else>
      <div class="hd-sidebar">
        <div class="hd-sidebar__header">
          <span class="hd-sidebar__title">Support</span>
        </div>
        <button class="hd-sidebar__new-btn" @click="handleStartNewChat">
          + New Conversation
        </button>
        <div class="hd-sidebar__divider" />
        <div class="hd-sidebar__list">
          <div
            v-for="chat in myChats"
            :key="chat.id"
            class="conv-item"
            :class="{ 'conv-item--active': helpdeskStore.currentChat?.id === chat.id }"
            @click="handleSelectChat(chat)"
          >
            <span class="conv-item__title">{{ getChatTitle(chat) }}</span>
            <button class="conv-item__delete" @click.stop="handleDeleteChat(chat.id)">✕</button>
          </div>
          <p v-if="myChats.length === 0" class="hd-sidebar__empty">No conversations yet</p>
        </div>
      </div>

      <div class="hd-chat">
        <template v-if="helpdeskStore.currentChat">
          <div class="hd-chat__header">
            <h2>{{ chatTitle || 'New Conversation' }}</h2>
          </div>

          <div class="hd-chat__messages" ref="chatBox">
            <p v-if="helpdeskStore.messages.length === 0" class="hd-chat__no-chat">
              Send a message to start the conversation!
            </p>
            <div
              v-for="msg in helpdeskStore.messages"
              :key="msg.id"
              class="msg-bubble"
              :class="`msg-bubble--${msg.sender_type}`"
            >
              <div class="msg-bubble__wrapper">
                <span class="msg-bubble__sender">{{ getSenderLabel(msg.sender_type) }}</span>
                <div class="msg-bubble__content">{{ msg.content }}</div>
              </div>
            </div>
            <div v-if="isThinking" class="hd-chat__thinking">
              <span>Thinking...</span>
            </div>
          </div>

          <div class="hd-chat__footer">
            <div v-if="helpdeskStore.currentChat.status === 'ai'" class="hd-chat__actions">
              <Button variant="outline" size="sm" @click="handleTransfer">
                Request Human Agent
              </Button>
            </div>
            <div
              v-if="helpdeskStore.currentChat.status !== 'closed'"
              class="hd-chat__input-row"
            >
              <button
                v-if="voiceSupported"
                class="hd-chat__voice-btn"
                :class="{ 'hd-chat__voice-btn--recording': isRecording }"
                @click="toggleVoice"
                :title="isRecording ? 'Stop recording' : 'Voice input'"
              >
                🎤
              </button>
              <input
                class="hd-chat__input"
                v-model="messageInput"
                placeholder="Type a message..."
                @keyup.enter="handleSendMessage"
                :disabled="isThinking"
              />
              <button
                class="hd-chat__send-btn"
                @click="handleSendMessage"
                :disabled="isThinking"
              >
                Send
              </button>
            </div>
            <div v-else class="hd-chat__closed">
              This conversation is closed.
            </div>
          </div>
        </template>

        <div v-else class="hd-chat__empty">
          <span>💬</span>
          <p>Select a conversation or start a new one</p>
          <Button @click="handleStartNewChat">+ New Conversation</Button>
        </div>
      </div>
    </template>

  </div>
</template>