import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {Region} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import SkillController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/SkillController";
import RegionController from "@/actions/Modules/Geo/Http/Controllers/Dashboard/RegionController";


type Props = {
  row: Region,
};

const Edit = ({row}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('regions')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('regions'),
          path: SkillController.index().url,
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
        {t('regions')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            row={row}
            callback={(form) => {
              const route = RegionController.update(row.id as number);
              form.transform((data) => {
                return {
                  ...data,
                  _method: route.method,
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
