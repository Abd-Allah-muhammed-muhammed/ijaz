import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import ReactSelectController from "@/actions/App/Http/Controllers/General/ReactSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchCities = async (locale: string, signal: AbortSignal, regionId?: number | string, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + ReactSelectController.cities({mergeQuery: {region_id: regionId, search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const citiesQueryOptions = (locale: string, regionId?: number | string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['cities', {regionId, search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchCities(locale, signal, regionId, search),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
    enabled: Boolean(regionId),
  };
}

const useCitiesQuery = (regionId?: number | string, search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(citiesQueryOptions(locale, regionId, search));
};

export default useCitiesQuery;
