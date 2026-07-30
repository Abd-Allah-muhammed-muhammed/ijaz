import { usePageData } from '@/vendor/metronic/layout/core';
import { Link } from '@inertiajs/react';
import { Fragment } from 'react';

/**
 * Page title + breadcrumb strip for the Admin shell.
 * Reads from PageData context (fed by headless <PageTitle /> on each page).
 */
export function Toolbar() {
  const { pageTitle, pageDescription, pageBreadcrumbs } = usePageData();

  if (!pageTitle && (!pageBreadcrumbs || pageBreadcrumbs.length === 0)) {
    return null;
  }

  const crumbs = (pageBreadcrumbs ?? []).filter((crumb) => !crumb.isSeparator || crumb.title);

  return (
    <div className="border-b border-border/80 bg-background px-4 py-5 md:px-6">
      <div className="mx-auto flex w-full max-w-[90rem] flex-col gap-1">
        {crumbs.length > 0 && (
          <nav aria-label="Breadcrumb" className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
            {crumbs.map((crumb, index) => (
              <Fragment key={`${crumb.path}-${index}`}>
                {index > 0 && <span className="text-border">/</span>}
                {crumb.path && !crumb.isActive ? (
                  <Link href={crumb.path} className="transition-colors hover:text-foreground">
                    {crumb.title}
                  </Link>
                ) : (
                  <span className={crumb.isActive ? 'text-foreground' : undefined}>{crumb.title}</span>
                )}
              </Fragment>
            ))}
          </nav>
        )}
        {pageTitle && (
          <h1 className="text-xl font-semibold tracking-tight text-foreground md:text-2xl">{pageTitle}</h1>
        )}
        {pageDescription && (
          <p className="max-w-2xl text-sm text-muted-foreground">{pageDescription}</p>
        )}
      </div>
    </div>
  );
}

/** @deprecated Alias kept for existing page imports */
export function ToolbarWrapper() {
  return <Toolbar />;
}
