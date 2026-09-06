/**
 * Pure helpers for PageFilterBar search clear — unit-tested without a DOM.
 */

/** Clear (X) is shown only when the search box has any characters. */
export function shouldShowSearchClearButton(value: string): boolean {
  return value.length > 0;
}

/**
 * Clears search immediately (does not wait for Enter).
 * Returns the emptied value for controlled-input state updates.
 */
export function clearSearchFilterValue(
  name: string,
  onFilterChange: (name: string, value: string) => void,
): string {
  onFilterChange(name, '');
  return '';
}
