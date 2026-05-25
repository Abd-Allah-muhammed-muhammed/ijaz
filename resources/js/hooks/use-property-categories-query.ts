import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import CatalogSelectController from "@/actions/Modules/Catalog/Http/Controllers/General/CatalogSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchPropertyCategories = async (locale: string, signal: AbortSignal, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + CatalogSelectController.propertyCategories({mergeQuery: {search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const propertyCategoriesQueryOptions = (locale: string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['propertyCategories', {search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchPropertyCategories(locale, signal, search),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}

const usePropertyCategoriesQuery = (search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(propertyCategoriesQueryOptions(locale, search));
};

export default usePropertyCategoriesQuery;
