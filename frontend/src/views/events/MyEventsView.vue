<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useEventsStore } from '@/stores/events.store'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import type { Event } from '@/types/event.types'
import './MyEventsView.scss'

const eventsStore = useEventsStore()

const createOpen = ref(false)
const editOpen = ref(false)
const editingEvent = ref<Event | null>(null)

const createForm = ref({ title: '', occurs_at: '', description: '' })
const editForm = ref({ description: '' })
const loading = ref(false)
const error = ref('')

onMounted(() => {
  eventsStore.fetchMyEvents()
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

async function handleCreate() {
  error.value = ''
  loading.value = true
  try {
    await eventsStore.createEvent(createForm.value)
    createForm.value = { title: '', occurs_at: '', description: '' }
    createOpen.value = false
  } catch (e: any) {
    error.value = e.message || 'Failed to create event'
  } finally {
    loading.value = false
  }
}

function openEdit(event: Event) {
  editingEvent.value = event
  editForm.value = { description: event.description || '' }
  editOpen.value = true
}

async function handleEdit() {
  if (!editingEvent.value) return
  error.value = ''
  loading.value = true
  try {
    await eventsStore.updateEvent(editingEvent.value.id, editForm.value)
    editOpen.value = false
  } catch (e: any) {
    error.value = e.message || 'Failed to update event'
  } finally {
    loading.value = false
  }
}

async function handleDelete(id: number) {
  await eventsStore.deleteEvent(id)
}
</script>

<template>
  <div class="my-events">
    <div class="my-events__header">
      <h1 class="my-events__title">My Events</h1>

      <Dialog v-model:open="createOpen">
        <DialogTrigger as-child>
          <Button>Create Event</Button>
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Create New Event</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="handleCreate">
            <div class="event-dialog__field">
              <Label for="title">Title *</Label>
              <Input id="title" v-model="createForm.title" required placeholder="Event title" />
            </div>
            <div class="event-dialog__field">
              <Label for="occurs-at">Date & Time *</Label>
              <Input 
                id="occurs-at" 
                v-model="createForm.occurs_at" 
                type="datetime-local" 
                required 
                :min="new Date().toISOString().slice(0, 16)"
                class="datetime-input"
                />
            </div>
            <div class="event-dialog__field">
              <Label for="description">Description</Label>
              <Textarea id="description" v-model="createForm.description" placeholder="Optional description" />
            </div>
            <p v-if="error" class="text-destructive text-sm">{{ error }}</p>
            <div class="event-dialog__footer">
              <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
              <Button type="submit" :disabled="loading">
                {{ loading ? 'Creating...' : 'Create' }}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <div v-if="eventsStore.loading" class="my-events__empty">
      Loading...
    </div>

    <div v-else-if="eventsStore.myEvents.length === 0" class="my-events__empty">
      You haven't created any events yet.
    </div>

    <div v-else class="my-events__grid">
      <Card v-for="event in eventsStore.myEvents" :key="event.id" class="flex flex-col">
        <CardHeader class="flex-1">
          <div class="my-event-card__title">{{ event.title }}</div>
          <div class="my-event-card__meta">
            <div class="my-event-card__meta-item">📅 {{ formatDate(event.occurs_at) }}</div>
            <div class="my-event-card__meta-item">👥 {{ event.attendees_count ?? 0 }} attending</div>
          </div>
          <p class="my-event-card__description">{{ event.description || '' }}</p>
        </CardHeader>
        <CardContent class="mt-auto">
          <div class="my-event-card__footer">
            <span></span>
            <div class="my-event-card__actions">
              <Button size="sm" variant="outline" @click="openEdit(event)">Edit</Button>

              <AlertDialog>
                <AlertDialogTrigger as-child>
                  <Button size="sm" variant="destructive">Delete</Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>Delete Event</AlertDialogTitle>
                    <AlertDialogDescription>
                      Are you sure you want to delete "{{ event.title }}"? This cannot be undone.
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="handleDelete(event.id)">Delete</AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <Dialog v-model:open="editOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Event</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="handleEdit">
          <div class="event-dialog__field">
            <Label for="edit-description">Description</Label>
            <Textarea id="edit-description" v-model="editForm.description" placeholder="Optional description" />
          </div>
          <p v-if="error" class="text-destructive text-sm">{{ error }}</p>
          <div class="event-dialog__footer">
            <Button type="button" variant="outline" @click="editOpen = false">Cancel</Button>
            <Button type="submit" :disabled="loading">
              {{ loading ? 'Saving...' : 'Save' }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>