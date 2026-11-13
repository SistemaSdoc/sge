<?php

namespace App\Policies\Tenant\Pap;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\User;

class GrupoPapPolicy
{
    /**
     * Mantém as abilities compatíveis e delega cada decisão à policy do respectivo fluxo.
     */
    public function viewAny(User $user): bool
    {
        return (new GrupoPapAcessoPolicy)->viewAny($user);
    }

    /**
     * Determina se o utilizador pode consultar um grupo PAP.
     */
    public function view(User $user, GrupoPap $grupo): bool
    {
        return (new GrupoPapAcessoPolicy)->view($user, $grupo);
    }

    /**
     * Determina se o utilizador pode criar grupos PAP.
     */
    public function create(User $user): bool
    {
        return (new GrupoPapAcessoPolicy)->create($user);
    }

    /**
     * Determina se o utilizador pode editar um grupo PAP.
     */
    public function update(User $user, GrupoPap $grupoPap): bool
    {
        return (new GrupoPapAcessoPolicy)->update($user, $grupoPap);
    }

    /**
     * Determina se um elemento do grupo pode corrigir a proposta de tema.
     */
    public function corrigirTema(User $user, GrupoPap $grupoPap): bool
    {
        return (new TemaPapPolicy)->corrigirTema($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode actualizar a proposta após uma melhoria.
     */
    public function atualizarTema(User $user, GrupoPap $grupoPap): bool
    {
        return (new TemaPapPolicy)->atualizarTema($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode reenviar o tema corrigido.
     */
    public function reenviarTema(User $user, GrupoPap $grupoPap): bool
    {
        return (new TemaPapPolicy)->reenviarTema($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode consultar o histórico do grupo.
     */
    public function viewHistorico(User $user, GrupoPap $grupoPap): bool
    {
        return (new AprovacaoTemaPapPolicy)->viewHistorico($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode consultar temas que aguardam melhoria.
     */
    public function viewMelhorias(User $user): bool
    {
        return (new AprovacaoTemaPapPolicy)->viewMelhorias($user);
    }

    /**
     * Determina se a coordenação tutora pode aprovar a proposta.
     */
    public function aprovar(User $user, GrupoPap $grupoPap): bool
    {
        return (new AprovacaoTemaPapPolicy)->aprovar($user, $grupoPap);
    }

    /**
     * Determina se a coordenação tutora pode reprovar a proposta.
     */
    public function reprovar(User $user, GrupoPap $grupoPap): bool
    {
        return (new AprovacaoTemaPapPolicy)->reprovar($user, $grupoPap);
    }

    /**
     * Determina se a coordenação tutora pode solicitar melhorias.
     */
    public function solicitarMelhoria(User $user, GrupoPap $grupoPap): bool
    {
        return (new AprovacaoTemaPapPolicy)->solicitarMelhoria($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode definir a data da defesa.
     */
    public function definirData(User $user, GrupoPap $grupoPap): bool
    {
        return (new DefesaPapPolicy)->definirData($user, $grupoPap);
    }

    /**
     * Determina se um elemento do grupo pode definir a proposta de tema.
     */
    public function definirTema(User $user, GrupoPap $grupoPap): bool
    {
        return (new TemaPapPolicy)->definirTema($user, $grupoPap);
    }

    /**
     * Determina se o professor tutor pode validar o tema.
     */
    public function aprovarComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return (new AprovacaoTemaPapPolicy)->aprovarComoTutor($user, $grupoPap);
    }

    /**
     * Determina se o professor tutor pode solicitar melhoria do tema.
     */
    public function solicitarMelhoriaComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return (new AprovacaoTemaPapPolicy)->solicitarMelhoriaComoTutor($user, $grupoPap);
    }

    /**
     * Determina se um elemento pode submeter uma versão do trabalho.
     */
    public function submeterTrabalho(User $user, GrupoPap $grupoPap): bool
    {
        return (new TrabalhoPapPolicy)->submeterTrabalho($user, $grupoPap);
    }

    /**
     * Determina se o professor tutor pode aprovar o trabalho.
     */
    public function aprovarTrabalhoComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return (new TrabalhoPapPolicy)->aprovarTrabalhoComoTutor($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode alterar o professor tutor do grupo.
     */
    public function alterarTutor(User $user, GrupoPap $grupoPap): bool
    {
        return (new GrupoPapAcessoPolicy)->alterarTutor($user, $grupoPap);
    }

    /**
     * Determina se o professor tutor pode solicitar correcções ao trabalho.
     */
    public function solicitarCorrecaoTrabalhoComoTutor(User $user, GrupoPap $grupoPap): bool
    {
        return (new TrabalhoPapPolicy)->solicitarCorrecaoTrabalhoComoTutor($user, $grupoPap);
    }

    /**
     * Determina se a coordenação tutora pode aprovar definitivamente o trabalho.
     */
    public function aprovarTrabalhoComoCoordenacao(User $user, GrupoPap $grupoPap): bool
    {
        return (new TrabalhoPapPolicy)->aprovarTrabalhoComoCoordenacao($user, $grupoPap);
    }

    /**
     * Determina se a coordenação tutora pode pedir correcções ao trabalho.
     */
    public function solicitarCorrecaoTrabalhoComoCoordenacao(User $user, GrupoPap $grupoPap): bool
    {
        return (new TrabalhoPapPolicy)->solicitarCorrecaoTrabalhoComoCoordenacao($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode descarregar versões do trabalho.
     */
    public function downloadVersaoTrabalho(User $user, GrupoPap $grupoPap): bool
    {
        return (new TrabalhoPapPolicy)->downloadVersaoTrabalho($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode eliminar um grupo PAP.
     */
    public function delete(User $user, GrupoPap $grupoPap): bool
    {
        return (new GrupoPapAcessoPolicy)->delete($user, $grupoPap);
    }

    /**
     * Impede a restauração directa de grupos PAP.
     */
    public function restore(User $user, GrupoPap $grupoPap): bool
    {
        return (new GrupoPapAcessoPolicy)->restore($user, $grupoPap);
    }

    /**
     * Impede a eliminação forçada de grupos PAP.
     */
    public function forceDelete(User $user, GrupoPap $grupoPap): bool
    {
        return (new GrupoPapAcessoPolicy)->forceDelete($user, $grupoPap);
    }

    /**
     * Determina se o utilizador pode escolher uma instituição nos filtros PAP.
     */
    public function selecionarInstituicao(User $user): bool
    {
        return (new GrupoPapAcessoPolicy)->selecionarInstituicao($user);
    }

    /**
     * Determina se o utilizador pode escolher um ano lectivo nos filtros PAP.
     */
    public function selecionarAnoLectivo(User $user): bool
    {
        return (new GrupoPapAcessoPolicy)->selecionarAnoLectivo($user);
    }

    /**
     * Determina se o utilizador tem uma função de direcção PAP.
     */
    private function isPapDirector(User $user): bool
    {
        return (new GrupoPapAcessoPolicy)->isPapDirector($user);
    }
}
