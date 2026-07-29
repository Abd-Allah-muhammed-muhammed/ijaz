export default function ButtonAction(
  {
    title,
    onClick
  }: {
    title: string,
    onClick: () => void,
  }
) {
  return (

      <a
        className='menu-link px-3'
        onClick={onClick}
      >
        {title}
      </a>
  )
}
