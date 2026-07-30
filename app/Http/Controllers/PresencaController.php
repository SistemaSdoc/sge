use App\Services\VerificadorPropinaService;

class PresencaController extends Controller
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    public function index(Request $request, Turma $turma)
    {
        $alunos = $turma->alunos()
            ->wherePivot('activo', true)
            ->with('inscricao.candidato:id,nome')
            ->get();

        $statusPagamento = $this->verificador->statusAlunos($alunos);

        // se preferires, junta directamente na lista de presenças
        $listaPresenca = $alunos->map(function ($aluno) use ($statusPagamento) {
            $status = $statusPagamento->firstWhere('aluno_id', $aluno->id);

            return [
                'aluno_id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'propina_em_dia' => $status['em_dia'],
                // ... campos de presença que já tens
            ];
        });

        return Inertia::render('presencas/index', [
            'lista' => $listaPresenca,
        ]);
    }
}