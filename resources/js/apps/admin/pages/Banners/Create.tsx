import { useTranslation } from 'react-i18next';
import MasterLayout from '@/apps/admin/layouts';
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from '@/apps/admin/layouts';
import {Content} from '@/apps/admin/layouts';
import {Head} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import {Banner} from "@/shared/types/models";

import {ReactNode} from "react";
import BannerController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/BannerController";


type Props = {
  row: Banner
};

const Create = ({row}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('banners')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('banners'),
          path: BannerController.index().url,
          isSeparator: false,
          isActive: false,
        },
        {
          title: t('create'),
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('banners')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            row={row}
            image={'/media/avatars/blank.png'}
            callback={(form) => {
              form.submit(BannerController.store());
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Create.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Create;
