import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'
import { groupSettingsBySection } from './settings-section-utils'

const __dirname = dirname(fileURLToPath(import.meta.url))

describe('groupSettingsBySection', () => {
  it('Settings page groups fields by section within a tab, with a generic "Other" heading for null/uncategorized sections', () => {
    const buckets = groupSettingsBySection([
      { key: 'email', type: 'text', section: 'contact' },
      { key: 'facebook', type: 'text', section: 'social' },
      { key: 'offer_note', type: 'textarea', section: null },
      { key: 'future_key', type: 'text', section: null },
      { key: 'phone', type: 'text', section: 'contact' },
    ])

    expect(buckets.map((b) => b.section)).toEqual(['contact', 'social', null])

    expect(buckets[0].textRows.map((r) => r.key)).toEqual(['email', 'phone'])
    expect(buckets[1].textRows.map((r) => r.key)).toEqual(['facebook'])
    expect(buckets[2].textRows.map((r) => r.key)).toEqual(['future_key'])
    expect(buckets[2].textareaRows.map((r) => r.key)).toEqual(['offer_note'])

    const allKeys = buckets.flatMap((b) => [...b.textRows, ...b.textareaRows].map((r) => r.key))
    expect(allKeys.sort()).toEqual(['email', 'facebook', 'future_key', 'offer_note', 'phone'].sort())
  })
})

describe('Settings Index visibility control', () => {
  it('the "Visible in public API" control renders as a FormCheck switch (matching Bank\'s is_active pattern), not a plain checkbox', () => {
    const settingsSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8')
    const banksSrc = readFileSync(
      join(__dirname, '..', 'Banks', 'Form.tsx'),
      'utf8',
    )

    expect(banksSrc).toContain('type="switch"')
    expect(settingsSrc).toContain('type="switch"')
    expect(settingsSrc).not.toMatch(/FormCheck[\s\S]*?type=["']checkbox["']/)
  })
})
