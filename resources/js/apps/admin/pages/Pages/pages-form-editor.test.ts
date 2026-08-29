import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import {
  getPageContentEditorModules,
  isPageContentRtlLocale,
  isStructuredHtmlContent,
  normalizeEditorHtml,
  PAGE_CONTENT_FORMATS,
  PAGE_CONTENT_TOOLBAR,
} from './page-content-editor'

const __dirname = dirname(fileURLToPath(import.meta.url))

describe('Pages Form rich text editor', () => {
  it('Pages Form renders a rich text editor for the content field per locale, not a plain textarea', () => {
    const formSrc = readFileSync(join(__dirname, 'Form.tsx'), 'utf8')
    const editorSrc = readFileSync(join(__dirname, 'PageContentEditor.tsx'), 'utf8')

    expect(formSrc).toContain('PageContentEditor')
    expect(formSrc).toMatch(/<PageContentEditor[\s\S]*locale=\{locale\}/)
    expect(formSrc).not.toMatch(/as=\{\s*['"]textarea['"]\s*\}/)
    expect(editorSrc).toContain('react-quill-new')
    expect(editorSrc).toContain('ReactQuill')
    expect(editorSrc).toContain('data-testid={`page-content-editor-${locale}`}')
    expect(PAGE_CONTENT_TOOLBAR).toEqual([
      [{ header: [1, 2, 3, false] }],
      ['bold', 'italic'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['link'],
    ])
    expect([...PAGE_CONTENT_FORMATS]).toEqual(['header', 'bold', 'italic', 'list', 'link'])
    expect(getPageContentEditorModules().toolbar).toEqual(PAGE_CONTENT_TOOLBAR)
    expect(isPageContentRtlLocale('ar')).toBe(true)
    expect(isPageContentRtlLocale('ur')).toBe(true)
    expect(isPageContentRtlLocale('en')).toBe(false)
  })

  it('the editor output is valid HTML saved to form state correctly', () => {
    const html =
      '<h2>Acceptance</h2><p>Hello <strong>world</strong></p><ul><li>One</li></ul><p><a href="https://example.com">Link</a></p>'

    expect(normalizeEditorHtml(html)).toBe(html)
    expect(normalizeEditorHtml('<p><br></p>')).toBe('')
    expect(normalizeEditorHtml('<p></p>')).toBe('')
    expect(normalizeEditorHtml(`  ${html}  `)).toBe(html)
    expect(isStructuredHtmlContent(html)).toBe(true)
    expect(isStructuredHtmlContent('plain text only')).toBe(false)

    const formSrc = readFileSync(join(__dirname, 'Form.tsx'), 'utf8')
    expect(formSrc).toMatch(/onChange=\{\(html\)\s*=>\s*\{[\s\S]*content:\s*html/)
  })
})
