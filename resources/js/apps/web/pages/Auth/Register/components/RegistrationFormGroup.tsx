import type { ReactNode } from 'react';
import { Col, Form } from 'react-bootstrap';
import InputError from '@/shared/components/inputs/InputError';

type ColSize = number | { span?: number; offset?: number };

export type RegistrationFormGroupProps = {
  label: string;
  required?: boolean;
  error?: string;
  htmlFor?: string;
  children: ReactNode;
  /** Bootstrap Col `sm` breakpoint. Defaults to 12. */
  sm?: ColSize;
  /** Bootstrap Col `md` breakpoint. */
  md?: ColSize;
  groupClassName?: string;
};

const DEFAULT_GROUP_CLASS = 'mb-10 fv-row';
const REQUIRED_LABEL_CLASS = 'required form-label mb-3';
const OPTIONAL_LABEL_CLASS = 'form-label mb-3';

/**
 * Standard registration field layout: Col → Form.Group → Label → control → InputError.
 */
export default function RegistrationFormGroup({
  label,
  required = false,
  error,
  htmlFor,
  children,
  sm = 12,
  md,
  groupClassName = DEFAULT_GROUP_CLASS,
}: RegistrationFormGroupProps) {
  return (
    <Col sm={sm} md={md}>
      <Form.Group className={groupClassName}>
        <Form.Label
          className={required ? REQUIRED_LABEL_CLASS : OPTIONAL_LABEL_CLASS}
          htmlFor={htmlFor}
        >
          {label}
        </Form.Label>
        {children}
        <InputError message={error} />
      </Form.Group>
    </Col>
  );
}
