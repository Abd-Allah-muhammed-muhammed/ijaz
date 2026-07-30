import AdminController from '@/actions/App/Http/Controllers/Dashboard/AdminController';
import BannerController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/BannerController';
import CarBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarBrandController';
import CarCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarCategoryController';
import CarTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarTypeController';
import CategoryController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/CategoryController';
import CityController from '@/actions/Modules/Geo/Http/Controllers/Dashboard/CityController';
import HomeController from '@/actions/App/Http/Controllers/Dashboard/HomeController';
import MessageController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/MessageController';
import NationalityController from '@/actions/Modules/Geo/Http/Controllers/Dashboard/NationalityController';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Dashboard/OrderController';
import PageController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController';
import PanAnalyticsController from '@/actions/App/Http/Controllers/Dashboard/PanAnalyticsController';
import PropertyCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyCategoryController';
import PropertyTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyTypeController';
import ProviderController from '@/actions/App/Http/Controllers/Dashboard/ProviderController';
import ProviderTypeController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/ProviderTypeController';
import QuestionController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/QuestionController';
import RegionController from '@/actions/Modules/Geo/Http/Controllers/Dashboard/RegionController';
import RoleController from '@/actions/App/Http/Controllers/Dashboard/RoleController';
import SkillController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/SkillController';
import SupportController from '@/actions/Modules/Support/Http/Controllers/Dashboard/SupportController';
import TopUpRequestController from '@/actions/Modules/Wallet/Http/Controllers/Dashboard/TopUpRequestController';
import UserController from '@/actions/App/Http/Controllers/Dashboard/UserController';
import WithdrawRequestController from '@/actions/Modules/Wallet/Http/Controllers/Dashboard/WithdrawRequestController';
import DeviceCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/DeviceCategoryController';
import ElectronicBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/ElectronicBrandController';
import SpecializationController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/SpecializationController';
import InstituteAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/InstituteAdvisementController';
import OpportunityController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/OpportunityController';
import GuarantorDashboardController from '@/actions/Modules/Guarantor/Http/Controllers/Dashboard/GuarantorController';
import PropertyAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/PropertyAdvisementController';
import CarAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/CarAdvisementController';
import ElectronicAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/ElectronicAdvisementController';
import SettingController from '@/actions/Modules/Settings/Http/Controllers/Dashboard/SettingController';
import ReviewController from '@/actions/Modules/Reviews/Http/Controllers/Dashboard/ReviewController';
import useActiveRoute from '@/shared/hooks/use-active-route';
import usePermissions from '@/shared/hooks/use-permissions';
import type { SidebarNavSection } from '@/shared/components/Sidebar';
import { useTranslation } from 'react-i18next';
import { useMemo } from 'react';

/**
 * Admin sidebar navigation — like-for-like port of the former Metronic
 * SidebarMenuMain (15 section groupings, same order, same permissions).
 */
