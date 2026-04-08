import {useEffect, useState} from "react"

export type RequestDate<T extends Record<string, unknown>> = {
  prams: T,
  callback: (data: T) => void,
}

type Prop<T extends Record<string, unknown>> = {
  requestData: RequestDate<T>,
  loaded ?: boolean,
}
const useRequestData = <T extends Record<string, unknown>>({requestData,loaded=false}: Prop<T>) => {
  const [data, setData] = useState<T>(requestData.prams)

  useEffect(() => {
    console.log(loaded)
    if (loaded == false) {
      loaded = true
      return;
    }
    requestData.callback(data)


  }, [data]);
  return {
    setPrams: setData,
    prams: data,
  }
}


export default useRequestData
