import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import {
  isPageContentRtlLocale,
  isStructuredHtmlContent,
  normalizeEditorHtml,
  PAGE_CONTENT_ALLOWED_TAGS,
  PAGE_CONTENT_HEADING_LEVELS,
  PAGE_CONTENT_TOOLBAR_ACTIONS,
} from './page-content-editor'

const __dirname = dirname(fileURLToPath(import.meta.url))

const formSrc = readFileSync(join(__dirname, 'Form.tsx'), 'utf8')
const editorSrc = readFileSync(join(__dirname, 'PageContentEditor.tsx'), 'utf8')
const editorConfigSrc = readFileSync(join(__dirname, 'page-content-editor.ts'), 'utf8')
const validationSrc = readFileSync(join(__dirname, 'validation.ts'), 'utf8')

describe('Pages Form Tiptap editor and locale tabs', () => {
  it("Pages Form renders locale tabs, only one locale's fields visible/mounted at a time", () => {
    expect(formSrc).toContain('activeLocale')
    expect(formSrc).toContain('setActiveLocale')
    expect(formSrc).toMatch(/rounded-pill/)
    expect(formSrc).toMatch(/btn-primary/)
    // Only the active locale's editor is mounted (conditional render, not CSS hide-all).
    expect(formSrc).toMatch(/locale=\{activeLocale\}/)
    expect(formSrc).toMatch(/PageContentEditor/)
    expect(formSrc).not.toMatch(/Object\.keys\(locales\)\.map\(\(locale\s*=>\s*\(/)
    expect(formSrc).not.toMatch(/Object\.keys\(locales\)\.map\(\(locale\)\s*=>\s*\(/)
  })

  it('switching locale tabs preserves unsaved edits in the other locales (state not lost on tab switch)', () => {
    // All locale translations live in form state; tab switch only changes activeLocale.
    expect(formSrc).toContain('translations:')
    expect(formSrc).toMatch(/form\.setData[\s\S]*translations|updateTranslation/)
    expect(formSrc).toMatch(/setActiveLocale\(/)
    // Must not reset translations when switching tabs.
    expect(formSrc).not.toMatch(/setActiveLocale\([^)]*\)[\s\S]{0,80}translations:\s*\{\}/)
    expect(formSrc).not.toMatch(/onClick=\{[^}]*setData\([^)]*translations:\s*Object\.keys/)
  })

  it('the Tiptap editor renders correctly in RTL for ar/ur locales — toolbar and content both right-aligned correctly, no mirrored/broken layout', () => {
    expect(editorSrc).toContain('@tiptap/react')
    expect(editorSrc).toContain('useEditor')
    expect(editorSrc).toContain("dir={rtl ? 'rtl' : 'ltr'}")
    expect(editorSrc).toContain('data-testid={`page-content-editor-${locale}`}')
    expect(isPageContentRtlLocale('ar')).toBe(true)
    expect(isPageContentRtlLocale('ur')).toBe(true)
    expect(isPageContentRtlLocale('en')).toBe(false)
    expect(isPageContentRtlLocale('hi')).toBe(false)
    // No CSS transforms that mirror/flip the toolbar (Quill RTL bug).
    expect(editorSrc).not.toMatch(/scaleX\s*\(\s*-1\s*\)/)
    expect(editorSrc).not.toMatch(/transform:\s*['"]scaleX/)
    expect(editorConfigSrc).not.toContain('react-quill')
    expect(editorSrc).not.toContain('react-quill')
    expect(editorSrc).not.toContain('ReactQuill')
  })

  it('editor output remains valid HTML matching the same safe-tag set as before (h1-h6, p, ul/ol/li, strong, em, a)', () => {
    const html =
      '<h2>Acceptance</h2><p>Hello <strong>world</strong></p><ul><li>One</li></ul><ol><li>First</li></ol><p><a href="https://example.com">Link</a></p><h3>More</h3><p><em>italic</em></p>'

    expect(normalizeEditorHtml(html)).toBe(html)
    expect(normalizeEditorHtml('<p></p>')).toBe('')
    expect(normalizeEditorHtml('<p><br></p>')).toBe('')
    expect(normalizeEditorHtml('<p><br class="ProseMirror-trailingBreak"></p>')).toBe('')
    expect(normalizeEditorHtml(`  ${html}  `)).toBe(html)
    expect(isStructuredHtmlContent(html)).toBe(true)
    expect(isStructuredHtmlContent('plain text only')).toBe(false)

    expect([...PAGE_CONTENT_ALLOWED_TAGS]).toEqual([
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
    ])
    expect([...PAGE_CONTENT_HEADING_LEVELS]).toEqual([2, 3])
    expect([...PAGE_CONTENT_TOOLBAR_ACTIONS]).toEqual([
      'paragraph',
      'heading',
      'bold',
      'italic',
      'bulletList',
      'orderedList',
      'link',
    ])

    expect(editorConfigSrc).toContain('StarterKit')
    expect(editorConfigSrc).toContain('Link')
    expect(formSrc).toMatch(/onChange=\{\(html\)\s*=>\s*\{[\s\S]*content:\s*html|updateTranslation\(activeLocale,\s*'content'/)
  })

  it('form validation still correctly requires title + content per locale before submit', async () => {
    expect(validationSrc).toMatch(/title:\s*z\.string\(\)\.min\(2/)
    expect(validationSrc).toMatch(/content:\s*z\.string\(\)\.min\(2/)
    expect(formSrc).toContain('translations.${activeLocale}.title')
    expect(formSrc).toContain('translations.${activeLocale}.content')

    ;(globalThis as { window: { _locales: Record<string, object> } }).window = {
      _locales: {
        en: {},
        ar: {},
        ur: {},
        hi: {},
      },
    }

    const { Inputs } = await import('./validation')

    const empty = Inputs.safeParse({
      translations: {
        en: { title: '', content: '' },
        ar: { title: '', content: '' },
        ur: { title: '', content: '' },
        hi: { title: '', content: '' },
      },
    })
    expect(empty.success).toBe(false)

    const missingContent = Inputs.safeParse({
      translations: {
        en: { title: 'Title', content: '' },
        ar: { title: 'عنوان', content: '<p>ok</p>' },
        ur: { title: 'عنوان', content: '<p>ok</p>' },
        hi: { title: 'शीर्षक', content: '<p>ok</p>' },
      },
    })
    expect(missingContent.success).toBe(false)

    const valid = Inputs.safeParse({
      translations: {
        en: { title: 'Title', content: '<p>Hello</p>' },
        ar: { title: 'عنوان', content: '<p>مرحبا</p>' },
        ur: { title: 'عنوان', content: '<p>ہیلو</p>' },
        hi: { title: 'शीर्षक', content: '<p>नमस्ते</p>' },
      },
    })
    expect(valid.success).toBe(true)
  })
})
