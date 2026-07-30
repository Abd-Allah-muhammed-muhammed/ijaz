import { ButtonAction } from '@/shared/components/Table';
import { confirmWithSweetAlert } from '@/shared/lib/confirm-action';
import { useTranslation } from 'react-i18next';

type Props = {
  title: string;
  callback: () => void;
};

export default function ConfirmAction({ title, callback }: Props) {
  const { t } = useTranslation();

  return (
    <ButtonAction
      onClick={() => {
        void confirmWithSweetAlert({
          title: t('are_you_sure'),
          cancelButtonText: t('cancel'),
          confirmButtonText: t('yes'),
        }).then((confirmed) => {
          if (confirmed) {
            callback();
          }
        });
      }}
      title={title}
    />
  );
}
