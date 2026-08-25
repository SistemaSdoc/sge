import { Head, Link, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, CreditCard, LogOut } from 'lucide-react';

const MESES = [
  'Janeiro',
  'Fevereiro',
  'Março',
  'Abril',
  'Maio',
  'Junho',
  'Julho',
  'Agosto',
  'Setembro',
  'Outubro',
  'Novembro',
  'Dezembro',
];

export default function PropinaPendente() {
  // Acede às props enviadas pelo middleware via Inertia::render()
  const { props } = usePage();
  const { pendencias = [], total = 0 } = props;

  return (
    <>
      <Head title="Propinas em atraso" />

      <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-red-50 to-rose-50 p-4">
        <div className="w-full max-w-lg rounded-2xl border border-red-100 bg-white p-8 shadow-2xl shadow-red-200/50 transition-all">
          {/* Cabeçalho */}
          <div className="mb-6 flex items-center gap-4">
            <div className="rounded-full bg-red-100 p-3">
              <AlertCircle className="h-8 w-8 text-red-600" />
            </div>
            <h1 className="text-2xl font-bold text-gray-800">
              Propinas em atraso
            </h1>
          </div>

          {/* Mensagem */}
          <div className="mb-6 rounded-r-lg border-l-4 border-red-500 bg-red-50 p-4">
            <p className="font-medium text-red-700">Acesso bloqueado</p>
            <p className="text-sm text-gray-600">
              Não é possível continuar enquanto houver pendências.
            </p>
          </div>

          {/* Lista de meses em falta */}
          {pendencias.length > 0 && (
            <div className="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
              <ul className="divide-y divide-gray-200">
                {pendencias.map((p, index) => (
                  <li
                    key={`${p.item_pagavel_id}-${p.mes}-${p.ano}-${index}`}
                    className="flex items-center justify-between px-5 py-3 transition-colors hover:bg-gray-100"
                  >
                    <span className="font-medium text-gray-700">{p.nome}</span>
                    <span className="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">
                      {MESES[p.mes - 1]} / {p.ano}
                    </span>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Total */}
          {total > 0 && (
            <div className="mb-6 rounded-xl bg-gray-100 py-3 text-center">
              <span className="text-gray-600">
                Total de <strong className="text-red-600">{total}</strong>{' '}
                {total === 1 ? 'mês em falta' : 'meses em falta'}
              </span>
            </div>
          )}

          {/* Botões */}
          <div className="space-y-3">
            <Link
              href="/minhas-propinas"
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3 font-medium text-white shadow-md transition-all hover:bg-red-700 hover:shadow-lg"
            >
              <CreditCard className="h-5 w-5" />
              Regularizar propinas
            </Link>

            <button
              onClick={() => window.history.back()}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-gray-200 px-4 py-3 font-medium text-gray-700 transition-all hover:bg-gray-300"
            >
              <ArrowLeft className="h-5 w-5" />
              Voltar
            </button>
          </div>

          {/* Rodapé com logout */}
          <div className="mt-6 flex justify-center border-t border-gray-200 pt-4">
            <Link
              href="/logout"
              method="post"
              as="button"
              className="flex items-center gap-1 text-sm text-gray-400 transition-colors hover:text-red-500"
            >
              <LogOut className="h-4 w-4" />
              Sair
            </Link>
          </div>
        </div>
      </div>
    </>
  );
}

// 👇 Impede que o layout padrão da aplicação seja aplicado
PropinaPendente.layout = null;
