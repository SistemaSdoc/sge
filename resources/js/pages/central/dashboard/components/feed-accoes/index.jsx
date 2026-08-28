import { ActionFeed as Base } from '@/pages/tenant/dashboards/director/components/feed-accoes';
import { ACCOES } from '@/utils/central-static';

export function ActionFeed() {
  return <Base items={ACCOES} />;
}
