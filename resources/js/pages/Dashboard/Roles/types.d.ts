import {Permission} from "@/types/models";

export type PermissionsGroup = {
  [key: string]: Permission[]
}

export type FormInput = {
  name: string
  permissions: Set<number>
};
