import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { KTCard, KTCardBody, KTIcon } from '@/vendor/metronic/helpers';
import clsx from 'clsx';
import { ReactElement, useEffect, useMemo, useState } from 'react';
import {
  Button,
  Col,
  Form as BTForm,
  FormControl,
  FormGroup,
  FormLabel,
  Modal,
  Row,
  Spinner,
} from 'react-bootstrap';
import SettingController from '@/actions/Modules/Settings/Http/Controllers/Dashboard/SettingController';
import usePermissions from '@/shared/hooks/use-permissions';
import InputError from '@/shared/components/inputs/InputError';
import ActionButton from '@/shared/components/action-button';
import { apiGet } from '@/shared/lib/api-client';
import { groupSettingsBySection } from './settings-section-utils';
import {
  settingsVisibilityBadgeClass,
  settingsVisibilityBadgeLabel,
} from './settings-visibility-utils';

type SettingType = 'text' | 'textarea';

type SettingRow = {
  id: number;
  key: string;
  content: string;
  type: SettingType;
  group: string;
  section: string | null;
  is_public: boolean;
};

type HistoryItem = {
  id: number;
  key: string;
  old_content: string | null;
  new_content: string | null;
  created_at: string | null;
  actor: { id: number; name: string } | null;
};

type Props = {
  groups: Record<string, SettingRow[]>;
  groupOrder: string[];
};

const SECTION_META: Record<string, { icon: string; labelKey: string }> = {
  contact: { icon: 'phone', labelKey: 'contact' },
  social: { icon: 'share', labelKey: 'social' },
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
  const canShow = hasPermission('show settings');
  const [historyKey, setHistoryKey] = useState<string | null>(null);

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
          canShow={canShow}
          onViewHistory={setHistoryKey}
        />

        <SettingHistoryModal
          settingKey={historyKey}
          onClose={() => setHistoryKey(null)}
        />
      </Content>
    </>
  );
};

type FormProps = {
  group: string;
  rows: SettingRow[];
  canEdit: boolean;
  canShow: boolean;
  onViewHistory: (key: string) => void;
};

function isTextareaRow(row: SettingRow): boolean {
  return row.type === 'textarea';
}

