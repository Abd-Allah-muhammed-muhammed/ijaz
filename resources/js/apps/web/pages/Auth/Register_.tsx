import type { ComponentProps } from 'react';
import RegisterPage from './Register/Register_';

type RegisterPageProps = ComponentProps<typeof RegisterPage>;

/**
 * Inertia + Laravel Vite preload require a real module at this path
 * (`InertiaPagePath::viteEntry` → `@vite([...])`). A pure
 * `export { default } from '…'` re-export is elided by Rollup and never
 * appears as a `src` key in the production manifest.
 */
export default function Register_(props: RegisterPageProps) {
  return <RegisterPage {...props} />;
}
