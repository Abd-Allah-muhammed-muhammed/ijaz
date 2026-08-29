import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import Link from '@tiptap/extension-link';
import StarterKit from '@tiptap/starter-kit';

/** Locales that edit RTL in the Pages content editor. */
export const PAGE_CONTENT_RTL_LOCALES = ['ar', 'ur'] as const;

/**
 * Official Ijaz logo — root-relative path matching existing web legal pages
 * (`PrivacyAndPolicies.tsx`, etc.). Production-safe on any host; mobile WebViews
 * should resolve against the API origin.
 */
export const PAGE_CONTENT_LOGO_SRC = '/media/logos/default.svg';

export const PAGE_CONTENT_LOGO_HTML =
  `<p style="text-align:center;"><img src="${PAGE_CONTENT_LOGO_SRC}" alt="Ijaz" width="120" height="120" /></p>`;

/**
 * Safe HTML tags accepted by PageHtmlSanitizer / the constrained legal-text editor.
 * (h1–h6 kept for sanitizer parity; toolbar only exposes h2/h3.)
 */
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
  'a',
  'img',
] as const;

/** Heading levels exposed in the toolbar (paragraph + these). */
export const PAGE_CONTENT_HEADING_LEVELS = [2, 3] as const;

/**
 * Constrained toolbar actions — legal-text set + Insert Logo.
 * No fonts, colors, tables, free media upload, strike, code, or blockquote.
 */
export const PAGE_CONTENT_TOOLBAR_ACTIONS = [
  'paragraph',
  'heading',
  'bold',
  'italic',
  'bulletList',
  'orderedList',
  'link',
  'insertLogo',
] as const;

export function isPageContentRtlLocale(locale: string): boolean {
  return (PAGE_CONTENT_RTL_LOCALES as readonly string[]).includes(locale);
}

/**
 * Tiptap extensions matching the constrained Pages toolbar.
 * StarterKit extras (strike, code, blockquote, etc.) are disabled.
 */
export function getPageContentEditorExtensions(placeholder?: string) {
  return [
    StarterKit.configure({
      heading: {
        levels: [...PAGE_CONTENT_HEADING_LEVELS],
      },
      bold: {},
      italic: {},
      bulletList: {},
      orderedList: {},
      listItem: {},
      paragraph: {},
      hardBreak: {},
      history: {},
      // Disabled — outside the legal-text formatting set.
      strike: false,
      code: false,
      codeBlock: false,
      blockquote: false,
      horizontalRule: false,
      dropcursor: false,
      gapcursor: false,
    }),
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
                return {};
              }

              return { style: attributes.style };
            },
          },
        };
      },
    }).configure({
      inline: false,
      allowBase64: false,
    }),
    Placeholder.configure({
      placeholder: placeholder || '',
    }),
  ];
}

/**
 * Normalize editor HTML before saving to form state.
 * Tiptap/ProseMirror may emit empty paragraph placeholders; strip those only.
 */
export function normalizeEditorHtml(html: string): string {
  if (!html) {
    return '';
  }

  const trimmed = html.trim();

  if (
    trimmed === '<p></p>' ||
    trimmed === '<p><br></p>' ||
    trimmed === '<p><br class="ProseMirror-trailingBreak"></p>'
  ) {
    return '';
  }

  return trimmed;
}

/** True when a string looks like semantic HTML (not a raw plain textarea value alone). */
export function isStructuredHtmlContent(html: string): boolean {
  return /<\/?(?:h[1-6]|p|ul|ol|li|strong|em|a|img)\b/i.test(html);
}