function SettingsGroupForm({ group, rows, canEdit, canShow, onViewHistory }: FormProps) {
  const { t } = useTranslation();
  const initialValues = useMemo(
    () =>
      rows.reduce<Record<string, string>>((carry, row) => {
        carry[row.key] = row.content ?? '';
        return carry;
      }, {}),
    [rows],
  );

  const sectionBuckets = useMemo(() => groupSettingsBySection(rows), [rows]);

  const form = useForm<{
    values: Record<string, string>;
    group: string;
  }>({
    values: initialValues,
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
    <BTForm
      onSubmit={(e) => {
        e.preventDefault();
        if (!canEdit) {
          return;
        }
        form.put(SettingController.update().url, {
          preserveScroll: true,
          onSuccess: () => {
            router.reload({ only: ['groups'] });
          },
        });
      }}
    >
      <div className="d-flex flex-column gap-6">
        {sectionBuckets.map((bucket) => {
          const meta = bucket.section
            ? (SECTION_META[bucket.section] ?? {
                icon: 'setting-2',
                labelKey: bucket.section,
              })
            : { icon: 'abstract-26', labelKey: 'other' };

          return (
            <KTCard key={bucket.section ?? '__other__'} className="border-0 shadow-sm rounded-4">
              <KTCardBody className="p-6 p-lg-9">
                <div className="d-flex align-items-center gap-3 mb-6">
                  <span className="symbol symbol-40px">
                    <span className="symbol-label bg-light-primary">
                      <KTIcon iconName={meta.icon} className="fs-2 text-primary" />
                    </span>
                  </span>
                  <h4 className="fw-bold text-gray-900 mb-0">
                    {t(meta.labelKey, { defaultValue: meta.labelKey })}
                  </h4>
                </div>

                {bucket.textRows.length > 0 && (
                  <Row className="g-4">
                    {bucket.textRows.map((row) => (
                      <Col sm={12} md={6} key={row.key}>
                        <SettingField
                          row={row}
                          form={form}
                          canEdit={canEdit}
                          canShow={canShow}
                          onViewHistory={onViewHistory}
                          t={t}
                        />
                      </Col>
                    ))}
                  </Row>
                )}

                {bucket.textareaRows.length > 0 && (
                  <div
                    className={clsx(
                      'd-flex flex-column gap-4',
                      bucket.textRows.length > 0 && 'mt-6',
                    )}
                  >
                    {bucket.textareaRows.map((row) => (
                      <SettingField
                        key={row.key}
                        row={row}
                        form={form}
                        canEdit={canEdit}
                        canShow={canShow}
                        onViewHistory={onViewHistory}
                        t={t}
                      />
                    ))}
                  </div>
                )}
              </KTCardBody>
            </KTCard>
          );
        })}
      </div>

      {canEdit && (
        <div className="d-flex justify-content-end mt-6">
          <ActionButton
            isProcessing={form.processing}
            text={t('save')}
            className="btn btn-primary"
          />
        </div>
      )}
    </BTForm>
  );
}

type SettingFieldProps = {
  row: SettingRow;
  form: ReturnType<
    typeof useForm<{
      values: Record<string, string>;
      group: string;
    }>
  >;
  canEdit: boolean;
  canShow: boolean;
  onViewHistory: (key: string) => void;
  t: (key: string, options?: { defaultValue?: string }) => string;
};

function SettingField({ row, form, canEdit, canShow, onViewHistory, t }: SettingFieldProps) {
  const isTextarea = isTextareaRow(row);

  return (
    <FormGroup>
      <div className="d-flex align-items-center justify-content-between gap-2 mb-2 flex-wrap">
        <FormLabel className="fw-semibold text-gray-800 mb-0">
          {t(`settings.${row.key}`, { defaultValue: row.key })}
        </FormLabel>
        <span
          className={clsx(
            'badge rounded-pill px-3 py-2 fw-bold',
            settingsVisibilityBadgeClass(row.is_public),
          )}
        >
          {settingsVisibilityBadgeLabel(row.is_public, t)}
        </span>
      </div>
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
      {canShow && (
        <button
          type="button"
          className="btn btn-sm btn-light-primary mt-2"
          onClick={() => onViewHistory(row.key)}
        >
          {t('view_history')}
        </button>
      )}
    </FormGroup>
  );
}

type SettingHistoryModalProps = {
  settingKey: string | null;
  onClose: () => void;
};

function SettingHistoryModal({ settingKey, onClose }: SettingHistoryModalProps) {
  const { t, i18n } = useTranslation();
  const isRTL = i18n.dir() === 'rtl';
  const [items, setItems] = useState<HistoryItem[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!settingKey) {
      setItems([]);
      return;
    }

    let cancelled = false;
    setLoading(true);

    void apiGet<{ data: HistoryItem[] }>(SettingController.history(settingKey).url)
      .then((payload) => {
        if (!cancelled) {
          setItems(payload.data ?? []);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setItems([]);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [settingKey]);

  return (
    <Modal show={settingKey !== null} onHide={onClose} centered size="lg">
      <Modal.Header closeButton>
        <Modal.Title>
          {t('view_history')}
          {settingKey ? ` — ${t(`settings.${settingKey}`, { defaultValue: settingKey })}` : ''}
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        {loading ? (
          <div className="text-center py-8">
            <Spinner animation="border" size="sm" />
          </div>
        ) : items.length === 0 ? (
          <p className="text-muted fst-italic mb-0">{t('no_history')}</p>
        ) : (
          <div className="d-flex flex-column">
            {items.map((history, index) => (
              <div key={history.id} className="d-flex gap-4">
                <div className="d-flex flex-column align-items-center">
                  <div
                    className="rounded-circle border border-3 border-white shadow-sm"
                    style={{
                      width: 14,
                      height: 14,
                      backgroundColor: '#7239ea',
                      marginTop: 6,
                    }}
                  />
                  {index < items.length - 1 && (
                    <div className="flex-grow-1 w-2px bg-gray-200 my-1" style={{ minHeight: 40 }} />
                  )}
                </div>
                <div className="pb-6 flex-grow-1">
                  <div className="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <span className="badge badge-light rounded-pill px-3 py-2 fw-bold text-break">
                      {history.old_content ?? '—'}
                    </span>
                    <i className={`bi ${isRTL ? 'bi-arrow-left' : 'bi-arrow-right'} text-muted fs-8`} />
                    <span className="badge badge-light-primary rounded-pill px-3 py-2 fw-bold text-break">
                      {history.new_content ?? '—'}
                    </span>
                  </div>
                  <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span className="fw-semibold text-gray-800 fs-7">
                      {history.actor?.name ?? t('system')}
                    </span>
                    <span className="text-muted fs-8">
                      {history.created_at ? new Date(history.created_at).toLocaleString() : ''}
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </Modal.Body>
      <Modal.Footer>
        <Button variant="light" onClick={onClose}>
          {t('close')}
        </Button>
      </Modal.Footer>
    </Modal>
  );
}

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
