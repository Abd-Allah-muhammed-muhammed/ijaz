import { usePage } from '@inertiajs/react';

const useActiveRoute = () => {
  const { url, component } = usePage(); // `url` is the current path, `component` is the page name.
  const normalize = (str: string) => {
    return str.replace(/^\/+|\/+$/g, '').replace(/\/\*/g, '(/.*)?');
  };

  return {
    matchUrl: function (pattern: string) {
      return new RegExp(`^/${normalize(pattern)}$`, 'i').test(url);
    },
    matchComponents: function (pattern: string) {
      return new RegExp(`^${normalize(pattern)}$`, 'i').test(
        component.replace(/^\/+|\/+$/g, ''),
      );
    },
    matchAny: function (pattern: string) {
      return this.matchUrl(pattern) || this.matchComponents(pattern);
    },
  };
};
export default useActiveRoute;
