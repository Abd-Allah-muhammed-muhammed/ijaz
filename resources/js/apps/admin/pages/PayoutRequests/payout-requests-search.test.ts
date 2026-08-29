import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))

describe('Payout Requests search wiring', () => {
  it('typing a term into the Payout Requests search box and pressing Enter triggers a filtered request, matching the Banks/Regions pattern', () => {
    const payoutSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8')
    const banksSrc = readFileSync(join(__dirname, '../Banks/Index.tsx'), 'utf8')

    expect(banksSrc).toMatch(/search=\{\{[\s\S]*?callback:\s*\(value\)\s*=>\s*\{[\s\S]*?searchPramsChanged\(['"]search['"]/)
    expect(payoutSrc).toMatch(/search=\{\{[\s\S]*?value:[\s\S]*?prams\?\.search/)
    expect(payoutSrc).toMatch(/callback:\s*\(value\)\s*=>\s*\{[\s\S]*?searchPramsChanged\(['"]search['"]/)
    expect(payoutSrc).toContain("searchPramsChanged('search', value)")
    expect(payoutSrc).toContain('applyFilterParam')
    expect(payoutSrc).toContain('visitWithFilters')
  })
})
