import {AuthLayout} from "@/layouts/provider/AuthLayout";
import {Head} from "@inertiajs/react";
import React, {ReactElement} from "react";
import Form from "@/pages/Dashboard/Providers/Form";
import {CategoryFormData} from "@/pages/Dashboard/Providers/types";
import AuthController from "@/actions/App/Http/Controllers/Frontend/AuthController";
import {City, ProviderType, Region} from "@/types/models";
import {KTIcon} from "@/_metronic/helpers";

type Props = {
  types: ProviderType[]
  regions: Region[],
  cities: City[]
};
const Register = ({types, regions, cities}: Props) => {
  return (
    <>
      <Head title={'Register'}/>
      {/*begin::Header*/}
      <div className="d-flex flex-stack py-2">
        {/* Back link */}
        <div className="ms-2">
          <a href="authentication/layouts/fancy/sign-in.html" className="btn btn-icon bg-light rounded-circle">
            <KTIcon iconName='black-left fs-2 text-gray-800' iconType='duotone'/>
          </a>

        </div>
        {/* Sign Up link */}
        <div className="m-0">
          <span className="text-gray-500 fw-bold fs-5 ms-2"
                data-kt-translate="sign-up-head-desc">Already a member ?</span>
          <a href="authentication/layouts/fancy/sign-in.html" className="link-primary fw-bold fs-5"
             data-kt-translate="sign-up-head-link">Sign In</a>
        </div>
      </div>
      {/*end::Header*/}
      {/* Body */}
      <div className="py-20">

        <Form
          cols={1}
          types={types}
          cities={cities}
          regions={regions}
          images={{
            logo: '',
            commercial_record: '',
          }}
          callback={(form) => {
            form.transform(data => {
              return {
                ...data,
                categories: data.categories.map((category: CategoryFormData) => {
                  return {
                    category: category.category.id,
                    skills: category.skills.map(skill => skill.id)
                  }
                }),
              };
            })
            form.submit(AuthController.store(), {
              onSuccess: () => {
                form.reset()
              },
            });
          }}
        />
      </div>
      {/*end::Body*/}
    </>
  )
};

Register.layout = (page: ReactElement) => {
  return (
    <AuthLayout>
      {page}
    </AuthLayout>
  )
};

export default Register;
