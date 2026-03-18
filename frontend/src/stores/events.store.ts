import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/api/http'
import type { Event, EventsResponse } from '@/types/event.types'

export const useEventsStore = defineStore('events', () => {
  const publicEvents = ref<Event[]>([])
  const myEvents = ref<Event[]>([])
  const joinedEvents = ref<Event[]>([])
  const loading = ref(false)
  const meta = ref({ total: 0, per_page: 15, current_page: 1, last_page: 1 })

  async function fetchPublicEvents(page = 1) {
    loading.value = true
    try {
      const response = await http.get<EventsResponse>(`/events?page=${page}`)
      publicEvents.value = response.data
      meta.value = response.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchMyEvents() {
    loading.value = true
    try {
      const response = await http.get<any>('/events/my')
      myEvents.value = response.data
    } finally {
      loading.value = false
    }
  }

  async function fetchJoinedEvents() {
    loading.value = true
    try {
      const response = await http.get<EventsResponse>('/events/joined')
      joinedEvents.value = response.data
    } finally {
      loading.value = false
    }
  }

  async function joinEvent(eventId: number) {
    await http.post(`/events/${eventId}/join`, {})
    const event = publicEvents.value.find(e => e.id === eventId)
    if (event) {
      event.attendees_count++
      event.is_joined = true
    }
  }

  async function leaveEvent(eventId: number) {
    await http.delete(`/events/${eventId}/leave`)
    const event = publicEvents.value.find(e => e.id === eventId)
    if (event) {
      event.attendees_count--
      event.is_joined = false
    }
  }

  async function createEvent(data: { title: string; occurs_at: string; description?: string }) {
    const response = await http.post<any>('/events', data)
    myEvents.value.unshift(response.data)
    return response.data
  }

  async function updateEvent(id: number, data: { description: string }) {
    const response = await http.put<any>(`/events/${id}`, data)
    const index = myEvents.value.findIndex(e => e.id === id)
    if (index !== -1) myEvents.value[index] = response.data
    return response.data
  }

  async function deleteEvent(id: number) {
    await http.delete(`/events/${id}`)
    myEvents.value = myEvents.value.filter(e => e.id !== id)
  }

  return {
    publicEvents, myEvents, joinedEvents, loading, meta,
    fetchPublicEvents, fetchMyEvents, fetchJoinedEvents,
    joinEvent, leaveEvent, createEvent, updateEvent, deleteEvent,
  }
})