import { download } from '@/actions/App/Http/Controllers/DeclaracaoController';

export function useDeclaracao(params) {
  const gerarDeclaracao = async (e, alunoId) => {
    e.stopPropagation();

    try {
      const response = await fetch(download({ ...params, aluno: alunoId }).url);

      const blob = await response.blob();
      const objectUrl = window.URL.createObjectURL(blob);

      const link = document.createElement('a');
      link.href = objectUrl;
      link.setAttribute('download', 'declaracao.docx');
      document.body.appendChild(link);
      link.click();
      link.remove();

      window.URL.revokeObjectURL(objectUrl);
    } catch (error) {
      console.error('Erro ao gerar declaração:', error);
    }
  };

  return { gerarDeclaracao };
}
