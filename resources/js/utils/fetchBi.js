function getValueFromSource(source, candidates) {
  for (const candidate of candidates) {
    const value = source?.[candidate];

    if (typeof value === 'string' && value.trim()) {
      return value.trim();
    }

    if (typeof value === 'number') {
      return String(value);
    }
  }

  return '';
}

function normalizeBiPayload(payload) {
  const source = Array.isArray(payload) ? payload[0] : payload;
  const data =
    source?.data ??
    source?.bi?.data ??
    source?.result ??
    source?.response ??
    source ??
    {};

  return {
    bi: getValueFromSource(data, ['bi', 'numero_bi', 'numeroBi', 'bilhete']),
    nome: getValueFromSource(data, [
      'nome',
      'nome_contribuinte',
      'nome_completo',
      'nomeCompleto',
      'full_name',
      'name',
    ]),
    telefone: getValueFromSource(data, [
      'telefone',
      'telefone1',
      'telemovel',
      'celular',
      'contacto',
      'numero_contacto',
      'numeroContacto',
    ]),
    email: getValueFromSource(data, ['email', 'email_personal', 'mail']),
    morada:
      getValueFromSource(data, [
        'morada',
        'endereco',
        'address',
        'residencia',
        'localizacao',
      ]) ||
      // compose morada from parts if single field not present
      [
        data?.bairro_morada,
        data?.comuna_morada,
        data?.municipio_morada,
        data?.provincia_morada,
        data?.province,
      ]
        .filter(Boolean)
        .join(', '),
  };
}

export async function fetchBi(bi, { signal } = {}) {
  if (!bi) {
    throw new Error('BI inválido');
  }

  // Use local proxy route to avoid browser CORS restrictions
  const url = `/bi/consultar/${encodeURIComponent(bi)}`;

  const response = await fetch(url, {
    headers: { Accept: 'application/json' },
    signal,
  });

  if (!response.ok) {
    const text = await response.text().catch(() => '');
    const message = text || `Erro na resposta (${response.status})`;
    const err = new Error(message);
    err.status = response.status;

    throw err;
  }

  const contentType = response.headers.get('content-type') ?? '';
  let payload = null;

  if (contentType.includes('application/json')) {
    payload = await response.json();
  } else {
    const text = await response.text();

    try {
      payload = JSON.parse(text);
    } catch {
      payload = { raw: text };
    }
  }

  const dados = normalizeBiPayload(payload);

  // If no useful data, surface a clear error
  if (!dados.nome && !dados.telefone && !dados.email && !dados.morada) {
    throw new Error('Nenhum dado foi retornado para este BI.');
  }

  return dados;
}

export default fetchBi;
