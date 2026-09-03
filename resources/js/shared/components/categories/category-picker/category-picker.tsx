import {
  useEffect,
  useMemo,
  useRef,
  useState,
  type ChangeEvent,
  type ReactNode,
} from 'react';
import { useTranslation } from 'react-i18next';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faChevronDown, faChevronLeft, faChevronRight, faXmark } from '@fortawesome/free-solid-svg-icons';
import { whenLocale } from '@/shared/helpers/general';
import { EmptyState } from '@/shared/components/ui';
import { useCategoryTree } from './use-category-tree';
import './category-picker.css';
import {
  buildLeafIndex,
  CATEGORY_PICKER_CHIP_BADGE_CLASS,
  CATEGORY_PICKER_CHIP_ROW_MAX_HEIGHT_CLASS,
  CATEGORY_PICKER_CHIP_ROW_SCROLL_CLASS,
  CATEGORY_PICKER_CHIP_ROW_SURFACE_CLASS,
  CATEGORY_PICKER_COUNT_BADGE_CLASS,
  CATEGORY_PICKER_FLEX_SCROLL_CHILD_CLASS,
  CATEGORY_PICKER_FLEX_SHRINK_0_CLASS,
  CATEGORY_PICKER_PANEL_HEIGHT_CLASS,
  CATEGORY_PICKER_PANEL_SCROLL_CLASS,
  CATEGORY_PICKER_PANEL_SURFACE_CLASS,
  CATEGORY_PICKER_ROOT_LIST_WIDTH_CLASS,
  CATEGORY_PICKER_TRUNCATE_LABEL_CLASS,
  collectLeaves,
  countSelectedUnder,
  filterLeafMatches,
  flattenLeafMatches,
  nodeHasChildren,
  selectionFromIds,
  triStateForNode,
  type CategoryPickerProps,
  type CategoryPickerSelection,
  type CategoryTreeNode,
  type TriState,
} from './types';

function TruncatedLabel({ text, className }: { text: string; className?: string }) {
  const labelClassName = [CATEGORY_PICKER_TRUNCATE_LABEL_CLASS, className]
    .filter(Boolean)
    .join(' ');

  return (
    <span className={labelClassName} title={text}>
      {text}
    </span>
  );
}

