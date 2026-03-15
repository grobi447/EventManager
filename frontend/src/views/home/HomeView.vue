<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useEventsStore } from '@/stores/events.store'
import { useAuthStore } from '@/stores/auth.store'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import './HomeView.scss'

const eventsStore = useEventsStore()
const authStore = useAuthStore()

onMounted(() => {
  eventsStore.fetchPublicEvents()
})

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function handleJoin(eventId: number) {
  if (!authStore.isAuthenticated) {
    window.location.href = '/login'
    return
  }
  await eventsStore.joinEvent(eventId)
}

async function handleLeave(eventId: number) {
  await eventsStore.leaveEvent(eventId)
}
</script>

<template>
  <div class="home">
    <div class="home__header">
      <h1 class="home__title">Upcoming Events</h1>
      <p class="home__subtitle">Discover and join events in your community</p>
    </div>

    <div v-if="eventsStore.loading" class="home__empty">
      Loading events...
    </div>

    <div v-else-if="eventsStore.publicEvents.length === 0" class="home__empty">
      No upcoming events found.
    </div>

    <div v-else class="home__grid">
      <Card v-for="event in eventsStore.publicEvents" :key="event.id" class="flex flex-col h-full">
        <CardHeader class="event-card__header flex-1">
          <div class="event-card__title">{{ event.title }}</div>
          <div class="event-card__meta">
            <div class="event-card__meta-item">
              📅 {{ formatDate(event.occurs_at) }}
            </div>
            <div class="event-card__meta-item">
              👤 {{ event.user?.name }}
            </div>
          </div>
          <p v-if="event.description" class="event-card__description">
            {{ event.description  || ''}}
          </p>
        </CardHeader>
        <CardContent class="mt-auto">
          <div class="event-card__footer">
            <span class="event-card__attendees">
              👥 {{ event.attendees_count }} attending
            </span>
            <Button
              v-if="authStore.isAuthenticated"
              size="sm"
              :variant="event.is_joined ? 'outline' : 'default'"
              @click="event.is_joined ? handleLeave(event.id) : handleJoin(event.id)"
            >
              {{ event.is_joined ? 'Leave' : 'Join' }}
            </Button>
            <Button v-else size="sm" @click="handleJoin(event.id)">
              Join
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <div v-if="eventsStore.meta.last_page > 1" class="home__pagination">
      <Button
        variant="outline"
        size="sm"
        :disabled="eventsStore.meta.current_page === 1"
        @click="eventsStore.fetchPublicEvents(eventsStore.meta.current_page - 1)"
      >
        Previous
      </Button>
      <Button
        variant="outline"
        size="sm"
        :disabled="eventsStore.meta.current_page === eventsStore.meta.last_page"
        @click="eventsStore.fetchPublicEvents(eventsStore.meta.current_page + 1)"
      >
        Next
      </Button>
    </div>
  </div>
</template>