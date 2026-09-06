import type { ReactElement } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Content } from '@/vendor/metronic/layout/components/content';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import ActionButton from '@/shared/components/action-button';
import ProfileIdentityHeader from '@/apps/provider/pages/Profile/components/ProfileIdentityHeader';
import LogoCard from '@/apps/provider/pages/Profile/components/LogoCard';
import GeneralInfoCard from '@/apps/provider/pages/Profile/components/GeneralInfoCard';
import PasswordCard from '@/apps/provider/pages/Profile/components/PasswordCard';
import CategoriesCard from '@/apps/provider/pages/Profile/components/CategoriesCard';
import RequiredFilesCard from '@/apps/provider/pages/Profile/components/RequiredFilesCard';
import DangerZoneCard from '@/apps/provider/pages/Profile/components/DangerZoneCard';
import { useProfileForm } from '@/apps/provider/pages/Profile/hooks/use-profile-form';
import type { ProfilePageProps } from '@/apps/provider/pages/Profile/types';

export default function Profile({
  provider,
  types,
  cities,
  regions,
}: ProfilePageProps) {
  const { t } = useTranslation();
  const {
    form,
    requiredFiles,
    selectingCategory,
    setProviderType,
    mergeCategoriesFromModal,
    removeCategory,
    submit,
  } = useProfileForm({ provider, types });

  const categoriesError =
    form.errors.categories ??
    Object.entries(form.errors).find(([key]) => key.startsWith('categories'))?.[1];

  return (
    <>
      <ToolbarWrapper />
      <Content>
        <Head title={t('profile')} />
        <ProfileIdentityHeader provider={provider} />

        <form onSubmit={submit}>
          <LogoCard
            providerName={provider.name ?? ''}
            initialUrl={provider.logo}
            serverError={form.errors.logo}
            onFileChange={(file) => form.setData('logo', file)}
          />

          <GeneralInfoCard
            form={form}
            types={types}
            regions={regions}
            cities={cities}
            onProviderTypeChange={setProviderType}
          />

          <PasswordCard form={form} />

          <CategoriesCard
            providerTypeId={form.data.provider_type_id}
            selectingCategory={selectingCategory}
            error={categoriesError}
            onMergeFromModal={mergeCategoriesFromModal}
            onRemoveCategory={removeCategory}
          />

          <RequiredFilesCard
            form={form}
            requiredFiles={requiredFiles}
            provider={provider}
          />

          <div className="d-flex justify-content-end mb-5">
            <ActionButton type="submit" isProcessing={form.processing} text={t('save')} />
          </div>
        </form>

        <DangerZoneCard />
      </Content>
    </>
  );
}

Profile.layout = (page: ReactElement) => (
  <ProviderLayout>{page}</ProviderLayout>
);
