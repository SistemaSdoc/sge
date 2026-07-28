import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, XCircle, AlertCircle, Clock } from 'lucide-react';

export function TabHistorico({ grupoPap, historico = [] }) {

	const getStatusConfig = (status) => {
		const configs = {
			'pendente': {
				label: 'Pendente',
				variant: 'secondary',
				icon: Clock,
				color: 'text-gray-600',
				bgColor: 'bg-gray-50',
			},
			'aprovado': {
				label: 'Aprovado',
				variant: 'default',
				icon: CheckCircle,
				color: 'text-green-600',
				bgColor: 'bg-green-50',
			},
			'reprovado': {
				label: 'Reprovado',
				variant: 'destructive',
				icon: XCircle,
				color: 'text-red-600',
				bgColor: 'bg-red-50',
			},
			'melhoria-solicitada': {
				label: 'Melhoria Solicitada',
				variant: 'outline',
				icon: AlertCircle,
				color: 'text-yellow-600',
				bgColor: 'bg-yellow-50',
			},
		};

		return configs[status] || configs['pendente'];
	};

	return (
		<div className="mx-auto w-full max-w-6xl space-y-6 p-6">

			{/* Cabeçalho */}
			<div>
				<h2 className="text-2xl font-semibold">Histórico de Aprovação</h2>
				<p className="mt-1 text-gray-600">Acompanhe todas as decisões tomadas sobre este tema PAP.</p>
			</div>

			{/* Informações do Grupo */}
			<Card>
				<CardHeader>
					<CardTitle className="text-lg">{grupoPap.nome_grupo}</CardTitle>
				</CardHeader>
				<CardContent className="grid grid-cols-1 gap-6 md:grid-cols-3">
					<div>
						<p className="text-xs uppercase text-gray-500 font-semibold">Tema</p>
						<p className="text-sm font-medium mt-1">{grupoPap.tema_grupo}</p>
					</div>
					<div>
						<p className="text-xs uppercase text-gray-500 font-semibold">Turma</p>
						<p className="text-sm font-medium mt-1">{grupoPap.turma?.nome || 'Não informado'}</p>
					</div>
					<div>
						<p className="text-xs uppercase text-gray-500 font-semibold">Estado Atual</p>
						<div className="mt-1">
							<Badge variant={getStatusConfig(grupoPap.status_aprovacao).variant}>
								{getStatusConfig(grupoPap.status_aprovacao).label}
							</Badge>
						</div>
					</div>
				</CardContent>
			</Card>

			{/* Histórico de Decisões */}
			<Card>
				<CardHeader>
					<CardTitle className="text-lg">Histórico de Decisões</CardTitle>
				</CardHeader>
				<CardContent>
					{historico.length === 0 ? (
						<div className="py-8 text-center">
							<p className="text-sm text-gray-500">Nenhum histórico disponível.</p>
						</div>
					) : (
						<div className="space-y-4">
							{historico.map((item, index) => {
								const statusConfig = getStatusConfig(item.estado_novo);
								const StatusIcon = statusConfig.icon;

								return (
									<div 
										key={item.id} 
										className={`rounded-lg border p-4 ${statusConfig.bgColor}`}
									>
										{/* Cabeçalho do item */}
										<div className="flex items-start justify-between gap-4">
											<div className="flex items-start gap-3 flex-1">
												<StatusIcon className={`w-5 h-5 ${statusConfig.color} mt-0.5 flex-shrink-0`} />
												<div className="flex-1">
													<div className="flex items-center gap-2">
														<Badge variant={statusConfig.variant} className="text-xs">
															{statusConfig.label}
														</Badge>
														<span className="text-xs text-gray-500 font-medium">
															{item.created_at ? new Date(item.created_at).toLocaleDateString('pt-PT', {
																weekday: 'short',
																day: '2-digit',
																month: 'short',
																year: 'numeric',
															}) : ''}
														</span>
														<span className="text-xs text-gray-500">
															{item.created_at ? new Date(item.created_at).toLocaleTimeString('pt-PT', {
																hour: '2-digit',
																minute: '2-digit',
															}) : ''}
														</span>
													</div>
													<p className="text-sm font-medium mt-2">
														{item.utilizador?.nome || 'Utilizador não informado'}
													</p>
												</div>
											</div>
										</div>

										{/* Comentário/Motivo */}
										{item.comentario && (
											<div className="mt-3 ml-8 p-3 rounded-md bg-white bg-opacity-60 border border-gray-200">
												<p className="text-sm text-gray-700">{item.comentario}</p>
											</div>
										)}

										{/* Estado anterior (opcional) */}
										{item.estado_anterior && item.estado_anterior !== item.estado_novo && (
											<div className="mt-2 ml-8">
												<p className="text-xs text-gray-500">
													Anterior: <span className="font-medium">{getStatusConfig(item.estado_anterior).label}</span>
												</p>
											</div>
										)}
									</div>
								);
							})}
						</div>
					)}
				</CardContent>
			</Card>

		</div>
	);
}