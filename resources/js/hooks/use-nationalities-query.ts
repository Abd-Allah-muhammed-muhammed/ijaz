import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import ReactSelectController from "@/actions/App/Http/Controllers/General/ReactSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchNationalities = async (locale: string, signal: AbortSignal, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + ReactSelectController.nationalities({mergeQuery: {search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const nationalitiesQueryOptions = (locale: string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['nationalities', {search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchNationalities(locale, signal, search),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}

const useNationalitiesQuery = (search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(nationalitiesQueryOptions(locale, search));
};

export default useNationalitiesQuery;
