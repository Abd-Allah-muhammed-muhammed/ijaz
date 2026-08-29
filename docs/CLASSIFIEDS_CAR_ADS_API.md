# Classifieds — Car ads API notes

Focused notes for mobile clients creating/updating car advisements (`POST`/`PUT` `/api/v1/classifieds/cars`). Complements `docs/CLASSIFIEDS_PUBLISHER_ADS_API.md` (publisher card / ads-by-user).

Base path: `/api/v1/classifieds`

---

## Breaking change — `transmission` and `fuel_type` are fixed enums

**Previously:** both fields were nullable free-text strings (`string|max:255`). Arbitrary values such as `"test"` were silently accepted and stored.

**Now:** both fields remain **nullable/optional**, but when sent they **must** be one of the exact backed-enum values below. Any other string is rejected with a **422 validation error**.

| Field | Valid values | PHP enum |
|--------|----------------|----------|
| `transmission` | `automatic`, `manual` | `Modules\Classifieds\Enums\TransmissionEnum` |
| `fuel_type` | `petrol`, `diesel`, `electric`, `hybrid` | `Modules\Classifieds\Enums\FuelTypeEnum` |

### Examples

Accepted:

```json
{
  "transmission": "automatic",
  "fuel_type": "petrol"
}
```

Also accepted (omit either or both — same as before):

```json
{}
```

Rejected (422, `errors.transmission` / `errors.fuel_type`):

```json
{
  "transmission": "test",
  "fuel_type": "gasoline"
}
```

### Response shape

API and dashboard resources now return these fields in the same `{ value, label, color }` shape as `operation` / `usage_status` (not a raw string):

```json
{
  "transmission": {
    "value": "automatic",
    "label": "Automatic",
    "color": "info"
  },
  "fuel_type": {
    "value": "electric",
    "label": "Electric",
    "color": "success"
  }
}
```

When omitted / null in storage, the JSON value is `null`.

### Existing rows

This change applies to **new create/update submissions only**. Existing rows that already store free-text junk (e.g. `"test"`) are **not** migrated or retroactively rejected; they continue to load. Read responses for junk values surface `{ "value": "test", "label": "test", "color": "secondary" }` so admin review does not crash.

### Mobile action required

1. Send only the exact enum values listed above (or omit the fields).
2. Stop sending placeholder strings (`"test"`, localized free text, etc.).
3. Update UI to read `transmission.label` / `fuel_type.label` (or `.value`) instead of treating the field as a plain string.
