import { ConversationMessage } from '@/types/models';

type BaseResponse = {
  success: boolean;
  errors: Record<string, string[]>;
  message: string;
  token: string;
}

type SingleApiResponse<T> = BaseResponse & {
  data: T;
}

export type ApiResponse<T> = BaseResponse & {
  data: T[];
};

/**
 * card example
 * {
 *    card_scheme: "Visa"
 *    card_type: "Credit"
 *    expiryMonth: 12
 *    expiryYear: 2028
 *    payment_description: "4000 00## #### 0002"
 *    payment_method: "Visa"
 * }
 */
export type PaymentResponse = {
  id: number;
  amount: number;
  status: string;
  card: {
    card_scheme: string;
    card_type: string;
    expiryMonth: number,
    expiryYear: number,
    //  payment_description is card  masked card with last4 ,
    payment_description: string
    payment_method: string
  };
  currency: string;
  message: string | null;
};

export interface ConversationMessagePaginationResource {
  items: ConversationMessage[];
  paginate: {
    count: number,
    current_page: number,
    has_more_pages: boolean,
    last_page: number,
    next_page_url: null,
    per_page: number,
    prev_page_url: null,
    total: number,
  };
}
