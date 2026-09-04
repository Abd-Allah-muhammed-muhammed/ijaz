import axios from "@/shared/helpers/axios";
import {useMutation, UseMutationResult} from "@tanstack/react-query";
import {SingleApiResponse} from "@/shared/types/api";
import {AxiosError} from "axios";
// Paused (not removed) — Provider top-up store route is commented out.
// After re-enabling Modules/Wallet/Routes/provider.php top-up-requests and
// running wayfinder:generate, restore TopUpController.store().url below.
// Task: chore/provider-topup-pause (2026-09-04).
// import TopUpController from "@/actions/Modules/Wallet/Http/Controllers/Provider/TopUpController";
import {walletDepositFormSchema} from "@/apps/provider/pages/Auth/Profile/wallet-forms-schems";


type AddBalanceData = walletDepositFormSchema

type AddBalanceResponse = SingleApiResponse<{
  status: string;
  transaction_id: string,
  driver: string,
  url: string
  payable: boolean
  data: Record<string, unknown>,
  message: string,
}>;
const addBalance = async (data: AddBalanceData): Promise<AddBalanceResponse> => {
  // const locale = usePage().props.app.locale;
  // Paused — was TopUpController.store().url
  const res = await axios.post<AddBalanceResponse>('/provider/dashboard/top-up-requests', data, {
    headers: {
      // 'Accept-Language': locale
      'Content-Type': 'multipart/form-data'
    }
  });
  return res.data;
}

export const useAddBalance = (): UseMutationResult<AddBalanceResponse, AxiosError, AddBalanceData> => {
  return useMutation({mutationFn: addBalance})
}
