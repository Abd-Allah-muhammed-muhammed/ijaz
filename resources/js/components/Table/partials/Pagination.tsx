import {PaginationLink, PaginationMeta} from "@/types";
import clsx from "clsx";
import {Link} from "@inertiajs/react";

type Props = {
  paginationMeta: PaginationMeta;
  preserveScroll?: boolean;
  only?: string[];
}


export default function Pagination(
  {
    paginationMeta,
    preserveScroll = true,
    only
  }: Props
) {
  return (
    <div className='row'>
      <div
        className='col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'></div>
      <div className='col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'>
        <div id='kt_table_users_paginate'>
          <ul className="pagination">
            {paginationMeta.links.map((link: PaginationLink) => (
              <li
                key={`${link.label}-${Math.random()}`}
                className={`page-item ${link.active ? 'active' : ''} ${link.url ? '' : 'disabled'}`}
              >
                {link.url ? (
                  <Link
                    preserveScroll={preserveScroll}
                    className="page-link"
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    href={link.url}
                    only={only}
                  />
                ) : (
                  <Link
                    className="page-link"
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    href={'#'}
                    tabIndex={-1}
                    aria-disabled="true"
                  />
                )}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  )
}
