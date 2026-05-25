import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import CatalogSelectController from "@/actions/Modules/Catalog/Http/Controllers/General/CatalogSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchSpecializations = async (locale: string, signal: AbortSignal, search?: string, parentId?: number): Promise<SelectOption[]> => {
  const url = '/' + locale + CatalogSelectController.specializations({mergeQuery: {search, parent_id: parentId}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const specializationsQueryOptions = (locale: string, search?: string, parentId?: number): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['specializations', {search, parentId}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchSpecializations(locale, signal, search, parentId),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}

const useSpecializationsQuery = (search?: string, parentId?: number): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(specializationsQueryOptions(locale, search, parentId));
};

export default useSpecializationsQuery;
