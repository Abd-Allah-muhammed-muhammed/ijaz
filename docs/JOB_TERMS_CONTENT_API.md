# Job Terms content API (mobile)

Mobile should load Terms & Conditions as **structured HTML from the CMS Pages API**, not as a WebView of a marketing website page (which includes site chrome).

**Content is fully self-contained — render it exactly as received, no additional styling needed on your end.**

Each page is wrapped server-side in a badge + white card shell (inline styles). Headings already include inline brand teal (`#00686D`) + bold. Relative image paths in stored content (e.g. `/media/logos/default.svg`) are rewritten to **absolute URLs** at request time using the current app URL.

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

## Response shape — unchanged (no breaking change)

`data.content` is still a **single HTML string**, exactly as mobile already integrates against.

For `terms`, that string contains the merged Service Provider Authorization + How to Use Agency sections in one HTML blob (each section keeps its own headings inside the single card wrapper). The field type and envelope are unchanged — keep rendering `content` as-is in one HTML view.

## Why not WebView of the marketing site?

Admin-managed Pages content is the **body only** — no site header, footer, or nav. Render it in an in-app HTML view.

Do **not** open `/privacy-and-policies` (or similar marketing routes); those include website chrome (dark hero banner). Use this CMS API instead.

Website (optional): `GET /{locale}/pages/{slug}` (e.g. `/en/pages/terms`) uses the same server-rendered card HTML plus a web-only hero banner.

## Example response

```json
{
  "success": true,
  "message": "…",
  "data": {
    "id": 4,
    "slug": "terms",
    "title": "Terms and Conditions",
    "content": "<div class=\"cms-page-card\" …>…service provider authorization + how to use agency merged HTML…</div>"
  },
  "errors": []
}
```

`content` is a UTF-8 HTML fragment. Image `src` values inside it are absolute (e.g. `https://your-api-host/media/logos/default.svg`).

## HTML tags / shell to expect

| Piece | Notes |
|-------|--------|
| `.cms-page-card` wrapper | White rounded card + teal title badge (inline styles) |
| `h1`–`h6` | Section headings with inline `style="color: #00686D; font-weight: 700;"` |
| `p`, `ul` / `ol` / `li`, `strong` / `em`, `a`, `img`, tables, etc. | From the admin editor allowlist |

**Not** present after sanitization: `script`, `iframe`, event-handler attributes (`onclick`, etc.).

## Rendering notes

1. Fetch `GET /api/v1/catalog/pages/terms` with the user’s locale.
2. Show `title` in the native screen chrome if you want; the HTML `content` includes section headings inside the merged body.
3. Render `content` in a WebView/HTML widget **as-is** — no site CSS, no extra heading color pass required.
4. Prefer an HTML renderer that does not execute scripts (content is sanitized server-side; still treat as untrusted markup).
5. Absolute logo URLs are already resolved for the environment that served the request.

## Admin source of truth

Dashboard: **Content/CMS → Pages**.

- Edit `terms` like any normal page — its `content` field holds the full merged HTML directly.
- Leaf pages (`privacy`, `service-provider-authorization`, `how-to-use-agency`, `real-estate-marketplace-terms`) remain available as standalone, independently-editable CMS pages for future reuse; they are not linked to website routes or to `terms` rendering.
