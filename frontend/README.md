# EventManager — Frontend

Vue 3 + TypeScript SPA with shadcn-vue, Pinia, and Tailwind CSS.

## Architecture

**Feature-based structure** — each feature has its own view, store, and types.

```
src/
├── api/
│   └── http.ts              # Fetch wrapper — auto Bearer token, 401 logout
├── components/
│   ├── ui/                  # shadcn-vue components
│   └── Navbar/              # Navbar.vue + Navbar.scss
├── layouts/
│   └── AppLayout.vue        # Navbar wrapper
├── router/
│   └── index.ts             # Vue Router — auth guards
├── stores/
│   ├── auth.store.ts        # JWT token, user, login/logout
│   ├── events.store.ts      # Events CRUD state
│   └── helpdesk.store.ts    # Chat state, messages, agent chats
├── types/
│   ├── auth.types.ts
│   ├── event.types.ts
│   └── helpdesk.types.ts
└── views/
    ├── auth/
    │   ├── LoginView.vue        # Login + MFA step + forgot password
    │   ├── RegisterView.vue
    │   └── ResetPasswordView.vue
    ├── events/
    │   ├── HomeView.vue         # Public event listing + join/leave
    │   ├── MyEventsView.vue     # CRUD for own events
    │   └── JoinedEventsView.vue
    ├── helpdesk/
    │   ├── HelpdeskView.vue     # AI chat (user) + Agent panel
    │   └── HelpdeskView.scss
    └── settings/
        └── SettingsView.vue     # Password change + MFA setup
```

## Key Decisions

**Polling over WebSockets** — the helpdesk uses 3-second polling for simplicity within the project scope. For production, Laravel Reverb (WebSockets) would be the upgrade path.

**Optimistic UI** — helpdesk messages appear immediately before the server responds, then get replaced with the real server-side message on response.

**shadcn-vue** — installed locally (not via CLI) due to Node 24 compatibility. Tailwind CSS v3 used (v4 incompatible with shadcn-vue).

**Voice input** — Web Speech API (`SpeechRecognition`), `en-EN` locale, microphone button in helpdesk chat.

## Environment

```env
# frontend/.env.local
VITE_API_URL=http://localhost/api/v1
```

## Running Locally

```bash
# Via Docker (recommended)
docker compose up -d frontend

# Direct
cd frontend
npm install
npm run dev
```

## Router Guards

```
/                    → public
/login               → guest only (redirect to / if authenticated)
/register            → guest only
/reset-password      → guest only
/my-events           → requiresAuth
/joined-events       → requiresAuth
/helpdesk            → requiresAuth (renders agent or user view based on role)
/settings            → requiresAuth
```

After login: agents redirect to `/helpdesk`, users redirect to `/`.

## State Management

**auth.store** — persists token + user to localStorage. Exposes `isAuthenticated`, `isAgent`.

**helpdesk.store** — manages current chat, messages array, agent chats, selected chat for agents. Polling logic lives in the view component using `setInterval` + `onUnmounted` cleanup.

## Shadcn Components Used

button, card, input, label, form, toast, dialog, alert-dialog, dropdown-menu, avatar, separator, badge, navigation-menu, scroll-area, switch, tabs, textarea
