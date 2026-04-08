import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import ReactSelectController from "@/actions/App/Http/Controllers/General/ReactSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchRegions = async (locale: string, signal: AbortSignal, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + ReactSelectController.regions({mergeQuery: {search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const regionsQueryOptions = (locale: string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['regions', {search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchRegions(locale, signal, search),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}

const useRegionsQuery = (search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(regionsQueryOptions(locale, search));
};

export default useRegionsQuery;
