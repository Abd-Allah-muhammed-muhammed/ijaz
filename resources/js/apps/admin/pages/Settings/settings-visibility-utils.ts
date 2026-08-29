export function settingsVisibilityBadgeLabel(
  isPublic: boolean,
  t: (key: string) => string,
): string {
  return isPublic ? t('public') : t('private')
}

export function settingsVisibilityBadgeClass(isPublic: boolean): string {
  return isPublic ? 'badge-light-success' : 'badge-light-dark'
}
