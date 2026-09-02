import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const profileSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8')
const deactivateSrc = readFileSync(
  join(__dirname, '../../../layouts/accounts/components/settings/cards/DeactivateAccount.tsx'),
  'utf8',
)

describe('Profile self-deactivation control', () => {
  it('Profile page shows a real, working deactivate control that calls the new backend endpoint with a confirmation dialog', () => {
    expect(profileSrc).toContain('DeactivateAccount')

    expect(deactivateSrc).toMatch(/AuthController\.deactivate|deactivate\(\)/)
    expect(deactivateSrc).toMatch(/Swal|swal\.fire|are_you_sure/)
    expect(deactivateSrc).toMatch(/confirmed/)
    expect(deactivateSrc).not.toMatch(/loading\] = useState\(false\)/)
  })
})
