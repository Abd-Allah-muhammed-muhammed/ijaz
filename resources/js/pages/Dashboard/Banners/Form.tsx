import { useTranslation } from 'react-i18next';
import {Banner} from "@/types/models";
import {Col, Form as BTForm, FormControl, FormGroup, FormLabel, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {FormInput} from "./types";
import ActionButton from "@/components/action-button";
import BannerController from "@/actions/App/Http/Controllers/Dashboard/BannerController";
import ImageInput from "@/components/inputs/ImageInput";
import InputError from "@/components/inputs/InputError";

type Props = {
  /**
   * The role to be edited
   */
  row?: Banner
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInput>) => void

};

export default function Form({callback, row}: Props) {
  const { t } = useTranslation();


  const form = useForm<FormInput>({
    image: undefined,
    link: row?.link

  });
  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form);
      }
    }}>
      <Row>
        <Col sm={12} md={2}>
          <ImageInput
            url={row?.image}
            callback={(data) => {
              form.setData('image', data.currentTarget.files![0]);
            }}/>
          <InputError message={form.errors.image}/>
        </Col>
        <Col sm={12} md={10} className="mb-3">
          <Row>
            <Col sm={12} md={6} className="mb-3">
              <FormGroup>
                <FormLabel aria-required={true} className="required">
                  {t('link')}
                </FormLabel>
                <FormControl
                  placeholder={t('link')}
                  type='url'
                  onChange={(e) => {
                    form.setData('link', e.currentTarget.value);
                  }}
                  defaultValue={form.data.link || ''}
                />
                <InputError message={form.errors.link}/>
              </FormGroup>
            </Col>
          </Row>
          <br/>

        </Col>
      </Row>
      <Row>
        <Col sm={12} className="mb-3 d-flex gap-3 justify-content-end">
          <ActionButton
            type="submit"
            isProcessing={form.processing}
            text={t('save')}
          />
          <Link href={BannerController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
