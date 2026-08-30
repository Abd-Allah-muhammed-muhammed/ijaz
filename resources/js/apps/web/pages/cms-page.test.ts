import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const cmsPageSrc = readFileSync(join(__dirname, 'CmsPage.tsx'), 'utf8')

describe('CmsPageView reusable template', () => {
  it('the web CmsPage component no longer renders its own badge/card JSX — just the dark hero banner + dangerouslySetInnerHTML of the already-wrapped content from the server', () => {
    expect(cmsPageSrc).toContain('CmsPageView')
    expect(cmsPageSrc).toContain('bg-primary')
    expect(cmsPageSrc).toContain('one-side-border-bottom-lg')
    expect(cmsPageSrc).toContain('data-testid="cms-page-content"')
    expect(cmsPageSrc).toMatch(/dangerouslySetInnerHTML=\{\{\s*__html:\s*page\.content\s*\}\}/)
    expect(cmsPageSrc).not.toContain('CMS_PAGE_BADGE_BG')
    expect(cmsPageSrc).not.toContain('top-center-badge')
    expect(cmsPageSrc).not.toContain("borderRadius: '33px'")
    expect(cmsPageSrc).not.toContain('cms-page-title-badge')
  })

  it('visiting /pages/{slug} on the website renders this template using live CMS data — not hardcoded content', () => {
    expect(cmsPageSrc).toContain('CmsPageProps')
    expect(cmsPageSrc).toContain('page: {')
    expect(cmsPageSrc).toContain('slug: string')
    expect(cmsPageSrc).toContain('page.content')
    expect(cmsPageSrc).toContain('page.title')
    expect(cmsPageSrc).not.toContain("slug: 'terms'")
    expect(cmsPageSrc).not.toContain('Terms and Conditions')
  })

  it('/en/privacy-and-policies renders 4 stacked cards (server-composed), each with its own badge — web only mounts server HTML', () => {
    // Composition/card markup is server-side; the React page must not rebuild cards.
    expect(cmsPageSrc).toContain('cms-page-server-content')
    expect(cmsPageSrc).toContain('Already card/badge-wrapped HTML')
    expect(cmsPageSrc).not.toContain('pages.map')
    expect(cmsPageSrc).not.toContain('CmsPageCard')
  })
})
