/**
 * Escape HTML, then optionally wrap case-insensitive matches of `term` in <mark>.
 * Safe for dangerouslySetInnerHTML in BOTH search and non-search paths — content
 * is always escaped first; an empty/null term returns escaped text only.
 */
export function highlightSearchTerm(content: string, term?: string | null): string {
  const raw = String(content ?? '');
  const escaped = raw
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const needle = term?.trim();
  if (!needle) {
    return escaped;
  }

  const pattern = needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return escaped.replace(
    new RegExp(`(${pattern})`, 'gi'),
    '<mark class="bg-warning-subtle text-gray-900 px-1 rounded">$1</mark>',
  );
}
