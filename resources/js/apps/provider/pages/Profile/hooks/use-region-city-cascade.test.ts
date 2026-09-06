import { describe, expect, it } from 'vitest';
import {
  applyRegionChange,
  filterCitiesByRegion,
} from '@/apps/provider/pages/Profile/hooks/use-region-city-cascade';
import type { City } from '@/shared/types/models';

const cities = [
  { id: 1, region_id: 10, title: 'Riyadh City' },
  { id: 2, region_id: 10, title: 'Diriyah' },
  { id: 3, region_id: 20, title: 'Jeddah' },
] as City[];

describe('use-region-city-cascade helpers', () => {
  it('scopes city options to the selected region', () => {
    expect(filterCitiesByRegion(cities, 10).map((city) => city.id)).toEqual([
      1, 2,
    ]);
    expect(filterCitiesByRegion(cities, 20).map((city) => city.id)).toEqual([
      3,
    ]);
  });

  it('returns an empty city list when no region is selected', () => {
    expect(filterCitiesByRegion(cities, null)).toEqual([]);
  });

  it('clears city_id whenever the region changes', () => {
    expect(applyRegionChange(10)).toEqual({ region_id: 10, city_id: null });
    expect(applyRegionChange(null)).toEqual({ region_id: null, city_id: null });
  });
});