export function useAdminSidebarSections(): SidebarNavSection[] {
  const { matchUrl, matchComponents } = useActiveRoute();
  const { hasPermission, hasAnyPermission } = usePermissions();
  const { t } = useTranslation();

  return useMemo(
    (): SidebarNavSection[] => [
      // 1. Dashboard (standalone)
      {
        items: [
          {
            title: t('dashboard'),
            href: HomeController.url(),
            icon: 'element-11',
            isActive: matchUrl(HomeController.url()),
          },
        ],
      },

      // 2. Orders
      {
        title: t('orders'),
        show: hasPermission('show orders'),
        items: [
          {
            title: t('orders'),
            href: OrderController.index().url,
            icon: 'basket',
            isActive: matchComponents('dashboard.orders.*'),
            show: hasPermission('show orders'),
          },
        ],
      },

      // 3. Administration
      {
        title: t('administration'),
        show: hasAnyPermission(['show roles', 'show admins', 'show users', 'show providers']),
        items: [
          {
            title: t('roles'),
            href: RoleController.index().url,
            icon: 'lock-2',
            isActive: matchComponents('dashboard.roles.*'),
            show: hasPermission('show roles'),
          },
          {
            title: t('admins'),
            href: AdminController.index().url,
            icon: 'profile-circle',
            isActive: matchComponents('dashboard.admins.*'),
            show: hasPermission('show admins'),
          },
          {
            title: t('users'),
            href: UserController.index().url,
            icon: 'profile-user',
            isActive: matchComponents('dashboard.users.*'),
            show: hasPermission('show users'),
          },
          {
            title: t('providers'),
            href: ProviderController.index().url,
            icon: 'briefcase',
            isActive: matchComponents('dashboard.providers.*'),
            show: hasPermission('show providers'),
          },
        ],
      },

      // 4. Marketplace
      {
        title: t('marketplace'),
        show: hasAnyPermission(['show categories', 'show skills', 'show providerTypes']),
        items: [
          {
            title: t('categories'),
            href: CategoryController.index().url,
            icon: 'category',
            isActive: matchComponents('dashboard.categories.*'),
            show: hasPermission('show categories'),
          },
          {
            title: t('skills'),
            href: SkillController.index().url,
            icon: 'award',
            isActive: matchComponents('dashboard.skills.*'),
            show: hasPermission('show skills'),
          },
          {
            title: t('provider_types'),
            href: ProviderTypeController.index().url,
            icon: 'tag',
            isActive: matchComponents('dashboard.providerTypes.*'),
            show: hasPermission('show providerTypes'),
          },
        ],
      },

      // 5. Geo
      {
        title: t('geo'),
        show: hasAnyPermission(['show regions', 'show cities', 'show nationalities']),
        items: [
          {
            title: t('regions'),
            href: RegionController.index().url,
            icon: 'geolocation',
            isActive: matchComponents('dashboard.regions.*'),
            show: hasPermission('show regions'),
          },
          {
            title: t('cities'),
            href: CityController.index().url,
            icon: 'map',
            isActive: matchComponents('dashboard.cities.*'),
            show: hasPermission('show cities'),
          },
          {
            title: t('nationalities'),
            href: NationalityController.index().url,
            icon: 'flag',
            isActive: matchComponents('dashboard.nationalities.*'),
            show: hasPermission('show nationalities'),
          },
        ],
      },

      // 6. Catalog — Properties
      {
        title: t('catalog_properties'),
        show: hasAnyPermission(['show propertyCategories', 'show propertyTypes']),
        items: [
          {
            title: t('property_categories'),
            href: PropertyCategoryController.index().url,
            icon: 'category',
            isActive: matchComponents('dashboard.propertyCategories.*'),
            show: hasPermission('show propertyCategories'),
          },
          {
            title: t('property_types'),
            href: PropertyTypeController.index().url,
            icon: 'home-2',
            isActive: matchComponents('dashboard.property-types.*'),
            show: hasPermission('show propertyTypes'),
          },
        ],
      },

      // 7. Catalog — Cars
      {
        title: t('catalog_cars'),
        show: hasAnyPermission(['show carBrands', 'show carTypes', 'show carCategories']),
        items: [
          {
            title: t('car_categories'),
            href: CarCategoryController.index().url,
            icon: 'category',
            isActive: matchComponents('dashboard.car-categories.*'),
            show: hasPermission('show carCategories'),
          },
          {
            title: t('car_types'),
            href: CarTypeController.index().url,
            icon: 'car',
            isActive: matchComponents('dashboard.car-types.*'),
            show: hasPermission('show carTypes'),
          },
          {
            title: t('car_brands'),
            href: CarBrandController.index().url,
            icon: 'star',
            isActive: matchComponents('dashboard.car-brands.*'),
            show: hasPermission('show carBrands'),
          },
        ],
      },

      // 8. Catalog — Devices
      {
        title: t('catalog_devices'),
        show: hasAnyPermission(['show deviceCategories', 'show electronicBrands']),
        items: [
          {
            title: t('device_categories'),
            href: DeviceCategoryController.index().url,
            icon: 'devices',
            isActive: matchComponents('dashboard.device-categories.*'),
            show: hasPermission('show deviceCategories'),
          },
          {
            title: t('electronic_brands'),
            href: ElectronicBrandController.index().url,
            icon: 'star',
            isActive: matchComponents('dashboard.electronic-brands.*'),
            show: hasPermission('show electronicBrands'),
          },
        ],
      },

      // 9. Catalog — Institutes
      {
        title: t('catalog_institutes'),
        show: hasPermission('show specializations'),
        items: [
          {
            title: t('specializations'),
            href: SpecializationController.index().url,
            icon: 'book',
            isActive: matchComponents('dashboard.specializations.*'),
            show: hasPermission('show specializations'),
          },
        ],
      },

      // 10. Classifieds
      {
        title: t('classifieds'),
        show: hasAnyPermission([
          'show propertyAdvisements',
          'show carAdvisements',
          'show electronicAdvisements',
          'show instituteAdvisements',
        ]),
        items: [
          {
            title: t('property_advisements'),
            href: PropertyAdvisementController.index().url,
            icon: 'notepad-bookmark',
            isActive: matchComponents('dashboard.property-advisements.*'),
            show: hasPermission('show propertyAdvisements'),
          },
          {
            title: t('car_advisements'),
            href: CarAdvisementController.index().url,
            icon: 'notepad-bookmark',
            isActive: matchComponents('dashboard.car-advisements.*'),
            show: hasPermission('show carAdvisements'),
          },
          {
            title: t('electronic_advisements'),
            href: ElectronicAdvisementController.index().url,
            icon: 'devices',
            isActive: matchComponents('dashboard.electronic-advisements.*'),
            show: hasPermission('show electronicAdvisements'),
          },
          {
            title: t('institute_advisements'),
            href: InstituteAdvisementController.index().url,
            icon: 'building',
            isActive: matchComponents('dashboard.institute-advisements.*'),
            show: hasPermission('show instituteAdvisements'),
          },
        ],
      },

      // 11. Opportunities & Guarantor
      {
        title: t('opportunities_guarantor'),
        show: hasAnyPermission(['show opportunities', 'show guarantors']),
        items: [
          {
            title: t('opportunities'),
            href: OpportunityController.index().url,
            icon: 'briefcase',
            isActive: matchComponents('dashboard.opportunities.*'),
            show: hasPermission('show opportunities'),
          },
          {
            title: t('guarantor.module_title'),
            href: GuarantorDashboardController.index().url,
            icon: 'shield-tick',
            isActive: matchComponents('dashboard.guarantor.*'),
            show: hasPermission('show guarantors'),
          },
        ],
      },

      // 12. Finance
      {
        title: t('finance'),
        show: hasAnyPermission(['show topUpRequests', 'show withdrawRequests']),
        items: [
          {
            title: t('top_up_requests'),
            href: TopUpRequestController.index().url,
            icon: 'wallet',
            isActive: matchComponents('dashboard.top-up-requests.*'),
            show: hasPermission('show topUpRequests'),
          },
          {
            title: t('withdraw_requests'),
            href: WithdrawRequestController.index().url,
            icon: 'dollar',
            isActive: matchComponents('dashboard.withdraw-requests.*'),
            show: hasPermission('show withdrawRequests'),
          },
        ],
      },

      // 13. Content / CMS — section always shown (matches former SidebarMenuMain)
      {
        title: t('content_cms'),
        items: [
          {
            title: t('banners'),
            href: BannerController.index().url,
            icon: 'picture',
            isActive: matchComponents('dashboard.banners.*'),
            show: hasPermission('show banners'),
          },
          {
            title: t('pages'),
            href: PageController.index().url,
            icon: 'document',
            isActive: matchComponents('dashboard.pages.*'),
          },
          {
            title: t('questions'),
            href: QuestionController.index().url,
            icon: 'question-2',
            isActive: matchComponents('dashboard.questions.*'),
          },
          {
            title: t('messages'),
            href: MessageController.index().url,
            icon: 'sms',
            isActive: matchComponents('dashboard.messages.*'),
          },
        ],
      },

      // 14. Support — section always shown
      {
        title: t('support'),
        items: [
          {
            title: t('tickets'),
            href: SupportController.index().url,
            icon: 'message-question',
            isActive: matchComponents('dashboard.tickets.*'),
          },
        ],
      },

      // 15. Quality & System
      {
        title: t('quality_system'),
        show: hasAnyPermission(['show reviews', 'show settings', 'show panAnalytics']),
        items: [
          {
            title: t('reviews'),
            href: ReviewController.index().url,
            icon: 'star',
            isActive: matchComponents('dashboard.reviews.*'),
            show: hasPermission('show reviews'),
          },
          {
            title: t('settings'),
            href: SettingController.index().url,
            icon: 'setting-2',
            isActive: matchComponents('dashboard.settings.*'),
            show: hasPermission('show settings'),
          },
          {
            title: t('pan_analytics'),
            href: PanAnalyticsController.index().url,
            icon: 'chart-simple',
            isActive: matchComponents('dashboard.pan-analytics.*'),
            show: hasPermission('show panAnalytics'),
          },
        ],
      },
    ],
    [hasAnyPermission, hasPermission, matchComponents, matchUrl, t],
  );
}
