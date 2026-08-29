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
  it('the Settings page no longer renders any "View history" trigger, modal, or related UI for any field', () => {
    const settingsSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8')

    expect(settingsSrc).not.toContain('view_history')
    expect(settingsSrc).not.toContain('SettingHistoryModal')
    expect(settingsSrc).not.toContain('onViewHistory')
    expect(settingsSrc).not.toContain('historyKey')
    expect(settingsSrc).not.toContain('old_content')
    expect(settingsSrc).not.toContain('new_content')
    expect(settingsSrc).not.toMatch(/\.history\(/)
  })
})
