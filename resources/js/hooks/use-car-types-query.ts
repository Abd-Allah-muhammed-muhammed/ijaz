import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import CatalogSelectController from "@/actions/Modules/Catalog/Http/Controllers/General/CatalogSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchCarTypes = async (locale: string, signal: AbortSignal, carBrandId?: number | string, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + CatalogSelectController.carTypes({mergeQuery: {car_brand_id: carBrandId, search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const carTypesQueryOptions = (locale: string, carBrandId?: number | string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['carTypes', {carBrandId, search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchCarTypes(locale, signal, carBrandId, search),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
    enabled: Boolean(carBrandId),
  };
}

const useCarTypesQuery = (carBrandId?: number | string, search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(carTypesQueryOptions(locale, carBrandId, search));
};

export default useCarTypesQuery;
