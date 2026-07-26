import { useTranslation } from 'react-i18next';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { PageTitle } from '@/_metronic/layout/core';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { Content } from '@/_metronic/layout/components/content';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { KTCard } from '@/_metronic/helpers';
import { ReactElement, useMemo, useState } from 'react';
import { Card, Col, Form as BTForm, FormControl, FormGroup, FormLabel, Nav, Row, Tab } from 'react-bootstrap';
import SettingController from '@/actions/Modules/Settings/Http/Controllers/Dashboard/SettingController';
import usePermissions from '@/hooks/use-permissions';
import InputError from '@/components/inputs/InputError';
import ActionButton from '@/components/action-button';

type SettingRow = {
  id: number;
  key: string;
  content: string;
  group: string;
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
        <KTCard className="p-4">
          <Tab.Container
            activeKey={activeTab}
            onSelect={(key) => {
              if (key) {
                setActiveTab(key);
              }
            }}
          >
            <ul className="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-6">
              {tabs.map((group) => (
                <li className="nav-item" key={group}>
                  <Nav.Link className="nav-link text-active-primary py-5 me-6" eventKey={group}>
                    {t(group)}
                  </Nav.Link>
                </li>
              ))}
            </ul>
            <Tab.Content>
              {tabs.map((group) => (
                <Tab.Pane eventKey={group} key={group}>
                  <SettingsGroupForm
                    group={group}
                    rows={groups[group] ?? []}
                    canEdit={canEdit}
                    t={t}
                  />
                </Tab.Pane>
              ))}
            </Tab.Content>
          </Tab.Container>
        </KTCard>
      </Content>
    </>
  );
};

type FormProps = {
  group: string;
  rows: SettingRow[];
  canEdit: boolean;
  t: (key: string) => string;
};

function SettingsGroupForm({ group, rows, canEdit, t }: FormProps) {
  const initialValues = useMemo(
    () =>
      rows.reduce<Record<string, string>>((carry, row) => {
        carry[row.key] = row.content ?? '';
        return carry;
      }, {}),
    [rows],
  );

  const form = useForm<{ values: Record<string, string>; group: string }>({
    values: initialValues,
    group,
  });

  if (rows.length === 0) {
    return (
      <Card className="border-0 shadow-none">
        <Card.Body className="text-muted py-10 text-center">
          {t('no_data')}
        </Card.Body>
      </Card>
    );
  }

  return (
    <BTForm
      onSubmit={(e) => {
        e.preventDefault();
        if (!canEdit) {
          return;
        }
        form.transform((data) => ({
          values: data.values,
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
      <Row>
        {rows.map((row) => (
          <Col sm={12} md={6} className="mb-4" key={row.key}>
            <FormGroup>
              <FormLabel className="fw-semibold">{row.key}</FormLabel>
              <FormControl
                as={row.content && row.content.length > 80 ? 'textarea' : 'input'}
                rows={row.content && row.content.length > 80 ? 4 : undefined}
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
            </FormGroup>
          </Col>
        ))}
      </Row>
      {canEdit && (
        <Row>
          <Col sm={12} className="d-flex justify-content-end">
            <ActionButton
              isProcessing={form.processing}
              text={t('save')}
              className="btn btn-primary"
            />
          </Col>
        </Row>
      )}
    </BTForm>
  );
}

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
