import { useTranslation } from 'react-i18next';
import {Admin, Role} from "@/types/models";
import {Col, Form as BTForm, FormControl, FormGroup, FormSelect, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {FormInput} from "./types";
import InputError from "@/components/inputs/InputError";
import ImageInput from "@/components/inputs/ImageInput";
import AdminController from "@/actions/App/Http/Controllers/Dashboard/AdminController";
import ActionButton from "@/components/action-button";

type Props = {
  /**
   * The role to be edited
   */
  admin?: Admin
  roles: Role[]
  image: string
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInput>) => void

};

export default function Form({callback, admin, roles, image}: Props) {
  const { t } = useTranslation();
  const form = useForm<FormInput>({
    name: admin?.name || '',
    email: admin?.email || '',
    password: null,
    password_confirmation: null,
    address: admin?.address || '',
    phone: admin?.phone || '',
    job: admin?.job || '',
    image: undefined,
    roles: admin?.roles?.length ? admin.roles.map((role) => role.id as number) : (roles.length > 0 ? [roles[0].id as number] : []),
  });
  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form);
      }
    }}>
      <Row>
        <Col sm={12} md={3} className="mb-3">
          <ImageInput
            url={image}
            callback={(data) => {
              form.setData('image', data.currentTarget.files![0]);
            }}/>
          <InputError message={form.errors.image}/>
        </Col>
        <Col className="mb-3">
          <Row>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormControl
                  placeholder={t('name')}
                  type='text'
                  onChange={(e) => {
                    form.setData('name', e.currentTarget.value);
                  }}
                  defaultValue={form.data.name}
                />
                <InputError message={form.errors.name}/>
              </FormGroup>
            </Col>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormControl
                  placeholder={t('email.prop')}
                  type='email'
                  onChange={(e) => {
                    form.setData('email', e.currentTarget.value);
                  }}
                  defaultValue={form.data.email}
                />
                <InputError message={form.errors.email}/>
              </FormGroup>
            </Col>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormControl
                  placeholder={t('phone')}
                  type="tel"
                  onChange={(e) => {
                    form.setData('phone', e.currentTarget.value);
                  }}
                  defaultValue={form.data.phone}
                />
                <InputError message={form.errors.phone}/>
              </FormGroup>
            </Col>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormControl
                  placeholder={t('job')}
                  type='text'
                  onChange={(e) => {
                    form.setData('job', e.currentTarget.value);
                  }}
                  defaultValue={form.data.job}
                />
                <InputError message={form.errors.job}/>
              </FormGroup>
            </Col>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormControl
                  placeholder={t('address')}
                  type='text'
                  onChange={(e) => {
                    form.setData('address', e.currentTarget.value);
                  }}
                  defaultValue={form.data.address}
                />
                <InputError message={form.errors.address}/>
              </FormGroup>
            </Col>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormControl
                  placeholder={t('password.prop')}
                  type='password'
                  onChange={(e) => {
                    form.setData('password', e.currentTarget.value);
                  }}
                />
                <InputError message={form.errors.password}/>
              </FormGroup>
            </Col>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormControl
                  placeholder={t('password_confirmation')}
                  type='password'
                  onChange={(e) => {
                    form.setData('password_confirmation', e.currentTarget.value);
                  }}
                />
                <InputError message={form.errors.password_confirmation}/>
              </FormGroup>
            </Col>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup>
                <FormSelect
                  defaultValue={form.data.roles.length > 0 ? form.data.roles[0] : ''}
                  onChange={(e) => {
                    form.setData('roles', [e.currentTarget.value as unknown as number])
                  }}
                >
                  {roles.map((role) => (
                    <option key={role.id} value={role.id}>
                      {role.name}
                    </option>
                  ))}
                </FormSelect>
                <InputError message={form.errors.roles}/>
              </FormGroup>
            </Col>
          </Row>
        </Col>
      </Row>
      <Row>
        <Col sm={12} className="mb-3 d-flex gap-3 justify-content-end">
          <ActionButton
            type="submit"
            isProcessing={form.processing}
            text={t('save')}
          />
          <Link href={AdminController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
