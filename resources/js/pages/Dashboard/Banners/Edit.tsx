import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {Banner} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import BannerController from "@/actions/App/Http/Controllers/Dashboard/BannerController";


type Props = {
  row: Banner,

};

const Edit = ({row}: Props) => {
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
          title: t('edit'),
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
            callback={(form) => {
              const route = BannerController.update(row.id as number);
              form.transform((data) => {
                return {
                  ...data,
                  _method: 'PUT',
                }
              })
              form.post(route.url)
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Edit.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Edit;
