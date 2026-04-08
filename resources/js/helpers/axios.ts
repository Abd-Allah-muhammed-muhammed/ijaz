import axios from "axios";
import {url} from "@/helpers/general";

const a = axios.create({
  timeout: 10000, // Set a timeout for requests
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'withCredentials': true,
  },
  baseURL: url('/'),
});

a.interceptors.request.use((c) => {
  return c;
})

export default a;



