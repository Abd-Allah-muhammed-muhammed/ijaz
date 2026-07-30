
export type FormInput = {
  name: string
  email: string;
  phone: string;
  password: string|null,
  password_confirmation: string|null,
  roles: number[];
  job: string;
  address: string;
  image: undefined | File;
};
