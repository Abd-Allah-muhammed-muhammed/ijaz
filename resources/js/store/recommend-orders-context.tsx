import {createContext, Dispatch, ReactElement, SetStateAction, useContext, useState} from "react";
import {Order} from "@/types/models";

export type RecommendedOrdersContextType = {
  orders: Order[];
  setOrders: Dispatch<SetStateAction<Order[]>>
};

const RecommendedOrdersContext = createContext<RecommendedOrdersContextType | null>(null);

export const RecommendedOrdersProvider = ({children}: { children: ReactElement }) => {
  const [orders, setOrders] = useState<Order[]>([]);


  return (
    <RecommendedOrdersContext.Provider value={{
      orders,
      setOrders
    }}>
      {children}
    </RecommendedOrdersContext.Provider>

  )
}

export const useRecommendedOrdersContext = (): RecommendedOrdersContextType => {
  const context = useContext(RecommendedOrdersContext);
  if (!context) {
    throw new Error("useRecommendedOrdersContext must be used within a RecommendedOrdersProvider");
  }
  return context;
}
