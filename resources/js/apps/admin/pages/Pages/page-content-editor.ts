import Color from '@tiptap/extension-color'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import Link from '@tiptap/extension-link'
import { Table } from '@tiptap/extension-table'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import { TableRow } from '@tiptap/extension-table-row'
import TextAlign from '@tiptap/extension-text-align'
import { TextStyle } from '@tiptap/extension-text-style'
import Underline from '@tiptap/extension-underline'
import StarterKit from '@tiptap/starter-kit'

/** Locales that edit RTL in the Pages content editor. */
export const PAGE_CONTENT_RTL_LOCALES = ['ar', 'ur'] as const

/**
 * Official Ijaz logo — root-relative path matching existing web legal pages.
 * Production-safe on any host; mobile WebViews should resolve against the API origin.
 */
export const PAGE_CONTENT_LOGO_SRC = '/media/logos/default.svg'

export const PAGE_CONTENT_LOGO_HTML =
  `<p style="text-align:center;"><img src="${PAGE_CONTENT_LOGO_SRC}" alt="Ijaz" width="120" height="120" /></p>`

/** Safe HTML tags accepted by PageHtmlSanitizer / the unrestricted Pages editor. */
export const PAGE_CONTENT_ALLOWED_TAGS = [
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  'p',
  'ul',
  'ol',
  'li',
  'strong',
  'em',
  'u',
  's',
  'blockquote',
  'code',
  'pre',
  'hr',
  'span',
  'table',
  'thead',
  'tbody',
  'tr',
  'th',
  'td',
  'a',
  'img',
] as const

/** All heading levels exposed in the toolbar. */
export const PAGE_CONTENT_HEADING_LEVELS = [1, 2, 3, 4, 5, 6] as const

export const PAGE_CONTENT_TEXT_COLORS = [
  '#00686D',
  '#071437',
  '#000000',
  '#dc3545',
  '#198754',
  '#0d6efd',
] as const

/**
 * Unrestricted toolbar actions for the general-purpose Pages CMS editor.
 * Insert Logo + Insert Image remain available as convenience actions.
 */
export const PAGE_CONTENT_TOOLBAR_ACTIONS = [
  'paragraph',
  'heading',
  'bold',
  'italic',
  'underline',
  'strike',
  'code',
  'blockquote',
  'horizontalRule',
  'bulletList',
  'orderedList',
  'textAlign',
  'textColor',
  'table',
  'link',
  'insertLogo',
  'insertImage',
] as const

/** Build a centered img fragment for an uploaded (or any) content image URL. */
export function buildPageContentImageHtml(src: string, alt = ''): string {
  const safeSrc = src.trim()
  const safeAlt = alt.replaceAll('"', '&quot;')

  return `<p style="text-align:center;"><img src="${safeSrc}" alt="${safeAlt}" /></p>`
}

export type PageContentImageUploadResult = {
  url: string
  path?: string
}

type PageContentImageUploadPayload = {
  success?: boolean
  data?: PageContentImageUploadResult
  message?: string
  errors?: Record<string, string[]>
}

function extractPageContentImageUploadError(error: unknown): string {
  const axiosLike = error as {
    response?: { data?: PageContentImageUploadPayload }
    message?: string
  }

  return (
    axiosLike.response?.data?.errors?.image?.[0] ||
    axiosLike.response?.data?.message ||
    (error instanceof Error ? error.message : null) ||
    axiosLike.message ||
    'Image upload failed.'
  )
}

/**
 * Upload a Pages content image via the dashboard endpoint.
 * Throws with a user-facing message on validation / network failure.
 */
export async function uploadPageContentImage(
  file: File,
  post: (url: string, body: FormData) => Promise<PageContentImageUploadPayload>,
  uploadUrl: string,
): Promise<PageContentImageUploadResult> {
  const formData = new FormData()
  formData.append('image', file)

  let payload: PageContentImageUploadPayload

  try {
    payload = await post(uploadUrl, formData)
  } catch (error) {
    throw new Error(extractPageContentImageUploadError(error))
  }

  const url = payload.data?.url?.trim()

  if (!payload.success || !url) {
    throw new Error(payload.errors?.image?.[0] || payload.message || 'Image upload failed.')
  }

  return { url, path: payload.data?.path }
}

export function isPageContentRtlLocale(locale: string): boolean {
  return (PAGE_CONTENT_RTL_LOCALES as readonly string[]).includes(locale)
}

/** Tiptap extensions matching the unrestricted Pages toolbar. */
export function getPageContentEditorExtensions(placeholder?: string) {
  return [
    StarterKit.configure({
      heading: {
        levels: [...PAGE_CONTENT_HEADING_LEVELS],
      },
      bold: {},
      italic: {},
      strike: {},
      code: {},
      codeBlock: {},
      blockquote: {},
      horizontalRule: {},
      bulletList: {},
      orderedList: {},
      listItem: {},
      paragraph: {},
      hardBreak: {},
      history: {},
      dropcursor: false,
      gapcursor: false,
    }),
    Underline,
    TextStyle,
    Color,
    TextAlign.configure({
      types: ['heading', 'paragraph'],
      alignments: ['left', 'center', 'right', 'justify'],
    }),
    Table.configure({
      resizable: false,
    }),
    TableRow,
    TableHeader,
    TableCell,
    Link.configure({
      openOnClick: false,
      autolink: true,
      linkOnPaste: true,
      HTMLAttributes: {
        rel: 'noopener noreferrer nofollow',
        target: '_blank',
      },
    }),
    Image.extend({
      addAttributes() {
        return {
          ...this.parent?.(),
          style: {
            default: null,
            parseHTML: (element: HTMLElement) => element.getAttribute('style'),
            renderHTML: (attributes: { style?: string | null }) => {
              if (!attributes.style) {
                return {}
              }

              return { style: attributes.style }
            },
          },
        }
      },
    }).configure({
      inline: false,
      allowBase64: false,
    }),
    Placeholder.configure({
      placeholder: placeholder || '',
    }),
  ]
}

/**
 * Normalize editor HTML before saving to form state.
 * Tiptap/ProseMirror may emit empty paragraph placeholders; strip those only.
 */
export function normalizeEditorHtml(html: string): string {
  if (!html) {
    return ''
  }

  const trimmed = html.trim()

  if (
    trimmed === '<p></p>' ||
    trimmed === '<p><br></p>' ||
    trimmed === '<p><br class="ProseMirror-trailingBreak"></p>'
  ) {
    return ''
  }

  return trimmed
}

/** True when a string looks like semantic HTML (not a raw plain textarea value alone). */
export function isStructuredHtmlContent(html: string): boolean {
  return /<\/?(?:h[1-6]|p|ul|ol|li|strong|em|u|s|blockquote|code|pre|hr|span|table|thead|tbody|tr|th|td|a|img)\b/i.test(
    html,
  )
}
