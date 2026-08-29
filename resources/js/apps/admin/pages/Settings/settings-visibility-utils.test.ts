import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import {
  settingsVisibilityBadgeClass,
  settingsVisibilityBadgeLabel,
} from './settings-visibility-utils'

const __dirname = dirname(fileURLToPath(import.meta.url))

describe('Settings Index visibility control', () => {
  it('the Dashboard Settings page shows is_public as a read-only badge (Public/Private), not an editable toggle', () => {
    const settingsSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8')

    expect(settingsSrc).toContain('settingsVisibilityBadgeLabel')
    expect(settingsSrc).toContain('badge')
    expect(settingsSrc).not.toContain('type="switch"')
    expect(settingsSrc).not.toMatch(/FormCheck/)
    expect(settingsSrc).not.toMatch(/setData\(\s*['"]is_public['"]/)
    expect(settingsSrc).not.toMatch(/form\.data\.is_public/)
  })

  it('badge helpers map boolean is_public to Public/Private labels', () => {
    const t = (key: string) => (key === 'public' ? 'Public' : key === 'private' ? 'Private' : key)

    expect(settingsVisibilityBadgeLabel(true, t)).toBe('Public')
    expect(settingsVisibilityBadgeLabel(false, t)).toBe('Private')
    expect(settingsVisibilityBadgeClass(true)).toContain('success')
    expect(settingsVisibilityBadgeClass(false)).toContain('dark')
  })
})

describe('Settings history UI', () => {
  it('each setting field has a "View history" action showing past changes (old -> new, actor, timestamp) in a simple list, mirroring the Timeline style used on Guarantor Show', () => {
    const settingsSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8')
    const guarantorSrc = readFileSync(join(__dirname, '..', 'Guarantor', 'Show.tsx'), 'utf8')

    expect(settingsSrc).toContain('view_history')
    expect(settingsSrc).toContain('SettingHistoryModal')
    expect(settingsSrc).toContain('old_content')
    expect(settingsSrc).toContain('new_content')
    expect(settingsSrc).toContain('actor')
    expect(settingsSrc).toContain('created_at')

    // Reuse the same timeline rail pattern from Guarantor Show
    expect(guarantorSrc).toContain('rounded-circle border border-3')
    expect(settingsSrc).toContain('rounded-circle border border-3')
  })
})
