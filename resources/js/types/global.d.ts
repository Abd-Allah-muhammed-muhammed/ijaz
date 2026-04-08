import type {route as routeFn} from 'ziggy-js';
import {AuthenticatedUser, Locales, SharedData} from "@/types/index";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
  const route: typeof routeFn;

  interface Window {
    _translations: Record<string, string>;
    _locales : Locales;
    Echo: Echo
    Pusher: typeof Pusher;
  }


}

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps, AppPageProps , SharedData {
  }
  type FormDataConvertible = Set | Map
}
