import {toast} from 'sonner'
import {usePage} from "@inertiajs/react";
import {useEffect} from "react";

export default function ToastEffect() {
  const {flash} = usePage().props;
  useEffect(() => {
    if (flash.success) {
      toast.success(flash.success.content, {});
    }
    if (flash.error) {
      toast.error(flash.error.content);
    }
  }, [flash.error?.id, flash.success?.id]);

  return <></>;
}
