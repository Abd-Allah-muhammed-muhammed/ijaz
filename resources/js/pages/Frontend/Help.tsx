import {Head} from '@inertiajs/react'
import React, {ReactNode} from "react";
import FrontendLayout from "@/layouts/FrontendLayout";
import { useTranslation } from 'react-i18next';

const Help = ({}) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('Help')}/>

    </>
  )
}
Help.layout = (page: ReactNode) => <FrontendLayout children={page}/>;

export default Help;
