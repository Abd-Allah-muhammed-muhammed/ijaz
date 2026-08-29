/** Locales that edit RTL in the Pages content editor. */
export const PAGE_CONTENT_RTL_LOCALES = ['ar', 'ur'] as const;

/** Quill formats limited to legal-text-appropriate markup. */
export const PAGE_CONTENT_FORMATS = [
  'header',
  'bold',
  'italic',
  'list',
  'link',
] as const;

/**
 * Constrained Quill toolbar: headings, bold/italic, lists, links.
 * No fonts, colors, tables, or media.
 */
export const PAGE_CONTENT_TOOLBAR = [
  [{ header: [1, 2, 3, false] }],
  ['bold', 'italic'],
  [{ list: 'ordered' }, { list: 'bullet' }],
  ['link'],
] as const;

export function isPageContentRtlLocale(locale: string): boolean {
  return (PAGE_CONTENT_RTL_LOCALES as readonly string[]).includes(locale);
}

export function getPageContentEditorModules() {
  return {
    toolbar: PAGE_CONTENT_TOOLBAR,
  };
}

/**
 * Normalize Quill HTML before saving to form state.
 * Quill may emit empty paragraph placeholders; strip those only.
 */
export function normalizeEditorHtml(html: string): string {
  if (!html || html === '<p><br></p>' || html === '<p></p>') {
    return '';
  }

  return html.trim();
}

/** True when a string looks like Quill/semantic HTML (not a raw plain textarea value alone). */
export function isStructuredHtmlContent(html: string): boolean {
  return /<\/?(?:h[1-6]|p|ul|ol|li|strong|em|a)\b/i.test(html);
}
