import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import CatalogSelectController from "@/actions/Modules/Catalog/Http/Controllers/General/CatalogSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchPropertyTypes = async (locale: string, signal: AbortSignal, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + CatalogSelectController.propertyTypes({mergeQuery: {search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const propertyTypesQueryOptions = (locale: string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['propertyTypes', {search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchPropertyTypes(locale, signal, search),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}

const usePropertyTypesQuery = (search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(propertyTypesQueryOptions(locale, search));
};

export default usePropertyTypesQuery;
