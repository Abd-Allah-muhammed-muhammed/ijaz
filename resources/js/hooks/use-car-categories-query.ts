import axios from "@/helpers/axios";
import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {usePage} from "@inertiajs/react";
import ReactSelectController from "@/actions/App/Http/Controllers/General/ReactSelectController";
import {ApiResponse} from "@/types/api";
import {SelectOption} from "@/types";

export const fetchCarCategories = async (locale: string, signal: AbortSignal, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + ReactSelectController.carCategories({mergeQuery: {search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

export const carCategoriesQueryOptions = (locale: string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['carCategories', {search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchCarCategories(locale, signal, search),
    staleTime: 1000 * 60 * 5,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}

const useCarCategoriesQuery = (search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(carCategoriesQueryOptions(locale, search));
};

export default useCarCategoriesQuery;
