
import {ButtonAction} from "@/components/Table";
import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import {useTranslation} from "react-i18next";
type props = {
  title: string;
  callback: () => void;
};

export default function ConfirmAction({title, callback}: props) {
  const swal = withReactContent(Swal)
  const {t} = useTranslation();
  return (
    <ButtonAction
      onClick={() => {
        swal.fire({
          title: t('are_you_sure'),
          icon: 'warning',
          showCancelButton: true,
          cancelButtonText: t('cancel'),
          confirmButtonText: t('yes'),
        }).then(function (d) {
          if (d.isConfirmed) {
            if (callback) {
              callback();
            }
          }
        })
      }}
      title={title}
    />
  )
}
