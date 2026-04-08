import {Item} from './types'

type Props = {
  item: Item
  level: number
}
const Option = ({item, level}: Props) => {
  return (
    <>
      <option
        disabled={item.children?.length}
        className={`${item.children?.length ? 'fw-bold' : ''} `}
        value={item.id}
        dangerouslySetInnerHTML={{
          __html: `${'&nbsp;'.repeat((level - 1) * 3)}   ${item.name || item.title || ''}`
        }}
      >
      </option>
      {item.children?.length ? (
        item.children.map((child) => (
          <Option key={`RecursiveSelect-${Math.random()}-${child.id}`} item={child} level={level + 1}/>
        ))
      ) : null}
    </>
  );
}


export default Option
