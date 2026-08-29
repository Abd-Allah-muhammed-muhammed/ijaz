import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { KTCard, KTCardBody } from '@/vendor/metronic/helpers';
import clsx from 'clsx';
import { ReactElement, useMemo, useState } from 'react';
import {
  Col,
  Form as BTForm,
  FormCheck,
  FormControl,
  FormGroup,
  FormLabel,
  Row,
} from 'react-bootstrap';
import SettingController from '@/actions/Modules/Settings/Http/Controllers/Dashboard/SettingController';
import usePermissions from '@/shared/hooks/use-permissions';
import InputError from '@/shared/components/inputs/InputError';
import ActionButton from '@/shared/components/action-button';

type SettingType = 'text' | 'textarea';

type SettingRow = {
  id: number;
  key: string;
  content: string;
  type: SettingType;
  group: string;
  is_public: boolean;
};

type Props = {
  groups: Record<string, SettingRow[]>;
  groupOrder: string[];
};

const Index = ({ groups, groupOrder }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const page = usePage();
  const queryTab = (page.props as { prams?: { tab?: string } }).prams?.tab
    ?? new URLSearchParams(window.location.search).get('tab')
    ?? undefined;

  const tabs = useMemo(
    () => (groupOrder.length > 0 ? groupOrder : Object.keys(groups)),
    [groupOrder, groups],
  );

  const defaultTab = tabs.includes(queryTab ?? '') ? (queryTab as string) : (tabs[0] ?? 'general');
  const [activeTab, setActiveTab] = useState(defaultTab);
  const canEdit = hasPermission('edit settings');

  return (
    <>
      <Head title={t('settings')} />
      <PageTitle
        breadcrumbs={[
          {
            title: '',
            path: '',
            isSeparator: true,
            isActive: false,
          },
        ]}
      >
        {t('settings')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="d-flex flex-wrap gap-2 mb-6">
          {tabs.map((group) => (
            <button
              key={group}
              type="button"
              className={clsx(
                'btn btn-sm rounded-pill d-inline-flex align-items-center gap-2 px-4 py-2 fw-bold',
                activeTab === group
                  ? 'btn-primary'
                  : 'btn-light text-gray-600 btn-active-light-primary',
              )}
              onClick={() => setActiveTab(group)}
            >
              {t(`settings_tab_${group}`)}
            </button>
          ))}
        </div>

        <SettingsGroupForm
          key={activeTab}
          group={activeTab}
          rows={groups[activeTab] ?? []}
          canEdit={canEdit}
        />
      </Content>
    </>
  );
};

type FormProps = {
  group: string;
  rows: SettingRow[];
  canEdit: boolean;
};

function isTextareaRow(row: SettingRow): boolean {
  return row.type === 'textarea';
}

function SettingsGroupForm({ group, rows, canEdit }: FormProps) {
  const { t } = useTranslation();
  const initialValues = useMemo(
    () =>
      rows.reduce<Record<string, string>>((carry, row) => {
        carry[row.key] = row.content ?? '';
        return carry;
      }, {}),
    [rows],
  );

  const initialPublic = useMemo(
    () =>
      rows.reduce<Record<string, boolean>>((carry, row) => {
        carry[row.key] = Boolean(row.is_public);
        return carry;
      }, {}),
    [rows],
  );

  const { textRows, textareaRows } = useMemo(() => {
    const text: SettingRow[] = [];
    const textarea: SettingRow[] = [];

    for (const row of rows) {
      if (isTextareaRow(row)) {
        textarea.push(row);
      } else {
        text.push(row);
      }
    }

    return { textRows: text, textareaRows: textarea };
  }, [rows]);

  const form = useForm<{
    values: Record<string, string>;
    is_public: Record<string, boolean>;
    group: string;
  }>({
    values: initialValues,
    is_public: initialPublic,
    group,
  });

  if (rows.length === 0) {
    return (
      <KTCard className="border-0 shadow-sm rounded-4">
        <KTCardBody className="p-6 p-lg-9 text-muted text-center py-10">
          {t('no_data')}
        </KTCardBody>
      </KTCard>
    );
  }

  return (
    <KTCard className="border-0 shadow-sm rounded-4">
      <KTCardBody className="p-6 p-lg-9">
        <BTForm
          onSubmit={(e) => {
            e.preventDefault();
            if (!canEdit) {
              return;
            }
            form.transform((data) => ({
              values: data.values,
              is_public: data.is_public,
              group: data.group,
            }));
            form.put(SettingController.update().url, {
              preserveScroll: true,
              onSuccess: () => {
                router.reload({ only: ['groups'] });
              },
            });
          }}
        >
          {textRows.length > 0 && (
            <Row className="g-4">
              {textRows.map((row) => (
                <Col sm={12} md={6} key={row.key}>
                  <SettingField
                    row={row}
                    form={form}
                    canEdit={canEdit}
                    t={t}
                  />
                </Col>
              ))}
            </Row>
          )}

          {textareaRows.length > 0 && (
            <div className={clsx('d-flex flex-column gap-4', textRows.length > 0 && 'mt-6')}>
              {textareaRows.map((row) => (
                <SettingField
                  key={row.key}
                  row={row}
                  form={form}
                  canEdit={canEdit}
                  t={t}
                />
              ))}
            </div>
          )}

          {canEdit && (
            <div className="d-flex justify-content-end mt-8">
              <ActionButton
                isProcessing={form.processing}
                text={t('save')}
                className="btn btn-primary"
              />
            </div>
          )}
        </BTForm>
      </KTCardBody>
    </KTCard>
  );
}

type SettingFieldProps = {
  row: SettingRow;
  form: ReturnType<
    typeof useForm<{
      values: Record<string, string>;
      is_public: Record<string, boolean>;
      group: string;
    }>
  >;
  canEdit: boolean;
  t: (key: string, options?: { defaultValue?: string }) => string;
};

function SettingField({ row, form, canEdit, t }: SettingFieldProps) {
  const isTextarea = isTextareaRow(row);

  return (
    <FormGroup>
      <FormLabel className="fw-semibold text-gray-800">
        {t(`settings.${row.key}`, { defaultValue: row.key })}
      </FormLabel>
      <FormControl
        as={isTextarea ? 'textarea' : 'input'}
        rows={isTextarea ? 4 : undefined}
        type="text"
        disabled={!canEdit || form.processing}
        value={form.data.values[row.key] ?? ''}
        onChange={(e) => {
          const value = e.currentTarget.value;
          form.setData('values', {
            ...form.data.values,
            [row.key]: value,
          });
        }}
      />
      <InputError message={form.errors[`values.${row.key}`]} />
      <div className="d-flex align-items-center gap-2 mt-2">
        <FormCheck
          type="checkbox"
          id={`is-public-${row.key}`}
          className="mb-0"
          disabled={!canEdit || form.processing}
          checked={Boolean(form.data.is_public[row.key])}
          onChange={(e) => {
            form.setData('is_public', {
              ...form.data.is_public,
              [row.key]: e.currentTarget.checked,
            });
          }}
        />
        <label htmlFor={`is-public-${row.key}`} className="text-muted fs-7 mb-0 cursor-pointer">
          {t('visible_in_public_api')}
        </label>
      </div>
      <InputError message={form.errors[`is_public.${row.key}`]} />
    </FormGroup>
  );
}

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
