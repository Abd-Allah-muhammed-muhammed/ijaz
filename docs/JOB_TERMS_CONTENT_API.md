# Job Terms content API (mobile)

Mobile should load Terms & Conditions as **structured HTML from the CMS Pages API**, not as a WebView of a marketing website page (which includes site chrome).

## Endpoint

```http
GET /api/v1/catalog/pages/terms
Accept-Language: en
```

Unauthenticated. Locale via `Accept-Language` (`en`, `ar`, `ur`, `hi`).

List (titles only, no body):

```http
GET /api/v1/catalog/pages
```

## Why not WebView?

Admin-managed Pages content is the **body only** — no site header, footer, or nav. Render it in an in-app HTML view styled by the mobile app.

Do **not** open `/privacy-and-policies` (or similar web routes); those are static Inertia pages with marketing layout and lang-file HTML, not this CMS page.

## Example response

```json
{
  "success": true,
  "message": "…",
  "data": {
    "id": 1,
    "slug": "terms",
    "title": "Terms and Conditions",
    "content": "<h2>Acceptance</h2><p>This is placeholder Terms and Conditions content — not legal text.</p><p><strong>[PLACEHOLDER SECTION — replace with real legal text before launch]</strong></p><h2>Platform use</h2><p>[PLACEHOLDER SECTION — replace with real legal text before launch]</p><ul><li>First placeholder bullet</li><li>Second placeholder bullet</li></ul><ol><li>[PLACEHOLDER SECTION — replace with real legal text before launch]</li></ol>"
  },
  "errors": []
}
```

`content` is a UTF-8 HTML fragment. Seeded copy is clearly marked as placeholder until legal text is entered in Admin → Content/CMS → Pages (`slug: terms`).

## HTML tags to expect and style

Admin editor toolbar is constrained; server sanitization allows the same set. Style at least:

| Tag | Use |
|-----|-----|
| `h1`–`h6` | Section headings (`h2` is typical in seeded content) |
| `p` | Paragraphs |
| `ul` / `ol` / `li` | Bulleted and numbered lists |
| `strong` / `em` (and possibly `b` / `i`) | Emphasis |
| `a` | Links (`href` http/https/mailto; no `onclick`) |
| `br` | Soft line breaks |

**Not** present after sanitization: `script`, `iframe`, event-handler attributes (`onclick`, etc.), inline styles from a kitchen-sink editor.

## Rendering notes

1. Fetch `GET /api/v1/catalog/pages/terms` with the user’s locale.
2. Show `title` in the screen chrome; render `content` in a WebView/HTML widget **without** loading an external website URL.
3. Apply app typography/spacing to the tags above; do not rely on site CSS.
4. Prefer an HTML renderer that does not execute scripts (content is sanitized server-side; still treat as untrusted markup).

## Admin source of truth

Dashboard: **Content/CMS → Pages**. Edit the page with slug `terms`. Content is saved as clean HTML (WYSIWYG + server-side HTML Purifier config `pages`).
