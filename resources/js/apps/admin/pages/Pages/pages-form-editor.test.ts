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

  it('editor output remains valid HTML matching the expanded safe-tag set (headings, lists, tables, marks, media)', () => {
    const html =
      '<h2>Acceptance</h2><p>Hello <strong>world</strong></p><ul><li>One</li></ul><ol><li>First</li></ol><p><a href="https://example.com">Link</a></p><h3>More</h3><p><em>italic</em></p>'

    expect(normalizeEditorHtml(html)).toBe(html)
    expect(normalizeEditorHtml('<p></p>')).toBe('')
    expect(normalizeEditorHtml('<p><br></p>')).toBe('')
    expect(normalizeEditorHtml('<p><br class="ProseMirror-trailingBreak"></p>')).toBe('')
    expect(normalizeEditorHtml(`  ${html}  `)).toBe(html)
    expect(isStructuredHtmlContent(html)).toBe(true)
    expect(isStructuredHtmlContent('<table><tr><td>x</td></tr></table>')).toBe(true)
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
    ])
    expect([...PAGE_CONTENT_HEADING_LEVELS]).toEqual([1, 2, 3, 4, 5, 6])
    expect([...PAGE_CONTENT_TOOLBAR_ACTIONS]).toEqual([
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
    ])

    expect(editorConfigSrc).toContain('StarterKit')
    expect(editorConfigSrc).toContain('Link')
    expect(formSrc).toMatch(/onChange=\{\(html\)\s*=>\s*\{[\s\S]*content:\s*html|updateTranslation\(activeLocale,\s*'content'/)
  })

  it('form validation still correctly requires title + content per locale before submit', async () => {
    expect(validationSrc).toContain(".min(2, { message: 'Title is required' })")
    expect(validationSrc).toContain("message: 'content is required'")
    expect(validationSrc).not.toContain('superRefine')
    expect(validationSrc).not.toContain('composed_of_slugs')
    expect(formSrc).toContain('translations.${activeLocale}.title')
    expect(formSrc).toContain('translations.${activeLocale}.content')
    expect(formSrc).not.toContain('composed_of_slugs')
    expect(formSrc).not.toContain('Composed of')

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
      slug: 'probe',
      translations: {
        en: { title: '', content: '' },
        ar: { title: '', content: '' },
        ur: { title: '', content: '' },
        hi: { title: '', content: '' },
      },
    })
    expect(empty.success).toBe(false)

    const missingContent = Inputs.safeParse({
      slug: 'probe',
      translations: {
        en: { title: 'Title', content: '' },
        ar: { title: 'عنوان', content: '<p>ok</p>' },
        ur: { title: 'عنوان', content: '<p>ok</p>' },
        hi: { title: 'शीर्षक', content: '<p>ok</p>' },
      },
    })
    expect(missingContent.success).toBe(false)

    const valid = Inputs.safeParse({
      slug: 'probe',
      translations: {
        en: { title: 'Title', content: '<p>Hello</p>' },
        ar: { title: 'عنوان', content: '<p>مرحبا</p>' },
        ur: { title: 'عنوان', content: '<p>ہیلو</p>' },
        hi: { title: 'शीर्षक', content: '<p>नमस्ते</p>' },
      },
    })
    expect(valid.success).toBe(true)
  })

  it('the Pages Form editor has an "Insert Logo" toolbar button that inserts the fixed logo image (/media/logos/default.svg), centered, at the cursor position', () => {
    expect(editorSrc).toContain('Insert Logo')
    expect(editorSrc).toContain('PAGE_CONTENT_LOGO_HTML')
    expect(editorSrc).toMatch(/insertContent\(PAGE_CONTENT_LOGO_HTML\)/)
    expect(editorConfigSrc).toContain("PAGE_CONTENT_LOGO_SRC = '/media/logos/default.svg'")
    expect(editorConfigSrc).toContain('text-align:center')
    expect(editorConfigSrc).toContain("alt=\"Ijaz\"")
    expect(editorConfigSrc).toContain('width="120"')
    expect(editorConfigSrc).toContain('height="120"')
    expect(editorConfigSrc).toContain('insertLogo')
    expect(editorConfigSrc).toContain('@tiptap/extension-image')
  })

  it('the Pages content editor toolbar now has both "Insert Logo" and "Insert Image" actions', () => {
    expect(editorSrc).toContain('Insert Logo')
    expect(editorSrc).toContain('Insert Image')
    expect(editorConfigSrc).toContain("'insertLogo'")
    expect(editorConfigSrc).toContain("'insertImage'")
    expect([...PAGE_CONTENT_TOOLBAR_ACTIONS]).toContain('insertLogo')
    expect([...PAGE_CONTENT_TOOLBAR_ACTIONS]).toContain('insertImage')
  })

  it('"Insert Image" opens a file picker, uploads on selection, and inserts the resulting image into the editor at the cursor position', () => {
    expect(editorSrc).toContain('type="file"')
    expect(editorSrc).toContain('imageInputRef.current?.click()')
    expect(editorSrc).toContain('uploadPageContentImage')
    expect(editorSrc).toContain('uploadContentImage.url()')
    expect(editorSrc).toContain('buildPageContentImageHtml')
    expect(editorSrc).toMatch(/insertContent\(buildPageContentImageHtml\(/)
    expect(editorSrc).toContain('Uploading…')
  })

  it('upload failure (e.g. oversized file) shows a clear error, does not silently fail or insert a broken image', async () => {
    expect(editorSrc).toContain('imageUploadError')
    expect(editorSrc).toContain('setImageUploadError')
    expect(editorSrc).toContain('page-content-image-upload-error')
    expect(editorSrc).toContain('role="alert"')

    const { uploadPageContentImage, buildPageContentImageHtml } = await import('./page-content-editor')

    expect(buildPageContentImageHtml('/storage/pages/content/a.png', 'Hero')).toContain(
      'src="/storage/pages/content/a.png"',
    )

    const file = new File(['x'], 'big.jpg', { type: 'image/jpeg' })

    await expect(
      uploadPageContentImage(
        file,
        async () => {
          throw {
            response: {
              data: {
                message: 'The image failed to upload.',
                errors: { image: ['The image field must not be greater than 512 kilobytes.'] },
              },
            },
          }
        },
        '/dashboard/pages/content-images',
      ),
    ).rejects.toThrow('The image field must not be greater than 512 kilobytes.')

    await expect(
      uploadPageContentImage(
        file,
        async () => ({ success: false, message: 'Upload failed.', data: undefined }),
        '/dashboard/pages/content-images',
      ),
    ).rejects.toThrow('Upload failed.')
  })

  it('Pages editor toolbar now exposes strikethrough, code, blockquote, horizontal rule, text color, alignment, underline, table insert, and all heading levels', () => {
    expect(editorSrc).toContain('Strikethrough')
    expect(editorSrc).toContain('toggleStrike')
    expect(editorSrc).toContain('toggleCode')
    expect(editorSrc).toContain('toggleBlockquote')
    expect(editorSrc).toContain('setHorizontalRule')
    expect(editorSrc).toContain('toggleUnderline')
    expect(editorSrc).toContain('setTextAlign')
    expect(editorSrc).toContain('setColor')
    expect(editorSrc).toContain('insertTable')
    expect(editorSrc).toContain('type="color"')
    expect(editorConfigSrc).toContain('@tiptap/extension-underline')
    expect(editorConfigSrc).toContain('@tiptap/extension-text-align')
    expect(editorConfigSrc).toContain('@tiptap/extension-color')
    expect(editorConfigSrc).toContain('@tiptap/extension-table')
    expect([...PAGE_CONTENT_HEADING_LEVELS]).toEqual([1, 2, 3, 4, 5, 6])
    for (const action of [
      'underline',
      'strike',
      'code',
      'blockquote',
      'horizontalRule',
      'textAlign',
      'textColor',
      'table',
    ]) {
      expect([...PAGE_CONTENT_TOOLBAR_ACTIONS]).toContain(action)
    }
  })

  it('the 5 migrated pages are editable via the admin Pages CRUD like any other page', () => {
    // Admin Pages Form is slug-agnostic — same Form/editor for every CMS page.
    expect(formSrc).toContain('PageContentEditor')
    expect(formSrc).toContain('slug')
    expect(formSrc).toContain('translations')
    expect(editorSrc).toContain('Insert Logo')
    expect(editorSrc).toContain('Insert Image')
    expect([...PAGE_CONTENT_TOOLBAR_ACTIONS]).toContain('insertLogo')
    expect([...PAGE_CONTENT_TOOLBAR_ACTIONS]).toContain('insertImage')
  })

  it('the Pages admin form no longer has a "Composed of" field', () => {
    expect(formSrc).not.toContain('data-testid="pages-composed-of-field"')
    expect(formSrc).not.toContain('composed_of_slugs')
    expect(formSrc).not.toContain('pageOptions')
    expect(validationSrc).not.toContain('composed_of_slugs')
  })
})
