import {ListViewProvider, useListView} from './core/ListViewProvider'
import {UsersListHeader} from './components/header/UsersListHeader'
import {UsersTable} from './table/UsersTable'
import {UserEditModal} from './user-edit-modal/UserEditModal'
import {KTCard} from '../../../../../_metronic/helpers'
import {ToolbarWrapper} from '../../../../../_metronic/layout/components/toolbar'
import {Content} from '../../../../../_metronic/layout/components/content'

const UsersList = () => {
  const {itemIdForUpdate} = useListView()
  return (
    <>
      <KTCard>
        <UsersListHeader/>
        <UsersTable/>
      </KTCard>
      {itemIdForUpdate !== undefined && <UserEditModal/>}
    </>
  )
}

const UsersListWrapper = () => (
  <ListViewProvider>
    <ToolbarWrapper/>
    <Content>
      <UsersList/>
    </Content>
  </ListViewProvider>
)

export {UsersListWrapper}
