import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export function BrandingHeader() {
  const { branding, auth } = usePage<any>().props;
  const brand = branding || {
    appName: 'SaaS App',
    logoUrl: '/images/logo.png',
  };

  const targetRoute = auth?.user ? '/dashboard' : '/';

  return (
    <Link href={targetRoute} className="flex items-center gap-2.5 group">
      {brand.logoUrl ? (
        <img
          src={brand.logoUrl}
          alt={brand.appName}
          className="h-8 w-auto object-contain transition-transform group-hover:scale-105"
        />
      ) : (
        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 font-bold text-white transition-transform group-hover:scale-105">
          {brand.appName.charAt(0)}
        </div>
      )}
      <span className="font-bold text-lg tracking-tight text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
        {brand.appName}
      </span>
    </Link>
  );
}

export default BrandingHeader;
