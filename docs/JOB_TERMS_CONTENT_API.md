# Job Terms content API (mobile)

Mobile should load Terms & Conditions as **structured HTML from the CMS Pages API**, not as a WebView of a marketing website page (which includes site chrome).

**Content is fully self-contained — render it exactly as received, no additional styling needed on your end.**

Headings already include inline brand teal (`#00686D`) + bold. Body paragraphs inherit your HTML view’s default text color (no forced navy override). The optional logo uses a root-relative path that resolves against the API origin.

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

Any other CMS slug works the same way: `GET /api/v1/catalog/pages/{slug}`.

## Why not WebView of the marketing site?

Admin-managed Pages content is the **body only** — no site header, footer, or nav. Render it in an in-app HTML view.

Do **not** open `/privacy-and-policies` (or similar static Inertia routes); those are marketing pages with site chrome and lang-file HTML, not this CMS API.

Website (optional): `GET /{locale}/pages/{slug}` (e.g. `/en/pages/terms`) uses a reusable template (title badge + the same self-contained `content`).

## Example response

```json
{
  "success": true,
  "message": "…",
  "data": {
    "id": 1,
    "slug": "terms",
    "title": "Terms and Conditions",
    "content": "<h2 style=\"color: #00686D; font-weight: 700;\">Acceptance</h2><p>This is placeholder Terms and Conditions content — not legal text.</p><p><strong>[PLACEHOLDER SECTION — replace with real legal text before launch]</strong></p><h2 style=\"color: #00686D; font-weight: 700;\">Platform use</h2><p>[PLACEHOLDER SECTION — replace with real legal text before launch]</p><ul><li>First placeholder bullet</li><li>Second placeholder bullet</li></ul><ol><li>[PLACEHOLDER SECTION — replace with real legal text before launch]</li></ol>"
  },
  "errors": []
}
```

`content` is a UTF-8 HTML fragment. Seeded copy is clearly marked as placeholder until legal text is entered in Admin → Content/CMS → Pages (`slug: terms`).

When an admin inserts the official logo via the editor, expect something like:

```html
<p style="text-align:center;"><img src="/media/logos/default.svg" alt="Ijaz" width="120" height="120"></p>
```

Resolve `/media/logos/default.svg` against the API host (e.g. `https://ijaz.test/media/logos/default.svg` in local, or your production origin).

## HTML tags to expect

Admin editor toolbar is constrained; server sanitization allows the same set. Content already carries inline heading styles — **do not restyle headings with a second color unless product requires it**.

| Tag | Use |
|-----|-----|
| `h1`–`h6` | Section headings with inline `style="color: #00686D; font-weight: 700;"` (`h2`/`h3` typical) |
| `p` | Paragraphs (no forced color — inherit default) |
| `ul` / `ol` / `li` | Bulleted and numbered lists |
| `strong` / `em` (and possibly `b` / `i`) | Emphasis |
| `a` | Links (`href` http/https/mailto; no `onclick`) |
| `img` | Official logo only (`/media/logos/default.svg`, centered via inline style) |
| `br` | Soft line breaks |

**Not** present after sanitization: `script`, `iframe`, event-handler attributes (`onclick`, etc.).

## Rendering notes

1. Fetch `GET /api/v1/catalog/pages/terms` with the user’s locale.
2. Show `title` in the native screen chrome if you want; the HTML `content` is already self-styled.
3. Render `content` in a WebView/HTML widget **as-is** — no site CSS, no extra heading color pass required.
4. Prefer an HTML renderer that does not execute scripts (content is sanitized server-side; still treat as untrusted markup).
5. Set the WebView base URL to the API origin so root-relative logo paths load correctly.

## Admin source of truth

Dashboard: **Content/CMS → Pages**. Edit the page with slug `terms`. On save, the server sanitizes HTML and applies brand heading styles automatically. Use **Insert Logo** in the editor for the official mark.
