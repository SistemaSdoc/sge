import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, XCircle, AlertCircle } from 'lucide-react';

import ModalDecisaoAprovacao from '../../../../../../../../pages/pap/components/ModalDecisaoAprovacao';
import { aprovar, reprovar, solicitarMelhoria } from '@/actions/App/Http/Controllers/GrupoPapAprovacaoController';

export function TabAprovacao({ params, grupoPap, can, turma }) {
	const [open, setOpen] = useState(false);
	const [action, setAction] = useState(null);
	const [comentario, setComentario] = useState('');
	const [loading, setLoading] = useState(false);

	const abrir = (tipo) => {
		setAction(tipo);
		setComentario('');
		setOpen(true);
	};

	const fechar = () => {
		if (loading) return;
		setOpen(false);
		setAction(null);
		setComentario('');
	};

	const confirmar = () => {
		if (!action) return;
		setLoading(true);

		const rota = action === 'aprovar' ? aprovar : action === 'reprovar' ? reprovar : solicitarMelhoria;

		router.post(rota.url({ grupoPap: grupoPap.id }),
			action === 'aprovar' ? { comentario: comentario || null } : action === 'reprovar' ? { motivo: comentario } : { recomendacao: comentario },
			{
				preserveScroll: true,
				onSuccess: () => {
					setLoading(false);
					fechar();
					router.reload();
				},
				onError: () => setLoading(false),
			}
		);
	};

	const getStatusConfig = (status) => {
		const configs = {
			'pendente': {
				label: 'Pendente',
				variant: 'secondary',
				icon: AlertCircle,
				color: 'text-gray-600',
			},
			'aprovado': {
				label: 'Aprovado',
				variant: 'default',
				icon: CheckCircle,
				color: 'text-green-600',
			},
			'reprovado': {
				label: 'Reprovado',
				variant: 'destructive',
				icon: XCircle,
				color: 'text-red-600',
			},
			'melhoria-solicitada': {
				label: 'Melhoria Solicitada',
				variant: 'outline',
				icon: AlertCircle,
				color: 'text-yellow-600',
			},
		};

		return configs[status?.toLowerCase()] || configs['pendente'];
	};

	const statusAtual = (grupoPap.status_aprovacao || '').toLowerCase();
	const statusConfig = getStatusConfig(statusAtual);
	const StatusIcon = statusConfig.icon;
	const isFinalizado = statusAtual === 'aprovado' || statusAtual === 'reprovado';

	return (
		<div className="w-full space-y-6">

			{/* Card Principal */}
			<Card>
				<CardHeader>
					<div className="flex items-center justify-between">
						<CardTitle className="text-lg">Aprovação do Tema</CardTitle>
						<Badge variant={statusConfig.variant}>
							{statusConfig.label}
						</Badge>
					</div>
				</CardHeader>

				<CardContent className="space-y-6">
					{/* Informações do Tema */}
					<div className="grid grid-cols-1 gap-6 md:grid-cols-2">
						<div>
							<p className="text-xs uppercase text-gray-500 font-semibold">Tema</p>
							<p className="text-sm font-medium mt-1">{grupoPap.tema_grupo}</p>
						</div>
						<div>
							<p className="text-xs uppercase text-gray-500 font-semibold">Turma</p>
							<p className="text-sm font-medium mt-1">{turma?.nome || 'Não informado'}</p>
						</div>
						<div>
							<p className="text-xs uppercase text-gray-500 font-semibold">Grupo</p>
							<p className="text-sm font-medium mt-1">{grupoPap.nome_grupo}</p>
						</div>
						<div>
							<p className="text-xs uppercase text-gray-500 font-semibold">Professor Tutor</p>
							<p className="text-sm font-medium mt-1">{grupoPap.professor?.nome || 'Não informado'}</p>
						</div>
					</div>

					{/* Separador */}
					<div className="border-t" />

					{/* Área de Decisão */}
					{!isFinalizado ? (
						<div className="space-y-4">
							<p className="text-sm text-gray-600">
								Decida sobre a aprovação, reprovação ou solicite melhorias.
							</p>

							{can?.update && (
								<div className="flex flex-wrap gap-2 justify-end">
									<Button 
										variant="outline" 
										onClick={() => abrir('melhoria')}
										disabled={loading}
									>
										<AlertCircle className="w-4 h-4 mr-2" />
										Solicitar Melhoria
									</Button>
									<Button 
										variant="destructive" 
										onClick={() => abrir('reprovar')}
										disabled={loading}
									>
										<XCircle className="w-4 h-4 mr-2" />
										Reprovar
									</Button>
									<Button 
										onClick={() => abrir('aprovar')}
										disabled={loading}
										className="bg-green-600 hover:bg-green-700"
									>
										<CheckCircle className="w-4 h-4 mr-2" />
										Aprovar
									</Button>
								</div>
							)}
						</div>
					) : (
						<div className="rounded-lg bg-gray-50 p-4 text-center">
							<div className="flex items-center justify-center gap-2">
								<StatusIcon className={`w-5 h-5 ${statusConfig.color}`} />
								<p className="text-sm font-medium text-gray-700">
									Este tema já foi {statusConfig.label.toLowerCase()} e não pode ser alterado.
								</p>
							</div>
						</div>
					)}
				</CardContent>
			</Card>

			{/* Modal */}
			<ModalDecisaoAprovacao
				open={open}
				onClose={fechar}
				tema={grupoPap}
				action={action}
				comentario={comentario}
				onComentarioChange={setComentario}
				onConfirmar={confirmar}
				loading={loading}
			/>
		</div>
	);
}