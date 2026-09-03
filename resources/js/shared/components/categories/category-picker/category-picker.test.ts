import { describe, expect, it } from 'vitest';
import {
  buildLeafIndex,
  CATEGORY_PICKER_CHIP_ROW_MAX_HEIGHT_CLASS,
  CATEGORY_PICKER_CHIP_ROW_SCROLL_CLASS,
  CATEGORY_PICKER_PANEL_MAX_HEIGHT_CLASS,
  collectLeaves,
  filterLeafMatches,
  flattenLeafMatches,
  nodeHasChildren,
  selectionFromIds,
  triStateForNode,
  type CategoryTreeNode,
} from './types';

const leaf = (id: number, title: string, parent_id: number | null = null): CategoryTreeNode => ({
  id,
  title,
  icon: '',
  parent_id,
  has_children: false,
  children: [],
});

const branch = (
  id: number,
  title: string,
  children: CategoryTreeNode[],
  parent_id: number | null = null,
): CategoryTreeNode => ({
  id,
  title,
  icon: '',
  parent_id,
  has_children: true,
  children,
});

describe('category-picker tree helpers', () => {
  const rootA = branch(1, 'Root A', [
    branch(2, 'Sub A', [leaf(3, 'Leaf A1', 2), leaf(4, 'Leaf A2', 2)], 1),
    leaf(5, 'Direct Leaf', 1),
  ]);
  const rootB = branch(10, 'Root B', [leaf(11, 'Leaf B1', 10)]);

  it('collectLeaves walks nested branches and direct leaves', () => {
    expect(collectLeaves(rootA).map((n) => n.id)).toEqual([3, 4, 5]);
    expect(collectLeaves(leaf(99, 'Solo')).map((n) => n.id)).toEqual([99]);
  });

  it('flattenLeafMatches builds breadcrumbs across the tree', () => {
    const matches = flattenLeafMatches([rootA, rootB]);
    expect(matches.map((m) => ({ id: m.leaf.id, crumb: m.breadcrumb }))).toEqual([
      { id: 3, crumb: 'Root A > Sub A' },
      { id: 4, crumb: 'Root A > Sub A' },
      { id: 5, crumb: 'Root A' },
      { id: 11, crumb: 'Root B' },
    ]);
  });

  it('filterLeafMatches matches title or breadcrumb case-insensitively', () => {
    const matches = flattenLeafMatches([rootA, rootB]);
    expect(filterLeafMatches(matches, 'sub a').map((m) => m.leaf.id)).toEqual([3, 4]);
    expect(filterLeafMatches(matches, 'LEAF B').map((m) => m.leaf.id)).toEqual([11]);
    expect(filterLeafMatches(matches, '   ')).toHaveLength(matches.length);
  });

  it('triStateForNode reflects none / some / all', () => {
    const sub = rootA.children![0];
    expect(triStateForNode(sub, new Set())).toBe('none');
    expect(triStateForNode(sub, new Set([3]))).toBe('some');
    expect(triStateForNode(sub, new Set([3, 4]))).toBe('all');
  });

  it('selectionFromIds uses the leaf index for display fields', () => {
    const index = buildLeafIndex([rootA]);
    expect(selectionFromIds([5, 3, 999], index)).toEqual([
      { id: 5, title: 'Direct Leaf', icon: '' },
      { id: 3, title: 'Leaf A1', icon: '' },
    ]);
  });

  it('nodeHasChildren treats empty children as a leaf', () => {
    expect(nodeHasChildren(leaf(1, 'x'))).toBe(false);
    expect(nodeHasChildren(branch(1, 'x', [leaf(2, 'y')]))).toBe(true);
  });

  it('exports stable layout utility class constants', () => {
    expect(CATEGORY_PICKER_CHIP_ROW_MAX_HEIGHT_CLASS).toBe('mh-125px');
    expect(CATEGORY_PICKER_CHIP_ROW_SCROLL_CLASS).toBe('overflow-y-auto');
    expect(CATEGORY_PICKER_PANEL_MAX_HEIGHT_CLASS).toBe('mh-350px');
  });
});
