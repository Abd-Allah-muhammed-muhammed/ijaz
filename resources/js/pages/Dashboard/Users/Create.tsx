import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import Form from "./Form";
import { Nationality } from '@/types/models';
import UserController from "@/actions/App/Http/Controllers/Dashboard/UserController";


type Props = {
  nationalities: Nationality[]
};

const Create = ({nationalities}: Props) => {
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
          title: t('create'),
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('users')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <Form
          nationalities={nationalities}
          image='/media/avatars/blank.png'
          backUrl={UserController.index().url}
          callback={(form) => {
            form.transform(data => {
              return {
                ...data,
              };
            })
            form.submit(UserController.store());
          }}/>
      </Content>
    </>
  );
}
Create.layout = (page: any) => <MasterLayout children={page}/>;
export default Create;
