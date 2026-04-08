import { useTranslation } from 'react-i18next';
import { User, Nationality } from '@/types/models';
import { Card, Col, Form as BTForm, FormControl, FormGroup, FormLabel, FormSelect, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {FormInput} from "./types";
import ImageInput from "@/components/inputs/ImageInput";
import ActionButton from "@/components/action-button";
import {KTCard} from "@/_metronic/helpers";
import React from "react";
import InputError from "@/components/input-error";

const calcColSize = (cols: number, factor: number = 1) => {
  const result = (12 / cols) * factor
  if (result < 1) {
    return 1;
  }
  if (result > 12) {
    return 12;
  }
  return result;
}

type Props = {
  /**
   * The role to be edited
   */
  nationalities: Nationality[]
  row?: User
  backUrl?: string
  image: string
  cols?: number
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInput>) => void

};

export default function Form({callback, nationalities, row, backUrl, image, cols = 4}: Props) {
  const { t } = useTranslation();
  const form = useForm<FormInput>({
    f_name: row?.f_name || '',
    l_name: row?.l_name || '',
    image: undefined,
    email: row?.email || '',
    language: row?.language || '',
    latitude: row?.latitude || '',
    longitude: row?.longitude || '',
    password: '',
    password_confirmation: '',
    phone: row?.phone || '',
    nationality_id: row?.nationality_id || null,
  });

  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form);
      }
    }}>
      <KTCard className='mb-5'>
        <Card.Header>
          <Card.Title>
            {t('general.information')}
          </Card.Title>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col className="mb-3">
              <Row>
                <Col sm={12} md={calcColSize(cols)} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('f_name')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('f_name')}
                      type='text'
                      onChange={(e) => {
                        form.setData('f_name', e.currentTarget.value);
                      }}
                      defaultValue={form.data.f_name}
                    />
                    <InputError message={form.errors.f_name}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={calcColSize(cols)} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('l_name')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('l_name')}
                      type='text'
                      onChange={(e) => {
                        form.setData('l_name', e.currentTarget.value);
                      }}
                      defaultValue={form.data.l_name}
                    />
                    <InputError message={form.errors.l_name}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={calcColSize(cols)} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('email')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('email')}
                      type='email'
                      onChange={(e) => {
                        form.setData('email', e.currentTarget.value);
                      }}
                      defaultValue={form.data.email}
                    />
                    <InputError message={form.errors.email}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={calcColSize(cols)} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('phone')}
                    </FormLabel>
                    <FormControl
                      placeholder={t('phone')}
                      className="form-control-solid"
                      type="tel"
                      onChange={(e) => {
                        form.setData('phone', e.currentTarget.value);
                      }}
                      defaultValue={form.data.phone}
                    />
                    <InputError message={form.errors.phone}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={calcColSize(cols)} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('nationality')}
                    </FormLabel>
                    <FormSelect
                      className='form-select-solid'
                      defaultValue={form.data.nationality_id as unknown as string}
                      onChange={(e) => {
                        const val = parseInt(e.currentTarget.value);
                        form.setData('nationality_id', val || null);
                      }}>
                      <option>{t('choose')}</option>
                      {nationalities.map((r) => (
                        <option key={`nationality-${r.id}`} value={r.id}>
                          {r.name}
                        </option>
                      ))}
                    </FormSelect>
                    <InputError message={form.errors.nationality_id}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={calcColSize(cols)} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('password')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('password')}
                      type='password'
                      onChange={(e) => {
                        form.setData('password', e.currentTarget.value);
                      }}
                      defaultValue={form.data.password}
                    />
                    <InputError message={form.errors.password}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={calcColSize(cols)} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('password_confirmation')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('password_confirmation')}
                      type='password'
                      onChange={(e) => {
                        form.setData('password_confirmation', e.currentTarget.value);
                      }}
                      defaultValue={form.data.password_confirmation}
                    />
                    <InputError message={form.errors.password_confirmation}/>
                  </FormGroup>
                </Col>
                <Col sm={calcColSize(cols)}>
                  <FormGroup className='d-flex flex-column'>
                    <FormLabel className="required">
                      {t('image')}
                    </FormLabel>
                    <ImageInput
                      url={image}
                      height={100}
                      width={100}
                      callback={(data) => {
                        form.setData('image', data.currentTarget.files![0]);
                      }}
                    />
                    <InputError message={form.errors.image}/>
                  </FormGroup>

                </Col>
              </Row>
            </Col>
          </Row>
        </Card.Body>
      </KTCard>
      <Row>
        <Col sm={12} className="mb-3 d-flex gap-3 justify-content-end">
          {backUrl && (
            <Link href={backUrl} className="btn btn-light">
              {t('cancel')}
            </Link>
          )}

          <ActionButton
            type="submit"
            isProcessing={form.processing}
            text={t('save')}
          />

        </Col>
      </Row>
    </BTForm>
  );
}
