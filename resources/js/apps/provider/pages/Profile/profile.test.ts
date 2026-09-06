import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

const profileSrc = readFileSync(join(__dirname, 'Profile.tsx'), 'utf8');
const dangerSrc = readFileSync(
  join(__dirname, 'components/DangerZoneCard.tsx'),
  'utf8',
);
const deactivateSrc = readFileSync(
  join(
    __dirname,
    '../../layouts/accounts/components/settings/cards/DeactivateAccount.tsx',
  ),
  'utf8',
);

describe('Provider Profile redesign', () => {
  it('does not wrap Profile in AccountLayout wallet chrome', () => {
    expect(profileSrc).not.toContain('AccountLayout');
    expect(profileSrc).toContain('ProviderLayout');
    expect(profileSrc).toContain('ProfileIdentityHeader');
    expect(profileSrc).not.toContain('AccountMetrics');
  });

  it('composes card-based settings sections', () => {
    expect(profileSrc).toContain('LogoCard');
    expect(profileSrc).toContain('GeneralInfoCard');
    expect(profileSrc).toContain('PasswordCard');
    expect(profileSrc).toContain('CategoriesCard');
    expect(profileSrc).toContain('RequiredFilesCard');
    expect(profileSrc).toContain('DangerZoneCard');
  });

  it('Danger Zone reuses ConfirmDialog-based DeactivateAccount logic', () => {
    expect(dangerSrc).toContain('DeactivateAccount');
    expect(dangerSrc).toContain('embedded');
    expect(deactivateSrc).toContain('ConfirmDialog');
    expect(deactivateSrc).toMatch(/AuthController\.deactivate|deactivate\(\)/);
    expect(deactivateSrc).not.toMatch(/Swal|swal\.fire|withReactContent/);
  });
});
