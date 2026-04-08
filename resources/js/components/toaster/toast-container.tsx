import {Toaster} from "sonner";
import {useThemeMode} from "@/_metronic/partials";
import {usePage} from "@inertiajs/react";


export default function ToastContainer() {
  const {mode} = useThemeMode();
  const {locale} = usePage().props.app;
  return <Toaster position="top-center"  dir={['ar'].includes(locale) ? 'rtl' : "ltr"} theme={mode}/>
}
