import { CategoryFeesTypeEnum, OperationStatusEnum, ProviderTypeFilesEnum } from "@/Enums/Enums";
import { ProviderStatusEnum } from "@/Enums/Providers";
import { OfferStatusEnum, OrderStatusEnum } from "@/Enums/Order";
import { PaymentMethodEnum, PaymentStatusEnum } from "@/Enums/Payment";
import { UserStatusEnum } from '@/Enums/Users';
import { TicketSupportStatusEnum } from "@/Enums/SupportTickets";
import { GuaranteeRequestStatusEnum } from "@/Enums/GuaranteeRequest";
import { AdvisementStatusEnum } from "@/Enums/Advisements";

export interface Model {
  id: number | string;

  [key: string]: unknown
}


export interface Enum<T> {
  value: T;
  label: string;
}

export interface EnumWithColors<T> extends Enum<T> {
  color: string;
}

export interface User extends Model {
  f_name: string;
  l_name: string;
  name: string;
  email: string;
  image: string;
  email_verified_at: Date | null;
  wallet?: Wallet;
  language: string;
  latitude: string;
  longitude: string;
  phone: string;
  nationality_id: number;
  nationality?: Nationality;
  rating?: number;
  orders_count?: number;
  status: EnumWithColors<UserStatus>;
  blocked_at: Date;
  blocked_until: Date;
  latest_block_history: BlockHistory;
  created_at: Date;
}

export interface Admin extends Model, UserWithAvatar {
  email_verified_at: Date | null;
  root: boolean;
  roles: Role[];
  job: string;
  address: string;
  phone: string;
}

export interface UserWithRoles extends User {
  roles: Role[];
}

export interface UserWithPermissions extends User {
  permissions: Permission[];
}

export interface UserWithRolesAndPermissions extends UserWithRoles, UserWithPermissions {
}

export interface UserWithAvatar {
  name: string;
  email: string;
  image: string;
}

export interface AuthenticatedUser extends UserWithAvatar, Model {
  permissions: string[];
  roles: string[];
  user_type: string;
  socket_id: string;
  root?: boolean;
}

export interface Role extends Model {
  name: string;
  permissions?: Permission[];
  users_count?: number;
}

export interface Permission extends Model {
  name: string;
  group: string;
  roles?: Role[];
  roles_count?: number;
}

export interface Category extends Model {
  title: string;
  icon: string;
  parent_id: number | null;
  parent?: Category;
  children?: Category[];
  translations?: Record<string, CategoryTranslation>;
  translation?: CategoryTranslation;
  children_count?: number;
  has_children?: boolean;
  skills?: Skill[];
  provider_skills?: Skill[];
  fees?: number;
  fees_type: Enum<CategoryFeesType>;
}

export type CategoryFeesType = typeof CategoryFeesTypeEnum[keyof typeof CategoryFeesTypeEnum];

export interface CategoryTranslation extends Model {
  title: string;
  description: string;
  locale: string;
  category_id: number;
  category?: Category;
}

export interface PropertyCategory extends Model {
  title: string;
  is_active: boolean;
  parent_id: number | null;
  parent?: PropertyCategory;
  children?: PropertyCategory[];
  translations?: Record<string, PropertyCategoryTranslation>;
  translation?: PropertyCategoryTranslation;
  children_count?: number;
  has_children?: boolean;
}

export interface PropertyCategoryTranslation extends Model {
  title: string;
  locale: string;
  property_category_id: number;
  propertyCategory?: PropertyCategory;
}

export interface PropertyType extends Model {
  name: string;
  is_active: boolean;
  translations?: Record<string, PropertyTypeTranslation>;
  translation?: PropertyTypeTranslation;
}

export interface DeviceCategory extends Model {
  title: string;
  icon: string | null;
  parent_id: number | null;
  parent?: DeviceCategory;
  children?: DeviceCategory[];
  translations?: Record<string, DeviceCategoryTranslation>;
  translation?: DeviceCategoryTranslation;
  children_count?: number;
  has_children?: boolean;
}

