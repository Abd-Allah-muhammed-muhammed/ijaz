import MasterLayout from "@/_metronic/layout/MasterLayout";
import { PageTitle } from '@/_metronic/layout/core';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { Content } from '@/_metronic/layout/components/content';
import { useTranslation } from 'react-i18next';
import { Head, router } from '@inertiajs/react';
import { Card, Col, Row, Tab, Nav, Form, Button, Badge, ProgressBar, Modal } from 'react-bootstrap';
import { ReactElement, useState } from 'react';
import { KTIcon } from '@/_metronic/helpers';
import ReactApexChart from 'react-apexcharts';
import { ApexOptions } from 'apexcharts';
import usePermissions from "@/hooks/use-permissions";
import PanAnalyticsController from '@/actions/App/Http/Controllers/Dashboard/PanAnalyticsController';


interface PanAnalytic {
  id: number;
  name: string;
  impressions: number;
  hovers: number;
  clicks: number;
  engagement_rate: number;
  click_rate: number;
  category: 'page' | 'button' | 'form' | 'other';
}

interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Props {
  analytics: PaginatedData<PanAnalytic>;
  summary: {
    total_impressions: number;
    total_hovers: number;
    total_clicks: number;
    overall_engagement_rate: number;
  };
  categories: Record<string, number>;
  topElements: PanAnalytic[];
  funnelData: {
    impressions: number;
    hovers: number;
    clicks: number;
  };
  params: {
    search?: string;
    category?: string;
    per_page?: number;
  };
}

