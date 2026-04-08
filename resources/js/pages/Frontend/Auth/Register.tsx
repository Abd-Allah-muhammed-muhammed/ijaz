import FrontendLayout from "@/layouts/FrontendLayout";
import Form from "@/pages/Dashboard/Providers/Form";
import {City, ProviderType, Region} from "@/types/models";
import AuthController from "@/actions/App/Http/Controllers/Frontend/AuthController";
import {CategoryFormData} from "@/pages/Dashboard/Providers/types";
import {Head} from "@inertiajs/react";
import { useTranslation } from 'react-i18next';

type Props = {
  types: ProviderType[]
  regions: Region[],
  cities: City[]
};
const register = ({types, regions, cities}: Props) => {
  const { t } = useTranslation();

  return (
    <div className='container'>
      <Head title={t('register')}/>
      <Form
        types={types}
        cities={cities}
        regions={regions}
        images={{
          logo: '',
          commercial_record: '',
          owner: {
            id_image: '',
            profile_picture: '',
          }
        }}
        callback={(form) => {
          form.transform(data => {
            return {
              ...data,
              categories: data.categories.map((category: CategoryFormData) => {
                return {
                  category: category.category.id,
                  skills: category.skills.map(skill => skill.id)
                }
              }),
            };
          })
          form.submit(AuthController.store(), {
            onSuccess: () => {
              form.reset()
            },
          });
        }}
      />
    </div>
  );
}


register.layout = (page: any) => {
  return (
    <FrontendLayout>
      {page}
    </FrontendLayout>
  );
}

export default register;
