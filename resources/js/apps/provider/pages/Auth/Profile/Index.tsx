import {ReactElement} from "react";
import AccountLayout from '@/apps/provider/layouts/AccountLayout'
import {DeactivateAccount} from "@/apps/provider/layouts/accounts/components/settings/cards/DeactivateAccount";
import {Content} from "@/vendor/metronic/layout/components/content";
import ProviderLayout from "@/apps/provider/layouts/ProviderLayout";
import Form from "@/apps/admin/pages/Providers/Form";
import {City, Provider, ProviderType, Region} from "@/shared/types/models";
import {Head} from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import {zodValidate} from "@/shared/helpers/general";
import {Inputs} from "@/apps/admin/pages/Providers/Validation";
import AuthController from "@/actions/App/Http/Controllers/Provider/AuthController";

type Props = {
  types: ProviderType[],
  cities: City[],
  regions: Region[],
  provider: Provider,
}

const Index = (
  {
    types,
    cities,
    regions,
    provider
  }: Props
) => {
  const { t } = useTranslation();
  return (
    <Content>
      <Head title={t('profile')}/>
      <Form
        row={provider}
        types={types}
        cities={cities}
        regions={regions}
        callback={(form, requiredFiles) =>{
          if (!zodValidate(Inputs,form, {requiredFiles , id: provider.id})){
            return false
          }
          form.submit(AuthController.updateProfile(),{preserveScroll: true})
        }}

      />
      <DeactivateAccount/>
    </Content>
  );
}


Index.layout = (page: ReactElement) => {

  return (
    <ProviderLayout>
      {/* @ts-ignore */}
      <AccountLayout {...page.props}>
        {page}
      </AccountLayout>
    </ProviderLayout>

  )
}

export default Index
