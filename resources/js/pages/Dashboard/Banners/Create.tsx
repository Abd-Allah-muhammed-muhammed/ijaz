import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {Banner} from "@/types/models";

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
