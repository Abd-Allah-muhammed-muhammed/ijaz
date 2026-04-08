import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {City, Region} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import SkillController from "@/actions/App/Http/Controllers/Dashboard/SkillController";
import CityController from "@/actions/App/Http/Controllers/Dashboard/CityController";


type Props = {
  row: City,
  regions: Region[]

};

const Edit = ({regions, row}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('cities')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('cities'),
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
        {t('cities')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            regions={regions}
            row={row}
            callback={(form) => {
              const route = CityController.update(row.id as number);
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