function IndeterminateCheckbox({
  id,
  checked,
  triState,
  onChange,
  ariaLabel,
}: {
  id: string;
  checked: boolean;
  triState: TriState;
  onChange: (checked: boolean) => void;
  ariaLabel: string;
}) {
  const ref = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (ref.current) {
      ref.current.indeterminate = triState === 'some';
    }
  }, [triState]);

  return (
    <span className={`form-check form-check-custom form-check-solid m-0 ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
      <input
        ref={ref}
        id={id}
        type="checkbox"
        className="form-check-input"
        checked={checked}
        aria-label={ariaLabel}
        onChange={(event: ChangeEvent<HTMLInputElement>) => {
          onChange(event.target.checked);
        }}
      />
    </span>
  );
}

export default function CategoryPicker({
  provider_type_id,
  value,
  onChange,
  className,
}: CategoryPickerProps) {
  const { t } = useTranslation();
  const treeQuery = useCategoryTree(provider_type_id);
  const roots = treeQuery.data ?? [];

  const [activeRootId, setActiveRootId] = useState<number | null>(null);
  const [expandedSubIds, setExpandedSubIds] = useState<Set<number>>(() => new Set());
  const [search, setSearch] = useState('');

  const leafIndex = useMemo(() => buildLeafIndex(roots), [roots]);
  const allLeafMatches = useMemo(() => flattenLeafMatches(roots), [roots]);
  const selectedIds = useMemo(
    () => new Set(value.map((item) => item.id)),
    [value],
  );

  useEffect(() => {
    if (roots.length === 0) {
      setActiveRootId(null);
      return;
    }
    setActiveRootId((current) => {
      if (current !== null && roots.some((root) => root.id === current)) {
        return current;
      }
      return roots[0]?.id ?? null;
    });
  }, [roots]);

  const activeRoot = useMemo(
    () => roots.find((root) => root.id === activeRootId) ?? null,
    [roots, activeRootId],
  );

  const searchResults = useMemo(
    () => filterLeafMatches(allLeafMatches, search),
    [allLeafMatches, search],
  );

  const isSearching = search.trim().length > 0;

  const emitSelection = (nextIds: Set<number>) => {
    onChange(selectionFromIds([...nextIds], leafIndex));
  };

  const toggleLeaf = (leafId: number, checked: boolean) => {
    const next = new Set(selectedIds);
    if (checked) {
      next.add(leafId);
    } else {
      next.delete(leafId);
    }
    emitSelection(next);
  };

  const applyTriStateToggle = (node: CategoryTreeNode, checked: boolean) => {
    const next = new Set(selectedIds);
    for (const leaf of collectLeaves(node)) {
      if (checked) {
        next.add(leaf.id);
      } else {
        next.delete(leaf.id);
      }
    }
    emitSelection(next);
  };

  const removeSelection = (id: number) => {
    const next = new Set(selectedIds);
    next.delete(id);
    emitSelection(next);
  };

  const toggleExpanded = (subId: number) => {
    setExpandedSubIds((prev) => {
      const next = new Set(prev);
      if (next.has(subId)) {
        next.delete(subId);
      } else {
        next.add(subId);
      }
      return next;
    });
  };

  const expandChevron = whenLocale<ReactNode>(
    'ar',
    () => <FontAwesomeIcon icon={faChevronLeft} className="fs-7" />,
    () => <FontAwesomeIcon icon={faChevronRight} className="fs-7" />,
  );

  const selectedChips: CategoryPickerSelection[] = selectionFromIds(
    [...selectedIds],
    leafIndex,
  );

  const rootClassName = ['category-picker', className].filter(Boolean).join(' ');

  if (treeQuery.isError) {
    return (
      <div className={rootClassName}>
        <EmptyState compact title={t('error_loading_data')} />
      </div>
    );
  }

  if (treeQuery.isLoading) {
    return (
      <div className={rootClassName}>
        <div className="text-center text-muted py-10">{t('loading')}</div>
      </div>
    );
  }

  const panelScrollClassName = [
    CATEGORY_PICKER_PANEL_SURFACE_CLASS,
    CATEGORY_PICKER_PANEL_HEIGHT_CLASS,
    CATEGORY_PICKER_PANEL_SCROLL_CLASS,
    CATEGORY_PICKER_FLEX_SCROLL_CHILD_CLASS,
  ].join(' ');

  return (
    <div className={rootClassName}>
      <div className="mb-5">
        <label className="form-label" htmlFor="category-picker-search">
          {t('search')}
        </label>
        <input
          id="category-picker-search"
          type="search"
          className="form-control form-control-solid"
          placeholder={t('search')}
          value={search}
          onChange={(event) => setSearch(event.currentTarget.value)}
        />
      </div>

      {selectedChips.length > 0 && (
        <div
          className={`d-flex flex-wrap align-content-start gap-2 mb-5 ${CATEGORY_PICKER_CHIP_ROW_SURFACE_CLASS} ${CATEGORY_PICKER_CHIP_ROW_MAX_HEIGHT_CLASS} ${CATEGORY_PICKER_CHIP_ROW_SCROLL_CLASS}`}
          aria-label={t('categories')}
        >
          {selectedChips.map((chip) => (
            <span key={`chip-${chip.id}`} className={CATEGORY_PICKER_CHIP_BADGE_CLASS}>
              {chip.icon ? (
                <img src={chip.icon} alt="" className="w-20px h-20px rounded" />
              ) : null}
              <span>{chip.title}</span>
              <button
                type="button"
                className="btn btn-icon btn-sm btn-active-color-danger p-0"
                aria-label={`${t('remove')} ${chip.title}`}
                onClick={() => removeSelection(chip.id)}
              >
                <FontAwesomeIcon icon={faXmark} />
              </button>
            </span>
          ))}
        </div>
      )}

      {isSearching ? (
        <div className={`${panelScrollClassName} p-4`}>
          {searchResults.length === 0 ? (
            <EmptyState compact title={t('no_matching_records_found')} />
          ) : (
            <ul className="list-unstyled mb-0">
              {searchResults.map((match) => {
                const checked = selectedIds.has(match.leaf.id);
                return (
                  <li
                    key={`search-leaf-${match.leaf.id}`}
                    className="d-flex align-items-center justify-content-between gap-3 py-3 border-bottom border-gray-100"
                  >
                    <label
                      htmlFor={`search-leaf-check-${match.leaf.id}`}
                      className="d-flex align-items-center gap-3 flex-grow-1 min-w-0 cursor-pointer mb-0 overflow-hidden"
                    >
                      {match.leaf.icon ? (
                        <span className={`symbol symbol-40px ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                          <span className="symbol-label bg-light-primary">
                            <img
                              src={match.leaf.icon}
                              alt=""
                              className="w-100 h-100"
                            />
                          </span>
                        </span>
                      ) : null}
                      <span className="d-flex flex-column min-w-0 flex-grow-1 overflow-hidden">
                        <TruncatedLabel text={match.leaf.title} className="fw-bold text-gray-900" />
                        <TruncatedLabel text={match.breadcrumb} className="text-muted fs-7" />
                      </span>
                    </label>
                    <span className={`form-check form-check-custom form-check-solid m-0 ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                      <input
                        id={`search-leaf-check-${match.leaf.id}`}
                        type="checkbox"
                        className="form-check-input"
                        checked={checked}
                        onChange={(event) => {
                          toggleLeaf(match.leaf.id, event.target.checked);
                        }}
                      />
                    </span>
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      ) : (
        <div className={`d-flex flex-column flex-md-row gap-4 gap-md-5 min-w-0 ${CATEGORY_PICKER_FLEX_SCROLL_CHILD_CLASS}`}>
          <div
            className={`${CATEGORY_PICKER_ROOT_LIST_WIDTH_CLASS} ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS} ${panelScrollClassName}`}
            role="listbox"
            aria-label={t('categories')}
          >
            {roots.length === 0 ? (
              <EmptyState compact title={t('no_matching_records_found')} />
            ) : (
              <ul className="list-unstyled mb-0">
                {roots.map((root) => {
                  const selectedCount = countSelectedUnder(root, selectedIds);
                  const isActive = root.id === activeRootId;
                  return (
                    <li key={`root-${root.id}`}>
                      <button
                        type="button"
                        role="option"
                        aria-selected={isActive}
                        className={`btn btn-flush w-100 d-flex align-items-center gap-3 px-4 py-4 text-start overflow-hidden ${
                          isActive ? 'bg-light-primary' : ''
                        }`}
                        onClick={() => setActiveRootId(root.id)}
                      >
                        <span className="d-flex align-items-center gap-3 min-w-0 flex-grow-1 overflow-hidden">
                          {root.icon ? (
                            <span className={`symbol symbol-35px ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                              <span className="symbol-label bg-light">
                                <img src={root.icon} alt="" className="w-100 h-100" />
                              </span>
                            </span>
                          ) : null}
                          <TruncatedLabel text={root.title} className="fw-semibold text-gray-900" />
                        </span>
                        {selectedCount > 0 ? (
                          <span className={`${CATEGORY_PICKER_COUNT_BADGE_CLASS} ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                            {selectedCount}
                          </span>
                        ) : null}
                      </button>
                    </li>
                  );
                })}
              </ul>
            )}
          </div>

          <div className={`flex-grow-1 min-w-0 ${panelScrollClassName} p-4`}>
            {!activeRoot ? (
              <EmptyState compact title={t('no_matching_records_found')} />
            ) : (activeRoot.children ?? []).length === 0 ? (
              <EmptyState compact title={t('no_matching_records_found')} />
            ) : (
              <ul className="list-unstyled mb-0">
                    {(activeRoot.children ?? []).map((child) => {
                      if (!nodeHasChildren(child)) {
                        const checked = selectedIds.has(child.id);
                        return (
                          <li
                            key={`root-leaf-${child.id}`}
                            className="d-flex align-items-center justify-content-between gap-3 py-3 border-bottom border-gray-100 min-w-0"
                          >
                            <label
                              htmlFor={`root-leaf-check-${child.id}`}
                              className="d-flex align-items-center gap-3 flex-grow-1 min-w-0 cursor-pointer mb-0 overflow-hidden"
                            >
                              {child.icon ? (
                                <span className={`symbol symbol-40px ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                                  <span className="symbol-label bg-light-primary">
                                    <img
                                      src={child.icon}
                                      alt=""
                                      className="w-100 h-100"
                                    />
                                  </span>
                                </span>
                              ) : null}
                              <TruncatedLabel text={child.title} className="fw-semibold text-gray-900" />
                            </label>
                            <span className={`form-check form-check-custom form-check-solid m-0 ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                              <input
                                id={`root-leaf-check-${child.id}`}
                                type="checkbox"
                                className="form-check-input"
                                checked={checked}
                                onChange={(event) => {
                                  toggleLeaf(child.id, event.target.checked);
                                }}
                              />
                            </span>
                          </li>
                        );
                      }

                      const expanded = expandedSubIds.has(child.id);
                      const state = triStateForNode(child, selectedIds);
                      const leaves = child.children ?? [];

                      return (
                        <li
                          key={`sub-${child.id}`}
                          className="border-bottom border-gray-100 py-2"
                        >
                          <div className="d-flex align-items-center justify-content-between gap-3 min-w-0">
                            <button
                              type="button"
                              className="btn btn-flush d-flex align-items-center gap-3 flex-grow-1 min-w-0 text-start px-0 py-2 overflow-hidden"
                              aria-expanded={expanded}
                              onClick={() => toggleExpanded(child.id)}
                            >
                              <span className={`text-primary ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`} aria-hidden="true">
                                {expanded ? (
                                  <FontAwesomeIcon icon={faChevronDown} className="fs-7" />
                                ) : (
                                  expandChevron
                                )}
                              </span>
                              {child.icon ? (
                                <span className={`symbol symbol-35px ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                                  <span className="symbol-label bg-light-primary">
                                    <img
                                      src={child.icon}
                                      alt=""
                                      className="w-100 h-100"
                                    />
                                  </span>
                                </span>
                              ) : null}
                              <TruncatedLabel text={child.title} className="fw-bold text-gray-900" />
                              <span className={`text-muted fs-8 ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                                ({collectLeaves(child).length})
                              </span>
                            </button>
                            <div className={CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}>
                              <IndeterminateCheckbox
                                id={`sub-select-all-${child.id}`}
                                checked={state === 'all'}
                                triState={state}
                                ariaLabel={`${t('select-all')} ${child.title}`}
                                onChange={(checked) => applyTriStateToggle(child, checked)}
                              />
                            </div>
                          </div>
                          {expanded ? (
                            <ul className="list-unstyled ms-8 mb-2">
                              {leaves.map((leaf) => {
                                const selectableLeaves = nodeHasChildren(leaf)
                                  ? collectLeaves(leaf)
                                  : [leaf];

                                return selectableLeaves.map((selectable) => {
                                  const checked = selectedIds.has(selectable.id);
                                  return (
                                    <li
                                      key={`leaf-${selectable.id}`}
                                      className="d-flex align-items-center justify-content-between gap-3 py-2 min-w-0"
                                    >
                                      <label
                                        htmlFor={`leaf-check-${selectable.id}`}
                                        className="d-flex align-items-center gap-3 flex-grow-1 min-w-0 cursor-pointer mb-0 overflow-hidden"
                                      >
                                        {selectable.icon ? (
                                          <span className={`symbol symbol-30px ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                                            <span className="symbol-label bg-light">
                                              <img
                                                src={selectable.icon}
                                                alt=""
                                                className="w-100 h-100"
                                              />
                                            </span>
                                          </span>
                                        ) : null}
                                        <TruncatedLabel text={selectable.title} className="text-gray-800" />
                                      </label>
                                      <span className={`form-check form-check-custom form-check-solid m-0 ${CATEGORY_PICKER_FLEX_SHRINK_0_CLASS}`}>
                                        <input
                                          id={`leaf-check-${selectable.id}`}
                                          type="checkbox"
                                          className="form-check-input"
                                          checked={checked}
                                          onChange={(event) => {
                                            toggleLeaf(selectable.id, event.target.checked);
                                          }}
                                        />
                                      </span>
                                    </li>
                                  );
                                });
                              })}
                            </ul>
                          ) : null}
                        </li>
                      );
                    })}
              </ul>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
