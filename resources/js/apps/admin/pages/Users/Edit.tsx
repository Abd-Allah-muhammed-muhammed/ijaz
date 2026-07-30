import { useTranslation } from 'react-i18next';
import MasterLayout from "@/apps/admin/layouts/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import { User, Nationality } from '@/shared/types/models';
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import UserController from "@/actions/App/Http/Controllers/Dashboard/UserController";


type Props = {
  row: User,
  nationalities: Nationality[]
};

const Edit = ({row, nationalities}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('users')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('users'),
          path: UserController.index().url,
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
        {t('users')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            nationalities={nationalities}
            row={row}
            image={row.image}
            backUrl={UserController.index().url}
            callback={(form) => {
              const route = UserController.update(row.id as number);
              form.put(route.url)
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Edit.layout = (page: any) => <MasterLayout children={page}/>;

export default Edit;
