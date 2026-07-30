import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';

type Props = {
  name?: string,
  data: number[]|string[],
}
const ChartsWidget4 = ({name = "", data} : Props) => {
  const chartRef = useRef(null);
  const chartInstance = useRef(null);
  const [isDark, setIsDark] = useState(false);
  const locale = usePage().props.app.locale;

  useEffect(() => {
    // Check if dark mode is enabled
    const checkDarkMode = () => {
      return document.documentElement.classList.contains('dark') ||
             window.matchMedia('(prefers-color-scheme: dark)').matches;
    };

    setIsDark(checkDarkMode());

    // Get CSS variable value helper
    const getCssVar = (name: string) => {
      return getComputedStyle(document.documentElement)
        .getPropertyValue(name).trim();
    };

    const initChart = () => {
      if (!chartRef.current) return;

      const height = 350;
      const labelColor = getCssVar('--bs-gray-500') || '#a1a5b7';
      const borderColor = getCssVar('--bs-border-dashed-color') || '#e4e6ef';
      const baseColor = getCssVar('--bs-primary') || '#3699ff';
      const lightColor = getCssVar('--bs-primary') || '#3699ff';

      const generateDateLabels = (length: number) => {
        const today = new Date();
        const labels = [];

        for (let i = length - 1; i >= 0; i--) {
          const date = new Date(today);
          date.setDate(today.getDate() - i);

          const month = date.toLocaleDateString(locale === 'ar' ? 'ar' : 'en-US', { month: 'short' });
          const day = date.getDate();
          labels.push(`${month} ${day}`);
        }

        return labels;
      };

      const dateLabels = generateDateLabels(data.length);

      const options = {
        series: [{
          name: name,
          data: data
        }],
        chart: {
          fontFamily: 'inherit',
          type: 'area',
          height: height,
          toolbar: {
            show: false
          }
        },
        plotOptions: {},
        legend: {
          show: false
        },
        dataLabels: {
          enabled: false
        },
        fill: {
          type: "gradient",
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0,
            stops: [0, 80, 100]
          }
        },
        stroke: {
          curve: 'smooth',
          show: true,
          width: 3,
          colors: [baseColor]
        },
        xaxis: {
          categories: ["", ...dateLabels, ""],
          axisBorder: {
            show: false,
          },
          axisTicks: {
            show: false
          },
          tickAmount: 6,
          labels: {
            rotate: 0,
            rotateAlways: true,
            style: {
              colors: labelColor,
              fontSize: '12px'
            }
          },
          crosshairs: {
            position: 'front',
            stroke: {
              color: baseColor,
              width: 1,
              dashArray: 3
            }
          },
          tooltip: {
            enabled: true,
            formatter: undefined,
            offsetY: 0,
            style: {
              fontSize: '12px'
            },
          }
        },
        yaxis: {
          max: 36.3,
          min: 33,
          tickAmount: 6,
          labels: {
            style: {
              colors: labelColor,
              fontSize: '12px'
            },
            formatter: function (val: number|string) {
              return Number(val).toFixed(2);
            }
          }
        },
        states: {
          normal: {
            filter: {
              type: 'none',
              value: 0
            }
          },
          hover: {
            filter: {
              type: 'none',
              value: 0
            }
          },
          active: {
            allowMultipleDataPointsSelection: false,
            filter: {
              type: 'none',
              value: 0
            }
          }
        },
        tooltip: {
          style: {
            fontSize: '12px'
          },
          y: {
            formatter: function (val: string|number) {
              return val;
            }
          }
        },
        colors: [lightColor],
        grid: {
          borderColor: borderColor,
          strokeDashArray: 4,
          yaxis: {
            lines: {
              show: true
            }
          }
        },
        markers: {
          strokeColor: baseColor,
          strokeWidth: 3
        }
      };

      // Destroy existing chart if it exists
      if (chartInstance.current) {
        chartInstance.current.destroy();
      }

      // Create new chart
      chartInstance.current = new ApexCharts(chartRef.current, options);
      chartInstance.current.render();
    };

    // Load ApexCharts from CDN
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.44.0/apexcharts.min.js';
    script.async = true;
    script.onload = () => {
      setTimeout(initChart, 200);
    };
    document.body.appendChild(script);

    // Cleanup
    return () => {
      if (chartInstance.current) {
        chartInstance.current.destroy();
      }
      if (script.parentNode) {
        script.parentNode.removeChild(script);
      }
    };
  }, [isDark]);

  return (
      <div ref={chartRef} className="w-full"></div>
  );
};

export default ChartsWidget4;
