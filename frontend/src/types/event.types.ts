export interface EventUser {
    id: number
    name: string
}

export interface Event {
    id: number
    user_id: number
    title: string
    occurs_at: string
    description: string | null
    attendees_count: number
    user: EventUser
    is_joined: boolean
}

export interface EventsResponse {
    success: boolean
    data: Event[]
    meta: {
        total: number
        per_page: number
        current_page: number
        last_page: number
    }
}