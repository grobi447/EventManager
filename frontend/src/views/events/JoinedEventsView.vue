<script setup lang="ts">
import { onMounted } from 'vue'
import { useEventsStore } from '@/stores/events.store'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import './JoinedEventsView.scss'

const eventsStore = useEventsStore()

onMounted(() => {
  eventsStore.fetchJoinedEvents()
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

async function handleLeave(eventId: number) {
  await eventsStore.leaveEvent(eventId)
  eventsStore.joinedEvents = eventsStore.joinedEvents.filter(e => e.id !== eventId)
}
</script>

<template>
  <div class="joined-events">
    <div class="joined-events__header">
      <h1 class="joined-events__title">Joined Events</h1>
    </div>

    <div v-if="eventsStore.loading" class="joined-events__empty">
      Loading...
    </div>

    <div v-else-if="eventsStore.joinedEvents.length === 0" class="joined-events__empty">
      You haven't joined any events yet.
    </div>

    <div v-else class="joined-events__grid">
      <Card v-for="event in eventsStore.joinedEvents" :key="event.id" class="flex flex-col">
        <CardHeader class="flex-1">
          <div class="my-event-card__title">{{ event.title }}</div>
          <div class="my-event-card__meta">
            <div class="my-event-card__meta-item">📅 {{ formatDate(event.occurs_at) }}</div>
            <div class="my-event-card__meta-item">👤 {{ event.user?.name }}</div>
            <div class="my-event-card__meta-item">👥 {{ event.attendees_count ?? 0 }} attending</div>
          </div>
          <p class="my-event-card__description">{{ event.description || '' }}</p>
        </CardHeader>
        <CardContent class="mt-auto">
          <div class="my-event-card__footer">
            <span></span>
            <Button size="sm" variant="outline" @click="handleLeave(event.id)">
              Leave
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>