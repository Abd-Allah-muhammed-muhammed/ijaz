import { Col, FormControl, FormGroup, FormLabel, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import type { InertiaFormProps } from '@inertiajs/react';
import { KTIcon } from '@/vendor/metronic/helpers';
import InputError from '@/shared/components/inputs/InputError';
import { SectionCard } from '@/shared/components/ui';
import type { ProfileFormData, ProfileRequiredFiles } from '@/apps/provider/pages/Profile/types';
import type { Provider, ProviderTypeFileKeys } from '@/shared/types/models';

export type RequiredFilesCardProps = {
  form: InertiaFormProps<ProfileFormData>;
  requiredFiles: ProfileRequiredFiles;
  provider: Provider;
};

export default function RequiredFilesCard({
  form,
  requiredFiles,
  provider,
}: RequiredFilesCardProps) {
  const { t } = useTranslation();
  const visibleKeys = (
    Object.entries(requiredFiles) as [ProviderTypeFileKeys, boolean][]
  )
    .filter(([, enabled]) => enabled)
    .map(([key]) => key);

  const T = t as (key: string, options?: Record<string, string>) => string;

  return (
    <SectionCard title={T('required files')} className="mb-5">
      {visibleKeys.length === 0 ? (
        <p className="text-muted fs-7 mb-0">{T('no_required_files_for_type')}</p>
      ) : (
        <Row className="g-3">
          {visibleKeys.map((key) => {
            const existing = provider.media?.find(
              (media) => media.collection_name === key,
            );
            return (
              <Col xs={12} md={6} lg={4} key={`file-${key}`}>
                <FormGroup>
                  <FormLabel className="required">{T(key)}</FormLabel>
                  <FormControl
                    accept="application/pdf"
                    className="form-control-solid"
                    type="file"
                    onChange={(event) => {
                      const file = (
                        event.currentTarget as HTMLInputElement
                      ).files?.[0];
                      form.setData(key, file);
                    }}
                  />
                  {existing?.url ? (
                    <a
                      href={existing.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="mt-2 d-inline-flex align-items-center gap-1"
                    >
                      <KTIcon iconName="document" className="fs-2" />
                      {T('download existing file')}
                    </a>
                  ) : null}
                  <InputError message={form.errors[key]} />
                </FormGroup>
              </Col>
            );
          })}
        </Row>
      )}
    </SectionCard>
  );
}
