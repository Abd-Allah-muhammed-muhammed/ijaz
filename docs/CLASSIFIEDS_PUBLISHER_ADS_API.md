# Classifieds — Publisher card & ads-by-user API

Focused mobile surface for the clickable publisher card on ad-details and the type-scoped “ads by this user” feed. Does **not** document the full pre-existing Classifieds API.

Base path: `/api/v1/classifieds`

---

## Publisher field on ad details

All four advisement detail resources (`CarAdvisementResource`, `PropertyAdvisementResource`, `ElectronicAdvisementResource`, `InstituteAdvisementResource`) now include a **slim** `publisher` object alongside the existing full `user` object.

| Field | Shape | Purpose |
|--------|--------|---------|
| `user` | Full `UserResource` (id, name, phone, email, image, …) | Unchanged — existing contact-seller / profile use |
| `publisher` | `{ id, name, image }` only | New clickable publisher card — **no phone/email** |

`publisher` is present whenever `user` is eager-loaded (same as `show` and public `*/all` list items).

Example (nested keys only):

```json
{
  "user": {
    "id": 12,
    "socket_id": "user-12",
    "name": "Ada Lovelace",
    "f_name": "Ada",
    "l_name": "Lovelace",
    "phone": "966501111111",
    "image": "https://…",
    "email": "ada@example.com"
  },
  "publisher": {
    "id": 12,
    "name": "Ada Lovelace",
    "image": "https://…"
  }
}
```

Resource class: `App\Http\Resources\Api\V1\PublisherResource`

---

## Ads by this user (type-scoped, public)

Same visibility as `*/all`: **published** ads only. Filtered by morph owner `user_id` + `user_type = App\Models\User` (via the user’s MorphMany). No auth required.

| Method | Path |
|--------|------|
| `GET` | `/api/v1/classifieds/cars/by-user/{user}` |
| `GET` | `/api/v1/classifieds/properties/by-user/{user}` |
| `GET` | `/api/v1/classifieds/electronics/by-user/{user}` |
| `GET` | `/api/v1/classifieds/institutes/by-user/{user}` |

`{user}` is the publisher’s numeric user id (route-model bound to `App\Models\User`).

### Response pagination

Same envelope as other Classifieds lists (`BaseCollection`):

```json
{
  "data": {
    "items": [ /* type-specific advisement resources */ ],
    "total": 2,
    "count": 2,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

Empty publisher → `items: []`, `total: 0` (HTTP 200).

### Query params

Same optional filters as each type’s `*/all` endpoint (`per_page` default 15, plus type-specific filters such as `operation`, `city_id`, `search`, etc.).

### Product notes for mobile

- Feed is **type-scoped only** (car detail → car ads by that user; never a mixed-type feed).
- Use `publisher` for the card UI; keep using `user` where phone/email are already required.
- Navigate with `publisher.id` → corresponding `…/by-user/{id}` endpoint.
