import { Building2, CheckCircle2, LogIn, Radio, Users } from 'lucide-react';

function Kpi({ icon: Icon, label, value, hint, color }) {
  return (
    <div className="flex flex-col gap-1 border bg-card p-4">
      <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
        <Icon className="size-3" />
        {label}
      </span>
      <span className="text-3xl font-semibold" style={color ? { color } : undefined}>
        {value}
      </span>
      <span className="text-xs text-muted-foreground">{hint}</span>
    </div>
  );
}

export function KpiStrip({ kpis = {}, utilizadores = {}, logins = null, online = null }) {
  const pct = kpis.total ? Math.round((kpis.activas / kpis.total) * 100) : 0;
  const semDados = 'Sem dados ainda';
  const variacao = logins?.variacao;

  return (
    <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
      <Kpi icon={Building2} label="Total de Instituições" value={kpis.total ?? 0} hint={`+${kpis.novas_semana ?? 0} esta semana`} />
      <Kpi icon={CheckCircle2} label="Activas" value={kpis.activas ?? 0} hint={`${pct}% do total`} color="var(--color-success)" />
      <Kpi icon={Users} label="Utilizadores" value={utilizadores.total ?? 0} hint={`+${utilizadores.novos_semana ?? 0} esta semana`} />
      <Kpi
        icon={Radio}
        label="Online agora"
        value={online?.total ?? '—'}
        hint={online ? `em ${online.por_instituicao?.length ?? 0} instituições` : semDados}
        color="#0E9E83"
      />
      <Kpi
        icon={LogIn}
        label="Logins hoje"
        value={logins?.hoje ?? '—'}
        hint={logins ? (variacao != null ? `${variacao > 0 ? '+' : ''}${variacao}% vs ontem` : 'hoje') : semDados}
      />
    </div>
  );
}