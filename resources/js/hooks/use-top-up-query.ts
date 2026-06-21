import axios from "@/helpers/axios";
import {useMutation, UseMutationResult} from "@tanstack/react-query";
import {SingleApiResponse} from "@/types/api";
import {AxiosError} from "axios";
import TopUpController from "@/actions/Modules/Wallet/Http/Controllers/Provider/TopUpController";
import {walletDepositFormSchema} from "@/pages/Provider/Auth/Profile/wallet-forms-schems";


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
  const res = await axios.post<AddBalanceResponse>(TopUpController.store().url, data, {
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


