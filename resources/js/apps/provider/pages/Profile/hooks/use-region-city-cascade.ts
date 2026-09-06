import { useMemo } from 'react';
import type { City } from '@/shared/types/models';

/** Pure: city options scoped to the selected region (empty when no region). */
export function filterCitiesByRegion(
  cities: City[],
  regionId: number | null,
): City[] {
  if (!regionId) {
    return [];
  }

  return cities.filter((city) => city.region_id === regionId);
}

/**
 * Pure: selecting a region always clears city (matches legacy Form behavior).
 */
export function applyRegionChange(regionId: number | null): {
  region_id: number | null;
  city_id: null;
} {
  return {
    region_id: regionId,
    city_id: null,
  };
}

export function useRegionCityCascade(
  cities: City[],
  regionId: number | null,
) {
  const citiesForRegion = useMemo(
    () => filterCitiesByRegion(cities, regionId),
    [cities, regionId],
  );

  return {
    citiesForRegion,
    applyRegionChange,
  };
}
