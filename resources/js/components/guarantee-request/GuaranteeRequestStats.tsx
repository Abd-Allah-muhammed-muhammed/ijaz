import { KTIcon } from '@/_metronic/helpers';
import { useTranslation } from 'react-i18next';

type Props = {
    stats: {
        total: number;
        active: number;
        pending: number;
        completed: number;
        cancelled: number;
    };
};

const GuaranteeRequestStats = ({ stats }: Props) => {
    const { t } = useTranslation();

    const statItems = [
        {
            title: t('total_requests'),
            value: stats.total,
            icon: 'abstract-14',
            color: 'primary',
            bg: 'bg-light-primary',
        },
        {
            title: t('active_requests'),
            value: stats.active,
            icon: 'abstract-24',
            color: 'info',
            bg: 'bg-light-info',
        },
        {
            title: t('pending_requests'),
            value: stats.pending,
            icon: 'time',
            color: 'warning',
            bg: 'bg-light-warning',
        },
        {
            title: t('completed_requests'),
            value: stats.completed,
            icon: 'check-circle',
            color: 'success',
            bg: 'bg-light-success',
        },
        {
            title: t('cancelled_requests'),
            value: stats.cancelled,
            icon: 'cross-circle',
            color: 'danger',
            bg: 'bg-light-danger',
        },
    ];

    return (
        <div className='row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-5 g-xl-8 mb-5'>
            {statItems.map((item, index) => (
                <div className='col' key={index}>
                    <div className={`card card-xl-stretch mb-xl-8 ${item.bg} border-0 shadow-sm`}>
                        <div className='card-body d-flex flex-column'>
                            <div className='d-flex flex-stack mb-3'>
                                <div className={`symbol symbol-40px symbol-circle`}>
                                    <div className={`symbol-label bg-white text-${item.color} shadow-sm`}>
                                        <KTIcon iconName={item.icon} className={`fs-2 text-${item.color}`} />
                                    </div>
                                </div>
                            </div>
                            <div className='d-flex flex-column'>
                                <div className={`text-${item.color} fw-bold fs-2xh mb-2 mt-2`}>{item.value}</div>
                                <div className={`fw-bold text-${item.color} fs-7`}>{item.title}</div>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default GuaranteeRequestStats;
