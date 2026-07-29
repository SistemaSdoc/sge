<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propinas em atraso</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fdf2f2 0%, #fef9f9 100%);
            padding: 20px;
        }
        .container {
            max-width: 560px;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(220, 38, 38, 0.10), 0 8px 20px rgba(0,0,0,0.05);
            padding: 40px 35px 35px;
            border: 1px solid rgba(220, 38, 38, 0.12);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        .icon {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon svg {
            width: 28px;
            height: 28px;
            fill: none;
            stroke: #dc2626;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a2e;
            letter-spacing: -0.02em;
        }
        .subtitle {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }
        .subtitle strong {
            color: #dc2626;
            font-weight: 600;
        }
        .lista {
            list-style: none;
            padding: 0;
            margin: 0 0 24px 0;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #f1f3f5;
            background: #fafbfc;
        }
        .lista li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid #edf2f7;
        }
        .lista li:last-child {
            border-bottom: none;
        }
        .item-nome {
            font-weight: 500;
            color: #1a1a2e;
            font-size: 0.95rem;
        }
        .item-periodo {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .total {
            text-align: center;
            font-size: 0.95rem;
            color: #6b7280;
            background: #f9fafb;
            padding: 14px 0;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .total span {
            font-weight: 600;
            color: #1a1a2e;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            border-radius: 60px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            background: #dc2626;
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
        }
        .btn:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.30);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            box-shadow: none;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .footer {
            margin-top: 28px;
            text-align: center;
            font-size: 0.8rem;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
        }
        .footer a {
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
        }
        .footer a:hover {
            color: #dc2626;
        }
        @media (max-width: 480px) {
            .container { padding: 25px 20px; }
            h1 { font-size: 1.3rem; }
            .lista li { flex-direction: column; align-items: flex-start; gap: 6px; padding: 12px 16px; }
            .item-periodo { align-self: flex-start; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
            </div>
            <h1>Propinas em atraso</h1>
        </div>

        <div class="subtitle">
            <strong>Acesso bloqueado</strong> — não é possível continuar enquanto houver pendências.
        </div>

        @if(count($pendencias) > 0)
            <ul class="lista">
                @foreach($pendencias as $p)
                    <li>
                        <span class="item-nome">{{ $p['nome'] }}</span>
                        <span class="item-periodo">{{ $meses[$p['mes']] ?? $p['mes'] }} / {{ $p['ano'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="total">
            Total de <span>{{ $total }}</span> {{ $total === 1 ? 'mês em falta' : 'meses em falta' }}
        </div>

        <div class="actions">
            <a href="{{ $previousUrl ?? route('dashboard') }}" class="btn btn-secondary">Voltar</a>
        </div>

    </div>
</body>
</html>