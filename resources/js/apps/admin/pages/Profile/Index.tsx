import { useTranslation } from 'react-i18next';
import MasterLayout from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { Head, useForm } from '@inertiajs/react';
import { KTCard } from '@/vendor/metronic/helpers';
import { Admin } from '@/shared/types/models';
import { Col, Form as BTForm, FormControl, FormGroup, Row } from 'react-bootstrap';
import InputError from '@/shared/components/inputs/InputError';
import ImageInput from '@/shared/components/inputs/ImageInput';
import ActionButton from '@/shared/components/action-button';
import AuthController from '@/actions/App/Http/Controllers/Dashboard/AuthController';
import { ReactElement } from 'react';

type Props = {
  admin: Admin;
};

type ProfileForm = {
  name: string;
  email: string;
  phone: string;
  address: string;
  job: string;
  password: string;
  password_confirmation: string;
  image: File | undefined;
};

const Index = ({ admin }: Props) => {
  const { t } = useTranslation();
  const form = useForm<ProfileForm>({
    name: admin.name || '',
    email: admin.email || '',
    phone: admin.phone || '',
    address: admin.address || '',
    job: admin.job || '',
    password: '',
    password_confirmation: '',
    image: undefined,
  });

  return (
    <>
      <Head title={t('profile')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('profile'),
            path: '',
            isSeparator: true,
            isActive: false,
          },
        ]}
      >
        {t('profile')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <BTForm
            onSubmit={(e) => {
              e.preventDefault();
              form.post(AuthController.updateProfile().url, {
                forceFormData: true,
                preserveScroll: true,
              });
            }}
          >
            <Row>
              <Col sm={12} md={3} className="mb-3">
                <ImageInput
                  url={admin.image}
                  callback={(data) => {
                    form.setData('image', data.currentTarget.files![0]);
                  }}
                />
                <InputError message={form.errors.image} />
              </Col>
              <Col className="mb-3">
                <Row>
                  <Col sm={12} md={4} className="mb-3">
                    <FormGroup>
                      <FormControl
                        placeholder={t('name')}
                        type="text"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.currentTarget.value)}
                      />
                      <InputError message={form.errors.name} />
                    </FormGroup>
                  </Col>
                  <Col sm={12} md={4} className="mb-3">
                    <FormGroup>
                      <FormControl
                        placeholder={t('email')}
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.currentTarget.value)}
                      />
                      <InputError message={form.errors.email} />
                    </FormGroup>
                  </Col>
                  <Col sm={12} md={4} className="mb-3">
                    <FormGroup>
                      <FormControl
                        placeholder={t('phone')}
                        type="tel"
                        value={form.data.phone}
                        onChange={(e) => form.setData('phone', e.currentTarget.value)}
                      />
                      <InputError message={form.errors.phone} />
                    </FormGroup>
                  </Col>
                  <Col sm={12} md={4} className="mb-3">
                    <FormGroup>
                      <FormControl
                        placeholder={t('job')}
                        type="text"
                        value={form.data.job}
                        onChange={(e) => form.setData('job', e.currentTarget.value)}
                      />
                      <InputError message={form.errors.job} />
                    </FormGroup>
                  </Col>
                  <Col sm={12} md={4} className="mb-3">
                    <FormGroup>
                      <FormControl
                        placeholder={t('address')}
                        type="text"
                        value={form.data.address}
                        onChange={(e) => form.setData('address', e.currentTarget.value)}
                      />
                      <InputError message={form.errors.address} />
                    </FormGroup>
                  </Col>
                  <Col sm={12} md={4} className="mb-3">
                    <FormGroup>
                      <FormControl
                        placeholder={t('password')}
                        type="password"
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.currentTarget.value)}
                        autoComplete="new-password"
                      />
                      <InputError message={form.errors.password} />
                    </FormGroup>
                  </Col>
                  <Col sm={12} md={4} className="mb-3">
                    <FormGroup>
                      <FormControl
                        placeholder={t('password_confirmation')}
                        type="password"
                        value={form.data.password_confirmation}
                        onChange={(e) => form.setData('password_confirmation', e.currentTarget.value)}
                        autoComplete="new-password"
                      />
                      <InputError message={form.errors.password_confirmation} />
                    </FormGroup>
                  </Col>
                </Row>
              </Col>
            </Row>
            <Row>
              <Col sm={12} className="mb-3 d-flex gap-3 justify-content-end">
                <ActionButton type="submit" isProcessing={form.processing} text={t('save')} />
              </Col>
            </Row>
          </BTForm>
        </KTCard>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
