import axios, { AxiosResponse } from "axios";
import { ID, Response } from "@/_metronic/helpers";
import { User, UsersQueryResponse } from "./_models";

const API_URL = import.meta.env.VITE_APP_THEME_API_URL;
const USER_URL = `${API_URL}/user`;
const GET_USERS_URL = `${API_URL}/users/query`;

const getUsers = async (query: string): Promise<UsersQueryResponse> => {
  const d = await axios
    .get(`${GET_USERS_URL}?${query}`);
  return d.data;
};

const getUserById = async (id: ID): Promise<User | undefined> => {
  const response = await axios
    .get(`${USER_URL}/${id}`);
  const response_1 = response.data;
  return response_1.data;
};

const createUser = async (user: User): Promise<User | undefined> => {
  const response = await axios
    .put(USER_URL, user);
  const response_1 = response.data;
  return response_1.data;
};

const updateUser = async (user: User): Promise<User | undefined> => {
  const response = await axios
    .post(`${USER_URL}/${user.id}`, user);
  const response_1 = response.data;
  return response_1.data;
};

const deleteUser = async (userId: ID): Promise<void> => {
  await axios.delete(`${USER_URL}/${userId}`);
};

const deleteSelectedUsers = async (userIds: Array<ID>): Promise<void> => {
  const requests = userIds.map((id) => axios.delete(`${USER_URL}/${id}`));
  await axios.all(requests);
};

export {
  getUsers,
  deleteUser,
  deleteSelectedUsers,
  getUserById,
  createUser,
  updateUser,
};
