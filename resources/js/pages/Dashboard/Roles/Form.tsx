import { useTranslation } from 'react-i18next';
import {Role} from "@/types/models";
import {Card, Col, Form as BTForm, FormCheck, FormControl, FormGroup, FormLabel, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import {FormInput, PermissionsGroup} from "./types";
import ActionButton from "@/components/action-button";
import InputError from "@/components/input-error";

type Props = {
  /**
   * The permissions to be displayed in the form
   */
  permissions: PermissionsGroup,
  /**
   * The role to be edited
   */
  role?: Role
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInput>) => void

};

export default function Form({permissions, role, callback}: Props) {
  const { t } = useTranslation();
  const form = useForm<FormInput>({
    name: role?.name || '',
    permissions: new Set(role?.permissions?.map((p) => p?.id as number) || []),
  });
  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form);
      }
    }}>
      <Row>
        <Col sm={12} className="mb-3">
          <FormGroup>
            <FormControl placeholder={t('name')} type='text' onChange={(e) => {
              form.setData('name', e.currentTarget.value);
            }}
                         defaultValue={form.data.name}
            />
            <InputError message={form.errors.name} className="mt-2"/>
          </FormGroup>
        </Col>
        {form.errors.permissions && (
          <Col sm={12} className="mb-3">
            <div className="alert alert-danger">
              {form.errors.permissions}
            </div>
          </Col>
        )}
        <Col sm={12} className="mb-3">
          <Row>
            {Object.entries(permissions).map(([group, xpermissions]) => (
              <Col sm={6} md={4} lg={3} className="mb-3" key={`group-${group}}`}>
                <Card>
                  <Card.Header>
                    <Card.Title as={'h5'} style={{
                      width: '100%',
                    }}>
                      <FormGroup as={'div'} className="d-flex justify-content-between align-content-center" style={{
                        width: '100%',
                      }}>
                        <FormLabel className="flex-grow-1" htmlFor={`group-${group}`}>
                          {t(group)}
                        </FormLabel>
                        <FormCheck
                          id={`group-${group}`}
                          className="form-check-solid"
                          type="switch"
                          checked={xpermissions.every(function (permission) {
                            return form.data.permissions.has(permission.id as number);
                          })}
                          onChange={(e) => {
                            if (e.currentTarget.checked) {
                              form.setData((previousData) => {
                                const newPermissions = new Set(previousData.permissions);
                                xpermissions.forEach((permission) => {
                                  newPermissions.add(permission.id as number);
                                })
                                return {
                                  ...previousData,
                                  permissions: newPermissions,
                                };
                              })
                            } else {
                              form.setData((previousData) => {
                                const newPermissions = new Set(previousData.permissions);
                                xpermissions.forEach((permission) => {
                                  newPermissions.delete(permission.id as number);
                                })
                                return {
                                  ...previousData,
                                  permissions: newPermissions,
                                };
                              })
                            }
                          }}
                        />
                      </FormGroup>
                    </Card.Title>
                  </Card.Header>
                  <Card.Body>
                    {xpermissions.map((permission) => (
                      <FormGroup key={`permission-${permission.id}`}
                                 className='d-flex justify-content-between align-items-center'>
                        <FormLabel htmlFor={`permission-${permission.id}`}>
                          {t(permission.name)}
                        </FormLabel>
                        <FormCheck
                          id={`permission-${permission.id}`}
                          className="form-check-solid mb-3"
                          type="switch"
                          checked={form.data.permissions.has(permission.id as number)}
                          onChange={(e) => {
                            if (e.currentTarget.checked) {
                              form.setData((previousData) => {
                                return {
                                  ...previousData,
                                  permissions: new Set(previousData.permissions).add(permission.id as number),
                                };
                              })
                            } else {
                              form.setData((previousData) => {
                                const newPermissions = new Set(previousData.permissions);
                                newPermissions.delete(permission.id as number);
                                return {
                                  ...previousData,
                                  permissions: newPermissions,
                                };
                              })
                            }
                          }}
                        />
                      </FormGroup>
                    ))}
                  </Card.Body>
                </Card>
              </Col>
            ))}
          </Row>
        </Col>
      </Row>
      <Row>
        <Col sm={12} className="mb-3 d-flex gap-3 justify-content-end">
          <ActionButton type={"submit"} isProcessing={form.processing} text={t('submit')}/>
          <Link href={RoleController.index().url} className="btn btn-light btn-lg">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
