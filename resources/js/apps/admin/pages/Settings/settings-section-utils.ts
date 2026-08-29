export type SettingSectionKey = string | null

export type SettingRowLike = {
  key: string
  type: 'text' | 'textarea'
  section?: SettingSectionKey
}

export type SettingSectionBucket<T extends SettingRowLike> = {
  section: string | null
  textRows: T[]
  textareaRows: T[]
}

const KNOWN_SECTION_ORDER = ['contact', 'social'] as const

/**
 * Group settings by section for dashboard rendering.
 * Null/empty sections land in a final "Other" bucket — never dropped.
 * Within each section, text fields come first; textareas follow (full-width).
 */
export function groupSettingsBySection<T extends SettingRowLike>(rows: T[]): SettingSectionBucket<T>[] {
  const buckets = new Map<string, { textRows: T[]; textareaRows: T[] }>()

  for (const row of rows) {
    const sectionKey = row.section && row.section.trim() !== '' ? row.section : '__other__'
    if (!buckets.has(sectionKey)) {
      buckets.set(sectionKey, { textRows: [], textareaRows: [] })
    }
    const bucket = buckets.get(sectionKey)!
    if (row.type === 'textarea') {
      bucket.textareaRows.push(row)
    } else {
      bucket.textRows.push(row)
    }
  }

  const ordered: SettingSectionBucket<T>[] = []

  for (const known of KNOWN_SECTION_ORDER) {
    const bucket = buckets.get(known)
    if (bucket && (bucket.textRows.length > 0 || bucket.textareaRows.length > 0)) {
      ordered.push({ section: known, ...bucket })
      buckets.delete(known)
    }
  }

  for (const [key, bucket] of buckets) {
    if (key === '__other__') {
      continue
    }
    if (bucket.textRows.length > 0 || bucket.textareaRows.length > 0) {
      ordered.push({ section: key, ...bucket })
    }
  }

  const other = buckets.get('__other__')
  if (other && (other.textRows.length > 0 || other.textareaRows.length > 0)) {
    ordered.push({ section: null, ...other })
  }

  return ordered
}
