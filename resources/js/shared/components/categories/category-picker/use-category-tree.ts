import { useQuery, type UseQueryResult } from '@tanstack/react-query';
import { usePage } from '@inertiajs/react';
import axios from '@/shared/helpers/axios';
import AjaxController from '@/actions/App/Http/Controllers/General/AjaxController';
import type { ApiResponse } from '@/shared/types/api';
import type { CategoryTreeNode } from './types';

type TreeApiNode = {
  id: number;
  title?: string;
  icon?: string;
  parent_id?: number | null;
  has_children?: boolean;
  children?: TreeApiNode[];
};

function mapTreeNode(node: TreeApiNode): CategoryTreeNode {
  const children = (node.children ?? []).map(mapTreeNode);

  return {
    id: node.id,
    title: node.title ?? '',
    icon: node.icon ?? '',
    parent_id: node.parent_id ?? null,
    has_children: Boolean(node.has_children) || children.length > 0,
    children,
  };
}

export async function fetchCategoryTree(
  locale: string,
  signal: AbortSignal,
  providerTypeId?: string | number | null,
): Promise<CategoryTreeNode[]> {
  const provider_type_id =
    providerTypeId === null || providerTypeId === undefined || providerTypeId === ''
      ? undefined
      : providerTypeId;

  const url =
    '/' +
    locale +
    AjaxController.categoriesTree({
      mergeQuery: provider_type_id !== undefined ? { provider_type_id } : {},
    }).url;

  const { data } = await axios.get<ApiResponse<TreeApiNode>>(url, { signal });

  return (data.data ?? []).map(mapTreeNode);
}

export function useCategoryTree(
  providerTypeId?: string | number | null,
): UseQueryResult<CategoryTreeNode[]> {
  const locale = usePage().props.app.locale;

  return useQuery<CategoryTreeNode[]>({
    queryKey: ['categories', 'tree', { providerTypeId: providerTypeId ?? null }],
    queryFn: ({ signal }) => fetchCategoryTree(locale, signal, providerTypeId),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    enabled: true,
  });
}
