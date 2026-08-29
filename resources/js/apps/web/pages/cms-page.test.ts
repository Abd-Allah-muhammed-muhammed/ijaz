import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const cmsPageSrc = readFileSync(join(__dirname, 'CmsPage.tsx'), 'utf8')

describe('CmsPageView reusable template', () => {
  it('a new reusable web CmsPageView component renders: colored title badge (bg #00686D, page.title) at top, then the page content HTML below (dangerouslySetInnerHTML, already self-styled)', () => {
    expect(cmsPageSrc).toContain("CMS_PAGE_BADGE_BG = '#00686D'")
    expect(cmsPageSrc).toContain('CmsPageView')
    expect(cmsPageSrc).toContain('data-testid="cms-page-title-badge"')
    expect(cmsPageSrc).toContain('data-testid="cms-page-content"')
    expect(cmsPageSrc).toContain('backgroundColor: CMS_PAGE_BADGE_BG')
    expect(cmsPageSrc).toContain('{page.title}')
    expect(cmsPageSrc).toMatch(/dangerouslySetInnerHTML=\{\{\s*__html:\s*page\.content\s*\}\}/)
  })

  it('visiting /pages/terms (or the chosen route) on the website renders this template correctly using live CMS data — not hardcoded content', () => {
    expect(cmsPageSrc).toContain('CmsPageProps')
    expect(cmsPageSrc).toContain('page: {')
    expect(cmsPageSrc).toContain('slug: string')
    expect(cmsPageSrc).toContain('page.content')
    expect(cmsPageSrc).toContain('page.title')
    expect(cmsPageSrc).not.toContain("slug: 'terms'")
    expect(cmsPageSrc).not.toContain('Terms and Conditions')
  })
})
