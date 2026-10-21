# ─── Tenant CS app storage ───
mkdir -p /tmp/sge/tenant-cs-app
chmod -R 775 /tmp/sge/tenant-cs-app

cd ~/sge
if [ ! -L storage/tenantcs.app.pge.edu.ao ]; then
  # Se a pasta real ainda existir, copia o conteúdo
  if [ -d storage/tenantcs.app.pge.edu.ao ] && [ ! -L storage/tenantcs.app.pge.edu.ao ]; then
    rsync -av storage/tenantcs.app.pge.edu.ao/ /tmp/sge/tenant-cs-app/ 2>/dev/null || true
    rm -rf storage/tenantcs.app.pge.edu.ao
  fi
  ln -sfn /tmp/sge/tenant-cs-app storage/tenantcs.app.pge.edu.ao
fi
