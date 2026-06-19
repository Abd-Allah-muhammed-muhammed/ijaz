# Chat Module Refactor — Mobile Developer Notice

> Branch: refactor/chat-module
> Last updated: 2026-06-19
> Status: Refactor in progress — internal code restructuring only

---

## Summary

The chat system is being refactored internally into a dedicated `Modules/Chat/` module.

**This is a backend-only refactor.**

Zero changes to the mobile API.

---

## What is NOT changing (guaranteed)

| Item | Status |
|------|--------|
| All API URLs | ✅ Unchanged |
| All request body fields | ✅ Unchanged |
| All response shapes | ✅ Unchanged |
| All status codes | ✅ Unchanged |
| WebSocket channel names | ✅ Unchanged |
| Broadcast event names | ✅ Unchanged |
| Auth requirements | ✅ Unchanged |

---

## Current Chat Endpoints (all active, all unchanged)

### Direct / Member Chat
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/chats` | sanctum | List my conversations |
| POST | `/api/v1/chats` | sanctum | Open or get conversation |
| GET | `/api/v1/chats/{conversation}` | sanctum | List messages |
| POST | `/api/v1/chats/send/{conversation}` | sanctum | Send message |

### Order Chat
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/chats/orders` | sanctum | List order conversations |
| POST | `/api/v1/chats/orders` | sanctum | Open order conversation |
| GET | `/api/v1/chats/orders/{conversation}` | sanctum | List messages |
| POST | `/api/v1/chats/orders/send/{conversation}` | sanctum | Send message |

### Ticket Support Chat
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/chats/tickets` | sanctum | List ticket conversations |
| GET | `/api/v1/chats/tickets/{conversation}` | sanctum | List messages |
| POST | `/api/v1/chats/tickets/send/{conversation}` | sanctum | Send message |

### Opportunity Chat
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/chats/opportunities` | sanctum | List opportunity conversations |
| POST | `/api/v1/chats/opportunities` | sanctum | Open opportunity conversation |
| GET | `/api/v1/chats/opportunities/{conversation}` | sanctum | List messages |
| POST | `/api/v1/chats/opportunities/{conversation}/send` | sanctum | Send message |

### Guarantor Chat
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/chats/guarantor` | sanctum | List guarantor conversations |
| POST | `/api/v1/chats/guarantor` | sanctum | Open guarantor conversation |
| GET | `/api/v1/chats/guarantor/{conversation}` | sanctum | List messages |
| POST | `/api/v1/chats/guarantor/{conversation}/send` | sanctum | Send message |

---

## WebSocket Channels (unchanged)

| Channel | Type | Event | Description |
|---------|------|-------|-------------|
| `chats.{conversationId}` | Presence | `.new-message` | New message in conversation |
| Private per participant | Private | `.chat-updated` | Conversation list updated |

---

## Changelog
| Date | Change |
|------|--------|
| 2026-06-19 | Refactor started — internal restructuring only, no API changes |
