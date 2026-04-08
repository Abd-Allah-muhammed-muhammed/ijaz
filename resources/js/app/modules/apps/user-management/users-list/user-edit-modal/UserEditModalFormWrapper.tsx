import {UserEditModalForm} from './UserEditModalForm'
import {useListView} from '../core/ListViewProvider'

const UserEditModalFormWrapper = () => {
  const {itemIdForUpdate} = useListView()
  const {data: user, error, isLoading} = {
    data: [],
    error: {},
    isLoading: true,
  }

  if (!itemIdForUpdate) {
    return <UserEditModalForm isUserLoading={isLoading} user={{id: undefined}}/>
  }

  if (!isLoading && !error && user) {
    return <UserEditModalForm isUserLoading={isLoading} user={{}}/>
  }

  return null
}

export {UserEditModalFormWrapper}
