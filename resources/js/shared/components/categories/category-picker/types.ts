export type CategoryTreeNode = {
  id: number;
  title: string;
  icon: string;
  parent_id: number | null;
  has_children: boolean;
  children?: CategoryTreeNode[];
};

/** Controlled value shape — ids only; display fields come from the tree. */
export type CategoryPickerValueItem = {
  id: number;
};

/** Emitted by onChange with display fields for call-site chips / form maps. */
export type CategoryPickerSelection = {
  id: number;
  title: string;
  icon: string;
};

export type CategoryPickerProps = {
  provider_type_id?: string | number | null;
  value: readonly CategoryPickerValueItem[];
  onChange: (next: CategoryPickerSelection[]) => void;
  className?: string;
};

export type FlatLeafMatch = {
  leaf: CategoryTreeNode;
  root: CategoryTreeNode;
  /** Intermediate nodes between root and leaf (excluding both). */
  ancestors: CategoryTreeNode[];
  breadcrumb: string;
};

/** Registration wizard — one shared content width for every step and the nav row. */
export const REGISTRATION_STEP_CONTAINER_CLASS = 'w-100 mw-xl-850px mw-xxl-950px';
/** Shared layout constants (Bootstrap/Metronic utility classes). */
/** ~2–3 wrapped chip rows before internal scroll; keeps panels anchored below. */
export const CATEGORY_PICKER_CHIP_ROW_MAX_HEIGHT_CLASS = 'mh-125px';
export const CATEGORY_PICKER_CHIP_ROW_SCROLL_CLASS = 'overflow-y-auto';
export const CATEGORY_PICKER_CHIP_ROW_SURFACE_CLASS =
  'border border-gray-200 rounded p-3 bg-light';
/** Fixed panel height so overflow scroll works inside flex layouts (mh-* alone fails without min-height: 0). */
export const CATEGORY_PICKER_PANEL_HEIGHT_CLASS = 'h-350px';
export const CATEGORY_PICKER_PANEL_SCROLL_CLASS = 'overflow-y-auto overflow-x-hidden';
export const CATEGORY_PICKER_ROOT_LIST_WIDTH_CLASS = 'w-100 w-md-325px w-lg-350px';
export const CATEGORY_PICKER_PANEL_SURFACE_CLASS = 'border border-gray-200 rounded bg-body';
export const CATEGORY_PICKER_FLEX_SCROLL_CHILD_CLASS = 'category-picker-flex-scroll-child';
export const CATEGORY_PICKER_FLEX_SHRINK_0_CLASS = 'flex-shrink-0';
export const CATEGORY_PICKER_TRUNCATE_LABEL_CLASS = 'text-truncate min-w-0';
export const CATEGORY_PICKER_CHIP_BADGE_CLASS = 'badge badge-light-primary d-inline-flex align-items-center gap-2 py-2 px-3';
export const CATEGORY_PICKER_COUNT_BADGE_CLASS = 'badge badge-circle badge-primary';

export function nodeHasChildren(node: CategoryTreeNode): boolean {
  return Boolean(node.has_children) || (node.children?.length ?? 0) > 0;
}

export function collectLeaves(node: CategoryTreeNode): CategoryTreeNode[] {
  if (!nodeHasChildren(node)) {
    return [node];
  }

  return (node.children ?? []).flatMap((child) => collectLeaves(child));
}

export function flattenLeafMatches(roots: readonly CategoryTreeNode[]): FlatLeafMatch[] {
  const matches: FlatLeafMatch[] = [];

  const walk = (
    node: CategoryTreeNode,
    root: CategoryTreeNode,
    ancestors: CategoryTreeNode[],
  ): void => {
    if (!nodeHasChildren(node)) {
      const pathTitles =
        node.id === root.id
          ? [root.title]
          : [root.title, ...ancestors.map((a) => a.title)];

      matches.push({
        leaf: node,
        root,
        ancestors,
        breadcrumb: pathTitles.join(' > '),
      });
      return;
    }

    for (const child of node.children ?? []) {
      const nextAncestors =
        node.id === root.id ? ancestors : [...ancestors, node];
      walk(child, root, nextAncestors);
    }
  };

  for (const root of roots) {
    if (!nodeHasChildren(root)) {
      matches.push({
        leaf: root,
        root,
        ancestors: [],
        breadcrumb: root.title,
      });
      continue;
    }

    for (const child of root.children ?? []) {
      walk(child, root, []);
    }
  }

  return matches;
}

export function filterLeafMatches(
  matches: readonly FlatLeafMatch[],
  query: string,
): FlatLeafMatch[] {
  const normalized = query.trim().toLowerCase();
  if (!normalized) {
    return [...matches];
  }

  return matches.filter((match) => {
    const haystack = `${match.leaf.title} ${match.breadcrumb}`.toLowerCase();
    return haystack.includes(normalized);
  });
}

export function selectionFromIds(
  ids: readonly number[],
  leafIndex: ReadonlyMap<number, CategoryTreeNode>,
): CategoryPickerSelection[] {
  const result: CategoryPickerSelection[] = [];
  for (const id of ids) {
    const leaf = leafIndex.get(id);
    if (!leaf) {
      continue;
    }
    result.push({
      id: leaf.id,
      title: leaf.title,
      icon: leaf.icon,
    });
  }
  return result;
}

export function buildLeafIndex(roots: readonly CategoryTreeNode[]): Map<number, CategoryTreeNode> {
  const index = new Map<number, CategoryTreeNode>();
  for (const match of flattenLeafMatches(roots)) {
    index.set(match.leaf.id, match.leaf);
  }
  return index;
}

export function countSelectedUnder(
  node: CategoryTreeNode,
  selectedIds: ReadonlySet<number>,
): number {
  return collectLeaves(node).filter((leaf) => selectedIds.has(leaf.id)).length;
}

export type TriState = 'none' | 'some' | 'all';

export function triStateForNode(
  node: CategoryTreeNode,
  selectedIds: ReadonlySet<number>,
): TriState {
  const leaves = collectLeaves(node);
  if (leaves.length === 0) {
    return 'none';
  }
  const selectedCount = leaves.filter((leaf) => selectedIds.has(leaf.id)).length;
  if (selectedCount === 0) {
    return 'none';
  }
  if (selectedCount === leaves.length) {
    return 'all';
  }
  return 'some';
}
