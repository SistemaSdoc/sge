import { Head, Link, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, CreditCard, LogOut } from 'lucide-react';

const MESES = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
];

export default function PropinaPendente() {
    // Acede às props enviadas pelo middleware via Inertia::render()
    const { props } = usePage();
    const { pendencias = [], total = 0 } = props;

    return (
        <>
            <Head title="Propinas em atraso" />

            <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-50 to-rose-50 p-4">
                <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl shadow-red-200/50 border border-red-100 p-8 transition-all">
                    {/* Cabeçalho */}
                    <div className="flex items-center gap-4 mb-6">
                        <div className="p-3 bg-red-100 rounded-full">
                            <AlertCircle className="w-8 h-8 text-red-600" />
                        </div>
                        <h1 className="text-2xl font-bold text-gray-800">Propinas em atraso</h1>
                    </div>

                    {/* Mensagem */}
                    <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg mb-6">
                        <p className="text-red-700 font-medium">
                            Acesso bloqueado
                        </p>
                        <p className="text-gray-600 text-sm">
                            Não é possível continuar enquanto houver pendências.
                        </p>
                    </div>

                    {/* Lista de meses em falta */}
                    {pendencias.length > 0 && (
                        <div className="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden mb-6">
                            <ul className="divide-y divide-gray-200">
                                {pendencias.map((p, index) => (
                                    <li 
                                        key={`${p.item_pagavel_id}-${p.mes}-${p.ano}-${index}`}
                                        className="flex items-center justify-between px-5 py-3 hover:bg-gray-100 transition-colors"
                                    >
                                        <span className="font-medium text-gray-700">
                                            {p.nome}
                                        </span>
                                        <span className="bg-red-100 text-red-700 text-sm font-semibold px-3 py-1 rounded-full">
                                            {MESES[p.mes - 1]} / {p.ano}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* Total */}
                    {total > 0 && (
                        <div className="text-center bg-gray-100 rounded-xl py-3 mb-6">
                            <span className="text-gray-600">
                                Total de <strong className="text-red-600">{total}</strong> {total === 1 ? 'mês em falta' : 'meses em falta'}
                            </span>
                        </div>
                    )}

                    {/* Botões */}
                    <div className="space-y-3">
                        <Link
                            href="/minhas-propinas"
                            className="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-xl transition-all shadow-md hover:shadow-lg"
                        >
                            <CreditCard className="w-5 h-5" />
                            Regularizar propinas
                        </Link>

                        <button
                            onClick={() => window.history.back()}
                            className="w-full flex items-center justify-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-4 rounded-xl transition-all"
                        >
                            <ArrowLeft className="w-5 h-5" />
                            Voltar
                        </button>
                    </div>

                    {/* Rodapé com logout */}
                    <div className="mt-6 pt-4 border-t border-gray-200 flex justify-center">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="flex items-center gap-1 text-sm text-gray-400 hover:text-red-500 transition-colors"
                        >
                            <LogOut className="w-4 h-4" />
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