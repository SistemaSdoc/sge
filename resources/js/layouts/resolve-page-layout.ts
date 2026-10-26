import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import UserProfileLayout from '@/layouts/user-profile/layout';

export function resolvePageLayout(name: string) {
  if (name.startsWith('errors/')) {
    return null;
  }

  if (
    name === 'central/welcome/index' ||
    name === 'tenant/certificado/show' ||
    name === 'tenant/access-denied'
  ) {
    return null;
  }

  if (name.startsWith('central/auth/') || name.startsWith('tenant/auth/')) {
    return AuthLayout;
  }

  if (
    name === 'tenant/users/profile/show' ||
    name === 'tenant/users/profile/academic' ||
    name === 'tenant/users/profile/security'
  ) {
    return [AppLayout, UserProfileLayout];
  }

  if (name.startsWith('tenant/settings/')) {
    return [AppLayout, SettingsLayout];
  }

  return AppLayout;
}