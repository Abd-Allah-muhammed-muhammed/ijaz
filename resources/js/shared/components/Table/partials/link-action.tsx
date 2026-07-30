import {Link} from "@inertiajs/react";

export default function LinkAction(
  {
    title,
    href
  }: {
    title: string,
    href: string,
  }
) {
  return (

    <Link
      href={href}
      className='menu-link px-3'
    >
      {title}
    </Link>

  )
}
