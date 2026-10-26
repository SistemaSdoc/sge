import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as centralAppearanceEdit } from '@/routes/central/dashboard/appearance';
import { edit as tenantAppearanceEdit } from '@/routes/tenant/dashboard/appearance';

const currentAppearanceEdit = () => {
  if (typeof window === 'undefined') {
    return tenantAppearanceEdit();
  }

  const centralUrl = new URL(
    centralAppearanceEdit().url,
    window.location.origin,
  );

  return centralUrl.host === window.location.host
    ? centralAppearanceEdit()
    : tenantAppearanceEdit();
};

export default function Appearance() {
  return (
    <>
      <Head title="Configurações de aparência" />

      <h1 className="sr-only">Configurações de aparência</h1>

      <div className="space-y-6">
        <Heading
          variant="small"
          title="Configurações de aparência"
          description="Actualize as configurações de aparência para sua conta"
        />
        <AppearanceTabs />
      </div>
    </>
  );
}

Appearance.layout = {
  breadcrumbs: [
    {
      title: 'Configurações de aparência',
      href: currentAppearanceEdit(),
    },
  ],
};
