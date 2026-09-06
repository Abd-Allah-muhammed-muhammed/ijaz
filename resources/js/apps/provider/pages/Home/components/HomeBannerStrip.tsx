import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Pagination } from 'swiper/modules';
import type { Banner } from '@/shared/types/models';

import 'swiper/css';
import 'swiper/css/pagination';

export type HomeBannerStripProps = {
  banners: Banner[];
};

/**
 * Full-width image strip — card padding would inset the art,
 * so this uses a plain rounded overflow wrapper instead.
 */
const HOME_BANNER_STRIP_STYLE = `
.home-banner-strip {
  position: relative;
}
.home-banner-strip .swiper {
  overflow: hidden;
  border-radius: 0.75rem;
}
.home-banner-strip-image {
  display: block;
  width: 100%;
  aspect-ratio: 5 / 1;
  object-fit: cover;
}
.home-banner-strip .swiper-pagination {
  bottom: 0.5rem;
  left: 0;
  right: 0;
  width: 100%;
}
.home-banner-strip .swiper-pagination-bullet {
  width: 6px;
  height: 6px;
  background: rgba(255, 255, 255, 0.55);
  opacity: 1;
}
.home-banner-strip .swiper-pagination-bullet-active {
  background: rgba(255, 255, 255, 0.95);
}
`;

export default function HomeBannerStrip({ banners }: HomeBannerStripProps) {
  const { t } = useTranslation();
  const showPagination = banners.length > 1;
  const ariaLabel = t('banners');

  return (
    <div className="mb-5 home-banner-strip">
      <style>{HOME_BANNER_STRIP_STYLE}</style>
      <Swiper
        slidesPerView={1}
        modules={showPagination ? [Pagination] : []}
        pagination={showPagination ? { clickable: true } : false}
        className="d-block"
      >
        {banners.map((banner) => (
          <SwiperSlide key={banner.id}>
            <Link
              href={banner.link ?? '#'}
              className="d-block text-decoration-none"
              aria-label={ariaLabel}
            >
              <img
                src={banner.image ?? undefined}
                alt=""
                className="home-banner-strip-image"
              />
            </Link>
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  );
}