export interface DeviceCategoryTranslation extends Model {
  title: string;
  locale: string;
  device_category_id: number;
  deviceCategory?: DeviceCategory;
}

export interface PropertyTypeTranslation {
  id: number;
  property_type_id: number;
  locale: string;
  name: string;
}

export interface CarBrand extends Model {
  is_active: boolean;
  image: string;
  image_url: string;
  name: string;
  translations: CarBrandTranslation[];
}

export interface CarBrandTranslation extends Model {
  car_brand_id: number;
  locale: string;
  name: string;
}

export interface CarType extends Model {
  is_active: boolean;
  image: string;
  image_url: string;
  car_brand_id: number;
  brand?: CarBrand;
  name: string;
  translations: CarTypeTranslation[];
}

export interface CarTypeTranslation extends Model {
  car_type_id: number;
  locale: string;
  name: string;
}

export interface Skill extends Model {
  title: string;
  category?: Category;
  translations?: Record<string, SkillTranslation>;
  translation?: SkillTranslation;
}

export interface SkillTranslation extends Model {
  title: string;
  locale: string;
  skill_id: number;
  skill?: Skill;
}

export interface Region extends Model {
  title: string;
  translations?: Record<string, RegionTranslation>;
  translation?: RegionTranslation;
  cities_count?: number;
}

export interface RegionTranslation extends Model {
  title: string;
  locale: string;
  region_id: number;
  region?: Region;
}

export interface City extends Model {
  title: string;
  translations?: Record<string, CityTranslation>;
  translation?: CityTranslation;
  region_id: number;
  region?: Region;
}

export interface CityTranslation extends Model {
  title: string;
  locale: string;
  city_id: number;
  city?: City;
}


export interface Nationality extends Model {
  id: number;
  name: string;
  translations?: Record<string, NationalityTranslation>;
  translation?: NationalityTranslation;
  region?: Region;
}

export interface NationalityTranslation extends Model {
  name: string;
  locale: string;
  nationality_id: number;
  nationality?: Nationality;
}


export type ProviderTypeFileKeys = typeof ProviderTypeFilesEnum[keyof typeof ProviderTypeFilesEnum];

export interface ProviderType extends Model {
  name: string;
  description: string;
  translations?: Record<string, ProviderTypeTranslation>;
  translation?: ProviderTypeTranslation;
  providers_count?: number;
  files: Record<ProviderTypeFileKeys, boolean>,
  image: string;
  categories: Category[];
}

export interface ProviderTypeTranslation extends Model {
  name: string;
  description?: string;
  locale: string;
  provider_type_id: number;
  providerType?: ProviderType;
}

export type ProviderStatus = typeof ProviderStatusEnum[keyof typeof ProviderStatusEnum];
export type UserStatus = typeof UserStatusEnum[keyof typeof UserStatusEnum];

export type SupportTicketStatus = typeof TicketSupportStatusEnum[keyof typeof TicketSupportStatusEnum];

export interface Provider extends Model {
  name: string;
  email: string;
  phone: string;
  iban: string;
  address: string;
  logo: string;
  status: EnumWithColors<ProviderStatus>;
  commercial_record: string;
  about: string;
  provider_type_id: number;
  region_id: number;
  city_id: number;
  owner_id: number;
  provider_type?: ProviderType;
  skills?: Skill[];
  region?: Region;
  city?: City;
  wallet?: Wallet;
  WalletTransaction?: WalletTransaction[];
  permissions?: Permission[];
  owner?: Employee;
  categories?: Category[];
  average_rating?: number;
  reviews_count?: number;
  orders_count?: number;
  top_up_requests_count?: number;
  media?: Media[];
  blocked_at: Date;
  blocked_until: Date;
  latest_block_history: BlockHistory;
  created_at: Date;
  socket_id: string;
}

export interface BlockHistory extends Model {
  blocked_at: Date;
  blocked_until?: Date;
  reason?: string;
}

