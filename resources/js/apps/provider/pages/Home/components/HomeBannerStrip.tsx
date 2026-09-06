import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Pagination } from 'swiper/modules';
import { KTIcon } from '@/vendor/metronic/helpers';
import { SectionCard } from '@/shared/components/ui';
import type { Banner } from '@/shared/types/models';
import { bannerAffordanceLabel } from '@/apps/provider/pages/Home/components/home-banner-strip-utils';

import 'swiper/css';
import 'swiper/css/pagination';

export type HomeBannerStripProps = {
  banners: Banner[];
};

export { bannerAffordanceLabel };

const HOME_BANNER_STRIP_STYLE = `
.home-banner-strip .swiper-pagination {
  position: static;
  margin-top: 0.5rem;
  min-height: 0.75rem;
}
.home-banner-strip .swiper-pagination-bullet {
  width: 6px;
  height: 6px;
  background: var(--bs-gray-400);
  opacity: 1;
}
.home-banner-strip .swiper-pagination-bullet-active {
  background: var(--bs-primary);
}
.home-banner-strip-thumb {
  width: 80px;
  height: 45px;
}
`;

export default function HomeBannerStrip({ banners }: HomeBannerStripProps) {
  const { t } = useTranslation();
  const viewMoreLabel = t('view_more');
  const showPagination = banners.length > 1;

  return (
    <SectionCard className="mb-5 home-banner-strip" bodyClassName="card-body py-3 px-4 px-lg-6">
      <style>{HOME_BANNER_STRIP_STYLE}</style>
      <Swiper
        slidesPerView={1}
        modules={showPagination ? [Pagination] : []}
        pagination={showPagination ? { clickable: true } : false}
        className="d-block"
      >
        {banners.map((banner) => {
          const label = bannerAffordanceLabel(banner, viewMoreLabel);

          return (
            <SwiperSlide key={banner.id}>
              <Link
                href={banner.link ?? '#'}
                className="d-flex align-items-center gap-3 text-decoration-none text-gray-900"
              >
                <img
                  src={banner.image ?? undefined}
                  alt=""
                  className="home-banner-strip-thumb object-fit-cover rounded flex-shrink-0"
                />
                <span className="fw-semibold fs-7 text-truncate min-w-0 flex-grow-1" title={label}>
                  {label}
                </span>
                <KTIcon iconName="arrow-right" className="fs-3 text-primary flex-shrink-0" />
              </Link>
            </SwiperSlide>
          );
        })}
      </Swiper>
    </SectionCard>
  );
}
