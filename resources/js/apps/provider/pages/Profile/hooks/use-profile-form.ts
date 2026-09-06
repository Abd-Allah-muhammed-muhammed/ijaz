import { useState, type FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController';
import { zodValidate } from '@/shared/helpers/general';
import type { ProviderTypeFileKeys } from '@/shared/types/models';
import type { Data as SelectCategoryModalData } from '@/shared/components/categories/category-selector/select-category-modal';
import { profileFormSchema } from '@/apps/provider/pages/Profile/profile-form-schema';
import type {
  ProfileCategorySelection,
  ProfileFormData,
  ProfilePageProps,
  ProfileRequiredFiles,
} from '@/apps/provider/pages/Profile/types';

const EMPTY_REQUIRED_FILES: ProfileRequiredFiles = {
  id_image: false,
  commercial_record: false,
  freelancer_certification: false,
  iban_certification: false,
  license_to_practice_law: false,
};

function requiredFilesFromType(
  files: ProfileRequiredFiles | undefined,
): ProfileRequiredFiles {
  return {
    ...EMPTY_REQUIRED_FILES,
    ...(files ?? {}),
  };
}

function mapProviderCategories(
  provider: ProfilePageProps['provider'],
): ProfileCategorySelection[] {
  return (
    provider.categories?.map((category) => ({
      category,
      skills:
        category.provider_skills?.map((skill) => ({
          value: String(skill.id),
          label: skill.title as string,
        })) ?? [],
    })) ?? []
  );
}

export function useProfileForm({
  provider,
  types,
}: Pick<ProfilePageProps, 'provider' | 'types'>) {
  const { t } = useTranslation();
  const [requiredFiles, setRequiredFiles] = useState<ProfileRequiredFiles>(
    requiredFilesFromType(provider.provider_type?.files),
  );
  const [selectingCategory, setSelectingCategory] = useState<
    ProfileCategorySelection[]
  >(mapProviderCategories(provider));

  const form = useForm<ProfileFormData>({
    provider_type_id: provider.provider_type_id ?? null,
    name: provider.name ?? null,
    email: provider.email ?? null,
    phone: provider.phone ?? null,
    iban: provider.iban ?? null,
    address: provider.address ?? null,
    region_id: provider.region_id ?? null,
    city_id: provider.city_id ?? null,
    password: null,
    password_confirmation: null,
    about: provider.about ?? '',
    categories:
      provider.categories?.map((category) => ({
        id: category.id as number,
        skills:
          category.provider_skills?.map((skill) => skill.id as number) ?? [],
      })) ?? [],
    logo: undefined,
    id_image: undefined,
    commercial_record: undefined,
    freelancer_certification: undefined,
    iban_certification: undefined,
    license_to_practice_law: undefined,
  });

  const setProviderType = (providerTypeId: number | null) => {
    if (providerTypeId) {
      const nextType = types.find((type) => type.id === providerTypeId);
      setRequiredFiles(requiredFilesFromType(nextType?.files));
    } else {
      setRequiredFiles(EMPTY_REQUIRED_FILES);
    }
    setSelectingCategory([]);
    form.setData((previous) => ({
      ...previous,
      provider_type_id: providerTypeId,
      categories: [],
    }));
  };

  const mergeCategoriesFromModal = (data: SelectCategoryModalData[]) => {
    setSelectingCategory((previous) => {
      const map = new Map(
        previous.map((item) => [item.category.id as number, item]),
      );
      data.forEach((item) => {
        if (!item.category) {
          return;
        }
        map.set(item.category.id as number, {
          category: item.category,
          skills: item.skills,
        });
      });
      return Array.from(map.values());
    });

    form.setData((previous) => {
      const map = new Map((previous.categories ?? []).map((item) => [item.id, item]));
      data.forEach((item) => {
        if (!item.category) {
          return;
        }
        map.set(item.category.id as number, {
          id: item.category.id as number,
          skills: item.skills.map((skill) => parseInt(String(skill.value), 10)),
        });
      });
      return {
        ...previous,
        categories: Array.from(map.values()),
      };
    });
  };

  const removeCategory = (categoryId: number) => {
    setSelectingCategory((previous) =>
      previous.filter((item) => item.category.id !== categoryId),
    );
    form.setData((previous) => ({
      ...previous,
      categories: (previous.categories ?? []).filter(
        (item) => item.id !== categoryId,
      ),
    }));
  };

  const setFileField = (key: ProviderTypeFileKeys, file: File | undefined) => {
    form.setData(key, file);
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();

    if (
      !zodValidate(profileFormSchema, form, {
        requiredFiles,
        id: Number(provider.id),
      })
    ) {
      toast.error(t('something went wrong'));
      return;
    }

    form.transform((data) => ({
      ...data,
      password: data.password || null,
      password_confirmation: data.password_confirmation || null,
    }));

    form.submit(AuthController.updateProfile(), {
      preserveScroll: true,
      forceFormData: true,
      onError: () => {
        toast.error(t('something went wrong'));
      },
      onSuccess: () => {
        form.reset(
          'password',
          'password_confirmation',
          'logo',
          'id_image',
          'commercial_record',
          'freelancer_certification',
          'iban_certification',
          'license_to_practice_law',
        );
      },
    });
  };

  return {
    form,
    requiredFiles,
    selectingCategory,
    setProviderType,
    mergeCategoriesFromModal,
    removeCategory,
    setFileField,
    submit,
  };
}