export interface Employee extends Model {
  name: string;
  email: string;
  phone: string;
  address: string;
  id_image: string;
  profile_picture: string;
  provider_id: number;
  provider?: Provider;
  permissions?: Permission[];
}

export interface Wallet extends Model {
  user_id: number;
  user_type: string;
  user?: User;
  credit: number;
  pending_credit: number;
  debit: number;
  pending_debit: number;
  total_earning: number;
  total_spent: number;
  balance: number;
  transactions?: WalletTransaction[];
}

export interface WalletTransaction extends Model {
  user_id: number;
  user_type: string;
  credit: number;
  debit: number;
  description: string;
  operation_id: number;
  operation_type: string;
  operation?: Model;
  wallet?: Wallet;
  wallet_id: number;
  created_at: Date;
}

export interface ConversationUser extends Model {
  name: string;
  image: string;
  socket_id: string;
  online: boolean;
}

export interface ConversationMessage extends Model {
  id: string;
  content: string;
  sender_id: number;
  sender_type: string;
  sender?: ConversationUser;
  conversation_id: string;
  conversation?: Conversation;
  created_at: Date;
  read_at: Date;
  attachments?: ConversationAttachment[];
  has_attachments: boolean;
}

export interface ConversationAttachment extends Model {
  id: string;
  type: string;
  filename: string;
  url: string;
}

export interface Conversation extends Model {
  id: string;
  user1: ConversationUser;
  user2: ConversationUser;
  messages: ConversationMessage[];
  last_message?: ConversationMessage;
  unread_count?: number;
  created_at: Date;
  last_massage_at: Date;
}

export type OrderStatus = typeof OrderStatusEnum[keyof typeof OrderStatusEnum];

export interface Order extends Model {
  title: string;
  description: string;
  status: EnumWithColors<OrderStatus>;
  budget_start: number;
  budget_end: number;
  expected_time: string;
  provider_id: number;
  provider?: Provider;
  category_id: number;
  category?: Category;
  region_id: number;
  region?: Region;
  city_id: number;
  city: City;
  accepted_offer_id: number;
  price: number;
  accepted_offer?: OrderOffer;
  offers?: OrderOffer[];
  created_at: Date;
  media_count?: number;
  offers_count?: number;
  media?: Media[];
  user_id: number;
  user?: User;
  skills?: Skill[];
  reviews: Review[];
}

export interface Media extends Model {
  name: string;
  collection_name: string;
  file_name: string;
  mime_type: string; // e.g., 'image', 'video', 'document'
  type: string; // e.g., 'image', 'video', 'document'
  size: string; // in bytes
  created_at: Date;
  url: string; // URL to access the media file
  extension: string; // e.g., 'jpg', 'png', 'mp4', 'pdf'
}

export type OrderOfferStatus = typeof OfferStatusEnum[keyof typeof OfferStatusEnum];

export interface OrderOffer extends Model {
  order_id: string;
  order?: Order;
  provider_id: number;
  provider?: Provider;
  price: number;
  description: string;
  status: EnumWithColors<OrderOfferStatus>;
  created_at: Date;
  category_id: number,
  category?: Category,
  user_id: number,
  user?: User,
}

export interface Banner extends Model {
  image: string;
  link: string | null;

}

export interface Review extends Model {
  comment: string;
  rating: number;
  reviewer_type: string;
  reviewer: Reviewer;
  reviewee_type: string;
  reviewee: Reviewer;
}

export interface Reviewer {
  name: string;
}

export type TopUpRequestStatus = typeof OperationStatusEnum[keyof typeof OperationStatusEnum];
export type PaymentStatus = typeof PaymentStatusEnum[keyof typeof PaymentStatusEnum];
export type PaymentMethod = typeof PaymentMethodEnum[keyof typeof PaymentMethodEnum];
export type GuaranteeRequestStatus = typeof GuaranteeRequestStatusEnum[keyof typeof GuaranteeRequestStatusEnum];

