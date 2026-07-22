import { gerar } from '@/actions/App/Http/Controllers/CertificadoController';

export function useCertificado(params) {
  const gerarCertificado = async (e, alunoId) => {
    e.stopPropagation();

    try {
      const response = await fetch(gerar({ ...params, aluno: alunoId }).url);

      const blob = await response.blob();
      const objectUrl = window.URL.createObjectURL(blob);

      const link = document.createElement('a');
      link.href = objectUrl;
      link.setAttribute('download', 'certificado.pdf');
      document.body.appendChild(link);
      link.click();
      link.remove();

      window.URL.revokeObjectURL(objectUrl);
    } catch (error) {
      console.error('Erro ao gerar certificado:', error);
    }
  };

  return { gerarCertificado };
}
