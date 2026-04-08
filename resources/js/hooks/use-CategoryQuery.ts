import axios from "@/helpers/axios";

import {useQuery, UseQueryOptions, UseQueryResult} from "@tanstack/react-query";
import {Category} from "@/types/models";
import {usePage} from "@inertiajs/react";
import ReactSelectController from "@/actions/App/Http/Controllers/General/ReactSelectController";
import {ApiResponse, SingleApiResponse} from "@/types/api";
import {SelectOption} from "@/types";
import AjaxController from "@/actions/App/Http/Controllers/General/AjaxController";

/**
 * Function to fetch categories from the API.
 * This function is used to retrieve a list of categories based on the provided locale and search term.
 * It makes an HTTP GET request to the categories endpoint and returns the data in the form of an array of Category objects.
 * @param locale
 * @param signal
 * @param search
 */
export const fetchSelect = async (locale: string, signal: AbortSignal, search?: string): Promise<SelectOption[]> => {
  const url = '/' + locale + ReactSelectController.categories({mergeQuery: {search}}).url
  const {data} = await axios.get<ApiResponse<SelectOption>>(url, {signal});
  return data.data;
}

/**
 * Function to fetch categories from the API.
 * This function is used to retrieve a list of categories based on the provided locale, parent_id, and search term.
 * It makes an HTTP GET request to the categories endpoint and returns the data in the form of an array of Category objects.
 * @param locale
 * @param signal
 * @param parent_id
 * @param search
 * @param provider_type_id
 */
export const fetchCategories = async (locale: string, signal: AbortSignal, parent_id?: string | number, search?: string, provider_type_id?: string): Promise<Category[]> => {
  const url = '/' + locale + AjaxController.categories({mergeQuery: {search, parent_id, provider_type_id}}).url
  const {data} = await axios.get<ApiResponse<Category>>(url, {signal});
  return data.data;
}


/**
 * Function to generate query options for fetching a single category by ID.
 * This function is used with React Query to manage the fetching and caching of a single category's data.
 * @param {string} locale - The locale for the category data.
 * @param {number} id - The ID of the category to fetch.
 * @returns {UseQueryOptions<Category>} - The query options for fetching a single category.
 */
export function CategoriesSingleQueryOptions(locale: string, id: number): UseQueryOptions<Category> {
  return {
    queryKey: ['categories', 'single', {id}],
    queryFn: ({signal}) => fetchOne(locale, signal, id),
    staleTime: 1000 * 60 * 5, // 5 minutes
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
    enabled: Boolean(id), // Disable the query if id is not provided
  }
}

/**
 * Function to fetch a single category by ID from the API.
 * This function is used to retrieve a specific category based on the provided locale and ID.
 * It makes an HTTP GET request to the categories endpoint and returns the data in the form of a Category object.
 * This function is typically used in conjunction with React Query to manage the fetching and caching of category data.
 * @param locale
 * @param signal
 * @param id
 */
export const fetchOne = async (locale: string, signal: AbortSignal, id: number): Promise<Category> => {
  const url = '/' + locale + AjaxController.category(id).url
  console.log(`Fetching category #${id} from URL: ${url}`);
  const {data} = await axios.get<SingleApiResponse<Category>>(url, {signal});
  return data.data;
}

/**
 * Function to generate query options for fetching categories.
 * This function is used with React Query to manage the fetching and caching of categories data.
 * @param locale
 * @param search
 */
export const categoriesSelectQueryOptions = (locale: string, search?: string): UseQueryOptions<SelectOption[]> => {
  const queryKey = ['categories', 'select', {search}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchSelect(locale, signal, search,),
    staleTime: 1000 * 1,  // 1 minute
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}


export const categoriesQueryOptions = (locale: string, parent_id ?: string | number, search?: string, provider_type_id?: string): UseQueryOptions<Category[]> => {
  const queryKey = ['categories', {search, parent_id, provider_type_id}] as const;
  return {
    queryKey: [queryKey],
    queryFn: ({signal}) => fetchCategories(locale, signal, parent_id, search, provider_type_id),
    staleTime: 1000 * 5,  // 5 minute
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  };
}

/**
 * Custom hook to fetch categories from the API.
 * Uses React Query for data fetching and caching.
 *
 * @returns {UseQueryResult<Skill[]>} - The query object containing categories data and status.
 */
export const useGetCategoriesOptions = (search?: string): UseQueryResult<SelectOption[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<SelectOption[]>(categoriesSelectQueryOptions(locale, search));
};

/**
 * Custom hook to fetch a single category by ID.
 * Uses React Query for data fetching and caching.
 *
 * @param {number} id - The ID of the category to fetch.
 * @returns {UseQueryResult<Category>} - The query object containing the category data and status.
 */
export const useGetCategory = (id: number): UseQueryResult<Category> => {
  const locale = usePage().props.app.locale;
  return useQuery<Category>(CategoriesSingleQueryOptions(locale, id));
}


export const useGetCategories = (parent_id?: string | number, search?: string, provider_type_id?: string): UseQueryResult<Category[]> => {
  const locale = usePage().props.app.locale;
  return useQuery<Category[]>(categoriesQueryOptions(locale, parent_id, search, provider_type_id));
}