export interface TopUpRequest extends Model {
  id: string;
  user_id: number;
  user_type: string;
  user?: User | Provider;
  amount: number;
  status: EnumWithColors<TopUpRequestStatus>;
  payment_status: EnumWithColors<PaymentStatus> | null;
  payment_method: Enum<PaymentMethod>;
  transaction_image: string | null;
  created_at: Date;
}

export interface WithdrawRequest extends Model {
  id: string;
  user_id: number;
  user_type: string;
  user?: User | Provider;
  amount: number;
  status: EnumWithColors<TopUpRequestStatus>;
  created_at: Date;
}

export interface Page extends Model {
  title: string;
  slug: string;
  content: string;
  translations?: Record<string, PageTranslation>;
  translation?: PageTranslation;
}

export interface PageTranslation extends Model {
  title: string;
  content: string;
  locale: string;
  page_id: number;
  page?: Page;
}

export interface Question extends Model {
  title: string;
  answer: string;
  translations?: Record<string, QuestionTranslation>;
  translation?: QuestionTranslation;
}

export interface QuestionTranslation extends Model {
  title: string;
  answer: string;
  locale: string;
  question_id: number;
  question?: Question;
}

export interface Message extends Model {
  name: string;
  phone: string;
  title: string;
  content: string;
  created_at: Date;
}

export interface OperationUser extends Model {
  type: string;
  socket_id: string;
  name: string;
  email: string;
  image: string;
  created_at: Date;
}

export interface TicketSupport<T extends Model> extends Model {
  title: string,
  message: string,
  status: EnumWithColors<SupportTicketStatus>,
  user: OperationUser
  operation?: Operation<T>
  created_at: string,
}

export interface Operation<T extends Model> {
  id: string,
  type: string
  show_url: string
  data: T
}

export interface GuaranteeRequest extends Model {
  id: string,
  title: string,
  user: User,
  provider: User,
  description: string,
  amount: number,
  fees: number,
  total: number,
  status: EnumWithColors<GuaranteeRequestStatus>,
  media?: Media[],
  created_at: Date,
}

export type AdvisementStatus = typeof AdvisementStatusEnum[keyof typeof AdvisementStatusEnum];
export type OperationEnum = typeof OperationEnum[keyof typeof OperationEnum];
export interface PropertyAdvisement extends Model {
  title: string;
  description: string;
  image: string;
  status: EnumWithColors<AdvisementStatus>;
  operation: EnumWithColors<OperationEnum>;
  facade: string;
  street_width: string;
  street_type: string;
  user_id: number;
  age: number;
  area: number;
  price: number;
  bedrooms_count: number;
  bathrooms_count: number;
  halls_count: number;
  phone: string;
  license?: string;
  options: string;
  latitude: string;
  longitude: string;
  address: string;
  property_type_id: number;
  city_id: number;
  region_id: number;
  category_id: number;
  show_price: boolean;
  property_type?: PropertyType;
  city?: City;
  region?: Region;
  category?: PropertyCategory;
  media?: Media[];
  user?: User;
  created_at: Date;
  updated_at: Date;
}
export interface CarAdvisement extends Model {
  title: string;
  description: string;
  image: string;
  status: EnumWithColors<AdvisementStatus>;
  operation: EnumWithColors<OperationEnum>;
  usage_status: EnumWithColors<UsageStatusEnum>;
  user_id: number;
  car_brand_id: number;
  car_type_id: number;
  car_category_id: number;
  year: number;
  mileage: number;
  transmission: string;
  fuel_type: string;
  engine_size?: string;
  color: string;
  price: number;
  phone: string;
  options: string;
  latitude: string;
  longitude: string;
  address: string;
  city_id: number;
  region_id: number;
  show_price: boolean;
  car_brand?: CarBrand;
  car_type?: CarType;
  car_category?: CarCategory;
  city?: City;
  region?: Region;
  media?: Media[];
  user?: User;
  created_at: Date;
  updated_at: Date;
}
