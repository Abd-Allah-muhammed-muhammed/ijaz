import axios from "@/helpers/axios";

import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {SelectOption} from "@/types";
import {usePage} from "@inertiajs/react";
import ReactSelectController from "@/actions/App/Http/Controllers/General/ReactSelectController";
import {ApiResponse} from "@/types/api";


/**
 * Function to fetch skills from the API.
 * This function is used to retrieve a list of skills based on the provided locale, category ID, and search term.
 * It makes an HTTP GET request to the skills endpoint and returns the data in the form of an array of Skill objects.
 * @param locale
 * @param signal
 * @param categoryId
 * @param search
 */
export const fetchSkills = async (locale: string, signal: AbortSignal, categoryId?: number|string, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + ReactSelectController.skills({mergeQuery: {category_id: categoryId, search,}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

/**
 * Function to generate query options for fetching skills.
 * This function is used with React Query to manage the fetching and caching of skills data.
 * @param locale
 * @param categoryId
 * @param search
 */
export const skillsQueryOptions = (locale: string, categoryId?: number| string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['skills', {categoryId, search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchSkills(locale, signal, categoryId, search,),
    staleTime: 1000 * 1,  // 1 minute
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
    enabled: Boolean(categoryId), // Disable the query if category is trusty
  };
}


/**
 * Custom hook to fetch skills from the API.
 * Uses React Query for data fetching and caching.
 *
 * @returns {UseQueryResult<Skill[]>} - The query object containing skills data and status.
 */
const useGetSkills = (categoryId: number | string, search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(skillsQueryOptions(locale, categoryId, search));
};

export default useGetSkills