const Index = ({ analytics, summary, categories, topElements, funnelData, params }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const [search, setSearch] = useState(params.search || '');
  const [showClearModal, setShowClearModal] = useState(false);
  const [activeCategory, setActiveCategory] = useState(params.category || 'all');
  const [isExporting, setIsExporting] = useState(false);

  // Calculate additional metrics
  const hoverToClickRate = summary.total_hovers > 0
    ? ((summary.total_clicks / summary.total_hovers) * 100).toFixed(2)
    : '0.00';

  const impressionToHoverRate = summary.total_impressions > 0
    ? ((summary.total_hovers / summary.total_impressions) * 100).toFixed(2)
    : '0.00';

  const overallConversionRate = summary.total_impressions > 0
    ? ((summary.total_clicks / summary.total_impressions) * 100).toFixed(2)
    : '0.00';

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get(PanAnalyticsController.index().url, {
      ...params,
      search: value,
      category: activeCategory !== 'all' ? activeCategory : undefined,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleCategoryChange = (category: string) => {
    setActiveCategory(category);
    router.get(PanAnalyticsController.index().url, {
      ...params,
      category: category !== 'all' ? category : undefined,
      search,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleExport = () => {
    setIsExporting(true);
    router.post(PanAnalyticsController.export().url, {}, {
      onFinish: () => setIsExporting(false),
    });
  };

  const handleClear = () => {
    router.delete(PanAnalyticsController.clear().url, {
      onSuccess: () => setShowClearModal(false),
    });
  };

  const handleClearFilters = () => {
    setSearch('');
    setActiveCategory('all');
    router.get(PanAnalyticsController.index().url, {}, {
      preserveState: false,
    });
  };

  // Enhanced bar chart configuration for top elements
  const barChartOptions: ApexOptions = {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      fontFamily: 'inherit',
      foreColor: '#a1a5b7',
    },
    plotOptions: {
      bar: {
        horizontal: false,
        borderRadius: 8,
        columnWidth: '60%',
        dataLabels: {
          position: 'top',
        },
      },
    },
    dataLabels: {
      enabled: true,
      offsetY: -25,
      style: {
        fontSize: '12px',
        fontWeight: 600,
        colors: ['#3F4254'],
      },
      background: {
        enabled: true,
        foreColor: '#ffffff',
        borderRadius: 4,
        padding: 4,
        opacity: 0.9,
        borderWidth: 0,
      },
    },
    xaxis: {
      categories: topElements.slice(0, 10).map(item => item.name),
      labels: {
        rotate: -45,
        rotateAlways: true,
        maxHeight: 100,
        style: {
          fontSize: '11px',
          fontWeight: 500,
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      title: {
        text: t('clicks'),
        style: {
          fontSize: '13px',
          fontWeight: 600,
        },
      },
      labels: {
        formatter: (val) => Math.floor(val).toString(),
      },
    },
    grid: {
      borderColor: '#eff2f5',
      strokeDashArray: 4,
      yaxis: {
        lines: {
          show: true,
        },
      },
    },
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'light',
        type: 'vertical',
        shadeIntensity: 0.5,
        gradientToColors: ['#34C759'],
        inverseColors: false,
        opacityFrom: 1,
        opacityTo: 0.8,
        stops: [0, 100],
      },
    },
    colors: ['#50CD89'],
    tooltip: {
      theme: 'light',
      y: {
        formatter: (val) => val.toLocaleString() + ' ' + t('clicks'),
      },
    },
    title: {
      text: t('top_10_elements_by_clicks'),
      align: 'left',
      margin: 20,
      offsetY: 0,
      style: {
        fontSize: '16px',
        fontWeight: 600,
        color: '#181C32',
      },
    },
  };

  const barChartSeries = [{
    name: t('clicks'),
    data: topElements.slice(0, 10).map(item => item.clicks),
  }];

  // Enhanced pie chart configuration for categories
  const pieChartOptions: ApexOptions = {
    chart: {
      type: 'donut',
      fontFamily: 'inherit',
    },
    labels: Object.keys(categories).map(cat => t(cat)),
    colors: ['#009EF7', '#50CD89', '#F1416C', '#FFC700'],
    legend: {
      position: 'bottom',
      fontSize: '13px',
      fontWeight: 500,
      markers: {
        width: 12,
        height: 12,
        radius: 12,
      },
      itemMargin: {
        horizontal: 10,
        vertical: 5,
      },
    },
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: '14px',
              fontWeight: 600,
              offsetY: -10,
            },
            value: {
              show: true,
              fontSize: '24px',
              fontWeight: 700,
              offsetY: 5,
              formatter: (val) => val.toString(),
            },
            total: {
              show: true,
              label: t('total_elements'),
              fontSize: '13px',
              fontWeight: 600,
              color: '#a1a5b7',
              formatter: () => {
                const total = Object.values(categories).reduce((a, b) => a + b, 0);
                return total.toString();
              },
            },
          },
        },
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (val: number) => val.toFixed(1) + '%',
      style: {
        fontSize: '12px',
        fontWeight: 600,
      },
      dropShadow: {
        enabled: false,
      },
    },
    tooltip: {
      theme: 'light',
      y: {
        formatter: (val) => val + ' ' + t('elements'),
      },
    },
    title: {
      text: t('distribution_by_category'),
      align: 'left',
      margin: 20,
      style: {
        fontSize: '16px',
        fontWeight: 600,
        color: '#181C32',
      },
    },
    states: {
      hover: {
        filter: {
          type: 'lighten',
          value: 0.1,
        },
      },
    },
  };

  const pieChartSeries = Object.values(categories);

  // Enhanced funnel chart configuration
  const funnelChartOptions: ApexOptions = {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      fontFamily: 'inherit',
      foreColor: '#a1a5b7',
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 8,
        barHeight: '70%',
        distributed: true,
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (val: number) => val.toLocaleString(),
      style: {
        fontSize: '14px',
        fontWeight: 600,
        colors: ['#ffffff'],
      },
      dropShadow: {
        enabled: true,
        top: 1,
        left: 1,
        blur: 1,
        opacity: 0.5,
      },
    },
    xaxis: {
      categories: [t('impressions'), t('hovers'), t('clicks')],
      labels: {
        formatter: (val) => val.toLocaleString(),
        style: {
          fontSize: '12px',
          fontWeight: 500,
        },
      },
      axisBorder: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        style: {
          fontSize: '13px',
          fontWeight: 600,
        },
      },
    },
    grid: {
      borderColor: '#eff2f5',
      strokeDashArray: 4,
      xaxis: {
        lines: {
          show: true,
        },
      },
    },
    colors: ['#009EF7', '#7239EA', '#F1416C'],
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'light',
        type: 'horizontal',
        shadeIntensity: 0.4,
        inverseColors: false,
        opacityFrom: 1,
        opacityTo: 0.8,
        stops: [0, 100],
      },
    },
    tooltip: {
      theme: 'light',
      y: {
        formatter: (val) => val.toLocaleString(),
      },
    },
    title: {
      text: t('conversion_funnel'),
      align: 'left',
      margin: 20,
      style: {
        fontSize: '16px',
        fontWeight: 600,
        color: '#181C32',
      },
    },
    legend: {
      show: false,
    },
  };

  const funnelChartSeries = [{
    name: t('count'),
    data: [funnelData.impressions, funnelData.hovers, funnelData.clicks],
  }];

  const getCategoryBadge = (category: string) => {
    const badges = {
      page: 'primary',
      button: 'success',
      form: 'warning',
      other: 'secondary',
    };
    return badges[category as keyof typeof badges] || 'secondary';
  };

  return (
    <>
      <Head title={t('pan_analytics')} />
      <PageTitle breadcrumbs={[]}>
        {/* <KTIcon iconName='chart-simple' className='fs-1 me-2' /> */}
        {t('pan_analytics')}
      </PageTitle>
      <ToolbarWrapper>
        <div className="d-flex align-items-center gap-2 gap-lg-3">
          <Form.Control
            type="text"
            placeholder={t('search')}
            value={search}
            onChange={(e) => handleSearch(e.target.value)}
            className="w-250px"
          />

          {hasPermission('export panAnalytics') && (
            <Button
              variant="light-primary"
              onClick={handleExport}
              disabled={isExporting}
            >
              <KTIcon iconName='file-down' className='fs-2' />
              {isExporting ? t('exporting') : t('export_csv')}
            </Button>
          )}

          {hasPermission('delete panAnalytics') && (
            <Button
              variant="light-danger"
              onClick={() => setShowClearModal(true)}
            >
              <KTIcon iconName='trash' className='fs-2' />
              {t('clear_analytics')}
            </Button>
          )}
        </div>
      </ToolbarWrapper>
      <Content>
        {/* Enhanced Summary Cards with Gradients */}
        <Row className="g-5 g-xl-8 mb-5">
          <Col xl={3} lg={6}>
            <Card className="h-100 shadow-sm border-0" style={{
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              transition: 'transform 0.2s ease-in-out',
            }}
              onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
              onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}
            >
              <Card.Body className="d-flex flex-column p-6">
                <div className="d-flex align-items-center justify-content-between mb-4">
                  <div className="symbol symbol-55px">
                    <div className="symbol-label" style={{ backgroundColor: 'rgba(255,255,255,0.2)' }}>
                      <KTIcon iconName='eye' className='fs-1 text-white' />
                    </div>
                  </div>
                  <div className="text-end">
                    <span className="text-white fw-semibold fs-7 d-block opacity-75 mb-1">
                      {t('total_impressions')}
                    </span>
                    <span className="text-white fw-bolder fs-2x d-block">
                      {summary.total_impressions.toLocaleString()}
                    </span>
                  </div>
                </div>
                <div className="d-flex align-items-center">
                  <div className="badge badge-light-primary fw-bold px-3 py-2">
                    <KTIcon iconName='arrow-up' className='fs-5 me-1' />
                    {t('views')}
                  </div>
                </div>
              </Card.Body>
            </Card>
          </Col>

          <Col xl={3} lg={6}>
            <Card className="h-100 shadow-sm border-0" style={{
              background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
              transition: 'transform 0.2s ease-in-out',
            }}
              onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
              onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}
            >
              <Card.Body className="d-flex flex-column p-6">
                <div className="d-flex align-items-center justify-content-between mb-4">
                  <div className="symbol symbol-55px">
                    <div className="symbol-label" style={{ backgroundColor: 'rgba(255,255,255,0.2)' }}>
                      <KTIcon iconName='hand' className='fs-1 text-white' />
                    </div>
                  </div>
                  <div className="text-end">
                    <span className="text-white fw-semibold fs-7 d-block opacity-75 mb-1">
                      {t('total_hovers')}
                    </span>
                    <span className="text-white fw-bolder fs-2x d-block">
                      {summary.total_hovers.toLocaleString()}
                    </span>
                  </div>
                </div>
                <div className="d-flex align-items-center justify-content-between">
                  <div className="badge badge-light-success fw-bold px-3 py-2">
                    {impressionToHoverRate}% {t('from_views')}
                  </div>
                </div>
              </Card.Body>
            </Card>
          </Col>

          <Col xl={3} lg={6}>
            <Card className="h-100 shadow-sm border-0" style={{
              background: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
              transition: 'transform 0.2s ease-in-out',
            }}
              onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
              onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}
            >
              <Card.Body className="d-flex flex-column p-6">
                <div className="d-flex align-items-center justify-content-between mb-4">
                  <div className="symbol symbol-55px">
                    <div className="symbol-label" style={{ backgroundColor: 'rgba(255,255,255,0.2)' }}>
                      <KTIcon iconName='click' className='fs-1 text-white' />
                    </div>
                  </div>
                  <div className="text-end">
                    <span className="text-white fw-semibold fs-7 d-block opacity-75 mb-1">
                      {t('total_clicks')}
                    </span>
                    <span className="text-white fw-bolder fs-2x d-block">
                      {summary.total_clicks.toLocaleString()}
                    </span>
                  </div>
                </div>
                <div className="d-flex align-items-center justify-content-between">
                  <div className="badge badge-light-warning fw-bold px-3 py-2">
                    {hoverToClickRate}% {t('from_hovers')}
                  </div>
                </div>
              </Card.Body>
            </Card>
          </Col>

          <Col xl={3} lg={6}>
            <Card className="h-100 shadow-sm border-0" style={{
              background: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
              transition: 'transform 0.2s ease-in-out',
            }}
              onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
              onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}
            >
              <Card.Body className="d-flex flex-column p-6">
                <div className="d-flex align-items-center justify-content-between mb-4">
                  <div className="symbol symbol-55px">
                    <div className="symbol-label" style={{ backgroundColor: 'rgba(255,255,255,0.2)' }}>
                      <KTIcon iconName='chart-line-up' className='fs-1 text-white' />
                    </div>
                  </div>
                  <div className="text-end">
                    <span className="text-white fw-semibold fs-7 d-block opacity-75 mb-1">
                      {t('engagement_rate')}
                    </span>
                    <span className="text-white fw-bolder fs-2x d-block">
                      {summary.overall_engagement_rate}%
                    </span>
                  </div>
                </div>
                <div className="d-flex align-items-center">
                  <div className="badge badge-light-info fw-bold px-3 py-2">
                    <KTIcon iconName='arrow-up' className='fs-5 me-1' />
                    {t('user_interaction')}
                  </div>
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row>

        {/* Additional Conversion Metrics */}
        <Row className="g-5 g-xl-8 mb-5">
          <Col xl={4}>
            <Card className="h-100 shadow-sm">
              <Card.Body className="p-6">
                <div className="d-flex align-items-center mb-4">
                  <div className="symbol symbol-45px me-3">
                    <div className="symbol-label bg-light-primary">
                      <KTIcon iconName='chart-simple-2' className='fs-2 text-primary' />
                    </div>
                  </div>
                  <div className="flex-grow-1">
                    <span className="text-gray-700 fw-semibold fs-6 d-block">
                      {t('overall_conversion_rate')}
                    </span>
                    <span className="text-gray-500 fw-semibold fs-7">
                      {t('impressions_to_clicks')}
                    </span>
                  </div>
                </div>
                <div className="d-flex align-items-end">
                  <span className="text-gray-900 fw-bolder fs-2x me-2">
                    {overallConversionRate}%
                  </span>
                  <span className="text-success fw-bold fs-6 mb-1">
                    <KTIcon iconName='arrow-up' className='fs-5' />
                  </span>
                </div>
                <ProgressBar
                  now={parseFloat(overallConversionRate)}
                  variant="primary"
                  className="h-8px mt-4"
                  style={{ borderRadius: '4px' }}
                />
              </Card.Body>
            </Card>
          </Col>

          <Col xl={4}>
            <Card className="h-100 shadow-sm">
              <Card.Body className="p-6">
                <div className="d-flex align-items-center mb-4">
                  <div className="symbol symbol-45px me-3">
                    <div className="symbol-label bg-light-success">
                      <KTIcon iconName='abstract-26' className='fs-2 text-success' />
                    </div>
                  </div>
                  <div className="flex-grow-1">
                    <span className="text-gray-700 fw-semibold fs-6 d-block">
                      {t('hover_conversion')}
                    </span>
                    <span className="text-gray-500 fw-semibold fs-7">
                      {t('hovers_to_clicks')}
                    </span>
                  </div>
                </div>
                <div className="d-flex align-items-end">
                  <span className="text-gray-900 fw-bolder fs-2x me-2">
                    {hoverToClickRate}%
                  </span>
                  <span className="text-success fw-bold fs-6 mb-1">
                    <KTIcon iconName='arrow-up' className='fs-5' />
                  </span>
                </div>
                <ProgressBar
                  now={parseFloat(hoverToClickRate)}
                  variant="success"
                  className="h-8px mt-4"
                  style={{ borderRadius: '4px' }}
                />
              </Card.Body>
            </Card>
          </Col>

          <Col xl={4}>
            <Card className="h-100 shadow-sm">
              <Card.Body className="p-6">
                <div className="d-flex align-items-center mb-4">
                  <div className="symbol symbol-45px me-3">
                    <div className="symbol-label bg-light-warning">
                      <KTIcon iconName='abstract-41' className='fs-2 text-warning' />
                    </div>
                  </div>
                  <div className="flex-grow-1">
                    <span className="text-gray-700 fw-semibold fs-6 d-block">
                      {t('interest_rate')}
                    </span>
                    <span className="text-gray-500 fw-semibold fs-7">
                      {t('impressions_to_hovers')}
                    </span>
                  </div>
                </div>
                <div className="d-flex align-items-end">
                  <span className="text-gray-900 fw-bolder fs-2x me-2">
                    {impressionToHoverRate}%
                  </span>
                  <span className="text-success fw-bold fs-6 mb-1">
                    <KTIcon iconName='arrow-up' className='fs-5' />
                  </span>
                </div>
                <ProgressBar
                  now={parseFloat(impressionToHoverRate)}
                  variant="warning"
                  className="h-8px mt-4"
                  style={{ borderRadius: '4px' }}
                />
              </Card.Body>
            </Card>
          </Col>
        </Row>

        {/* Enhanced Charts Section */}
        <Row className="g-5 g-xl-8 mb-5">
          <Col xl={6}>
            <Card className="shadow-sm border-0">
              <Card.Body className="p-6">
                <ReactApexChart
                  options={barChartOptions}
                  series={barChartSeries}
                  type="bar"
                  height={380}
                />
              </Card.Body>
            </Card>
          </Col>

          <Col xl={6}>
            <Card className="shadow-sm border-0">
              <Card.Body className="p-6">
                <ReactApexChart
                  options={pieChartOptions}
                  series={pieChartSeries}
                  type="donut"
                  height={380}
                />
              </Card.Body>
            </Card>
          </Col>
        </Row>

        <Row className="g-5 mb-5">
          <Col xl={12}>
            <Card className="shadow-sm border-0">
              <Card.Body className="p-6">
                <ReactApexChart
                
                  options={funnelChartOptions}
                  series={funnelChartSeries}
                  type="bar"
                  height={280}
                />
              </Card.Body>
            </Card>
          </Col>
        </Row>

        {/* Categorized Data Table */}
        <Card>
          <Card.Header>
            <Card.Title>{t('analytics_data')}</Card.Title>
            {(search || activeCategory !== 'all') && (
              <div className="card-toolbar">
                <Button
                  variant="light-primary"
                  size="sm"
                  onClick={handleClearFilters}
                >
                  <KTIcon iconName='filter-search' className='fs-3' />
                  {t('clear_filters')}
                </Button>
              </div>
            )}
          </Card.Header>
          <Card.Body>
            <Tab.Container activeKey={activeCategory} onSelect={(k) => k && handleCategoryChange(k)}>
              <Nav variant="tabs" className="mb-4">
                <Nav.Item>
                  <Nav.Link eventKey="all">{t('all_elements')} ({analytics.total})</Nav.Link>
                </Nav.Item>
                <Nav.Item>
                  <Nav.Link eventKey="page">{t('pages')} ({categories.page || 0})</Nav.Link>
                </Nav.Item>
                <Nav.Item>
                  <Nav.Link eventKey="button">{t('buttons')} ({categories.button || 0})</Nav.Link>
                </Nav.Item>
                <Nav.Item>
                  <Nav.Link eventKey="form">{t('forms')} ({categories.form || 0})</Nav.Link>
                </Nav.Item>
                <Nav.Item>
                  <Nav.Link eventKey="other">{t('other')} ({categories.other || 0})</Nav.Link>
                </Nav.Item>
              </Nav>

              <div className="table-responsive">
                <table className="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                  <thead>
                    <tr className="fw-bold text-muted">
                      <th className="min-w-150px">{t('element_name')}</th>
                      <th className="min-w-100px">{t('category')}</th>
                      <th className="min-w-100px text-end">{t('impressions')}</th>
                      <th className="min-w-100px text-end">{t('hovers')}</th>
                      <th className="min-w-100px text-end">{t('clicks')}</th>
                      <th className="min-w-150px">{t('engagement_rate')}</th>
                      <th className="min-w-150px">{t('click_rate')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {analytics.data.map((item) => (
                      <tr key={item.id}>
                        <td>
                          <span className="text-gray-900 fw-bold d-block fs-6">
                            {item.name}
                          </span>
                        </td>
                        <td>
                          <Badge bg={getCategoryBadge(item.category)}>
                            {t(item.category)}
                          </Badge>
                        </td>
                        <td className="text-end">
                          <span className="text-gray-900 fw-bold">
                            {item.impressions.toLocaleString()}
                          </span>
                        </td>
                        <td className="text-end">
                          <span className="text-gray-900 fw-bold">
                            {item.hovers.toLocaleString()}
                          </span>
                        </td>
                        <td className="text-end">
                          <span className="text-gray-900 fw-bold">
                            {item.clicks.toLocaleString()}
                          </span>
                        </td>
                        <td>
                          <div className="d-flex flex-column w-100 me-2">
                            <div className="d-flex flex-stack mb-2">
                              <span className="text-muted me-2 fs-7 fw-semibold">
                                {item.engagement_rate}%
                              </span>
                            </div>
                            <ProgressBar
                              now={item.engagement_rate}
                              variant="success"
                              className="h-6px w-100"
                            />
                          </div>
                        </td>
                        <td>
                          <div className="d-flex flex-column w-100 me-2">
                            <div className="d-flex flex-stack mb-2">
                              <span className="text-muted me-2 fs-7 fw-semibold">
                                {item.click_rate}%
                              </span>
                            </div>
                            <ProgressBar
                              now={item.click_rate}
                              variant="primary"
                              className="h-6px w-100"
                            />
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {analytics.data.length === 0 && (
                <div className="text-center py-10">
                  <KTIcon iconName='file' className='fs-3x text-muted mb-3' />
                  <div className="text-muted fw-semibold fs-6">
                    {t('no_data_available')}
                  </div>
                </div>
              )}

              {/* Pagination */}
              {analytics.last_page > 1 && (
                <div className="d-flex justify-content-between align-items-center mt-5">
                  <div className="text-muted">
                    {t('showing')} {analytics.data.length} {t('of')} {analytics.total} {t('results')}
                  </div>
                  <nav>
                    <ul className="pagination">
                      {Array.from({ length: analytics.last_page }, (_, i) => i + 1).map((page) => (
                        <li key={page} className={`page-item ${analytics.current_page === page ? 'active' : ''}`}>
                          <button
                            className="page-link"
                            onClick={() => router.get(PanAnalyticsController.index().url, {
                              ...params,
                              page,
                            })}
                          >
                            {page}
                          </button>
                        </li>
                      ))}
                    </ul>
                  </nav>
                </div>
              )}
            </Tab.Container>
          </Card.Body>
        </Card>
      </Content>

      {/* Clear Analytics Confirmation Modal */}
      <Modal show={showClearModal} onHide={() => setShowClearModal(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>{t('confirm_clear_analytics')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="text-center py-5">
            <KTIcon iconName='information' className='fs-3x text-warning mb-5' />
            <p className="fs-5 fw-semibold text-gray-800">
              {t('clear_analytics_warning')}
            </p>
            <p className="text-muted">
              {t('this_action_cannot_be_undone')}
            </p>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowClearModal(false)}>
            {t('cancel')}
          </Button>
          <Button variant="danger" onClick={handleClear}>
            {t('yes_clear_analytics')}
          </Button>
        </Modal.Footer>
      </Modal>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
