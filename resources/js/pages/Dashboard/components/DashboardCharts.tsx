import React from 'react'
import { useTranslation } from 'react-i18next'
import ReactApexChart from 'react-apexcharts'
import { ApexOptions } from 'apexcharts'
import { getCSSVariableValue } from '@/_metronic/assets/ts/_utils'

type RegistrationChartsProps = {
  dates: string[]
  userRegistrations: number[]
  providerRegistrations: number[]
}

export const RegistrationChart: React.FC<RegistrationChartsProps> = ({ dates, userRegistrations, providerRegistrations }) => {
  const labelColor = getCSSVariableValue('--bs-gray-500')
  const borderColor = getCSSVariableValue('--bs-gray-200')
  const baseColor = getCSSVariableValue('--bs-primary')
  const secondaryColor = getCSSVariableValue('--bs-success')

  const options: ApexOptions = {
    chart: {
      fontFamily: 'inherit',
      type: 'area',
      height: 350,
      toolbar: {
        show: false,
      },
    },
    plotOptions: {},
    legend: {
      show: true,
      position: 'top',
      horizontalAlign: 'right',
    },
    dataLabels: {
      enabled: false,
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.7,
        opacityTo: 0.3,
        stops: [0, 90, 100]
      }
    },
    stroke: {
      curve: 'smooth',
      show: true,
      width: 3,
    },
    xaxis: {
      categories: dates,
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        style: {
          colors: labelColor,
          fontSize: '12px',
        },
      },
    },
    yaxis: {
      labels: {
        style: {
          colors: labelColor,
          fontSize: '12px',
        },
      },
    },
    colors: [baseColor, secondaryColor],
    grid: {
      borderColor: borderColor,
      strokeDashArray: 4,
      yaxis: {
        lines: {
          show: true,
        },
      },
    },
  };

  const series = [
    {
      name: 'Users',
      data: userRegistrations,
    },
    {
      name: 'Providers',
      data: providerRegistrations,
    },
  ];

  return <ReactApexChart options={options} series={series} type="area" height={350} />
}

type RevenueChartProps = {
  dates: string[]
  revenue: number[]
}

export const RevenueChart: React.FC<RevenueChartProps> = ({ dates, revenue }) => {
  const labelColor = getCSSVariableValue('--bs-gray-500')
  const borderColor = getCSSVariableValue('--bs-gray-200')
  const baseColor = getCSSVariableValue('--bs-info')

  const options: ApexOptions = {
    chart: {
      fontFamily: 'inherit',
      type: 'bar',
      height: 350,
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '50%',
        borderRadius: 5
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: true,
      width: 2,
      colors: ['transparent'],
    },
    xaxis: {
      categories: dates,
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        style: {
          colors: labelColor,
          fontSize: '12px',
        },
      },
    },
    yaxis: {
      labels: {
        style: {
          colors: labelColor,
          fontSize: '12px',
        },
      },
    },
    fill: {
      opacity: 1,
    },
    colors: [baseColor],
    grid: {
      borderColor: borderColor,
      strokeDashArray: 4,
      yaxis: {
        lines: {
          show: true,
        },
      },
    },
    tooltip: {
      style: {
        fontSize: '12px',
      },
      y: {
        formatter: function (val) {
          return val + ' SAR'
        },
      },
    },
  };

  const series = [
    {
      name: 'Revenue',
      data: revenue,
    },
  ];

  return <ReactApexChart options={options} series={series} type="bar" height={350} />
}

type OrderStatusChartProps = {
  distribution: Record<string, number>
}

export const OrderStatusChart: React.FC<OrderStatusChartProps> = ({ distribution }) => {
  const baseColor = getCSSVariableValue('--bs-primary')
  const successColor = getCSSVariableValue('--bs-success')
  const dangerColor = getCSSVariableValue('--bs-danger')
  const warningColor = getCSSVariableValue('--bs-warning')
  const infoColor = getCSSVariableValue('--bs-info')

  const { t } = useTranslation()
  const labels = Object.keys(distribution).map(key => t(key))
  const series = Object.values(distribution)

  const options: ApexOptions = {
    chart: {
      fontFamily: 'inherit',
      type: 'donut',
      height: 350,
    },
    plotOptions: {
      pie: {
        donut: {
          size: '70%',
          labels: {
            show: true,
            total: {
              show: true,
              label: t('orders'),
              formatter: () => series.reduce((a, b) => a + b, 0).toString(),
            },
          },
        },
      },
    },
    labels: labels,
    colors: [baseColor, successColor, dangerColor, warningColor, infoColor],
    legend: {
      show: true,
      position: 'bottom',
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 0,
    },
  }

  return <ReactApexChart options={options} series={series} type="donut" height={350} />
}
