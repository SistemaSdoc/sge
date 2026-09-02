# Plano: autenticação Google apenas para utilizadores pré-cadastrados

## Regra de negócio

O login com Google não pode criar contas. O email devolvido pelo Google deve corresponder a um utilizador já existente na base central.

- Email inexistente: rejeitar o login, sem inserir nem alterar registos.
- Email existente sem `google_id`: associar o `google_id` e o avatar do Google ao utilizador existente, e autenticar.
- Email existente com o mesmo `google_id`: autenticar sem criar registo e atualizar apenas dados permitidos do perfil externo, como avatar.
- Email existente com outro `google_id`: rejeitar o login. Isto evita associar uma identidade Google diferente a uma conta já vinculada.
- Remover completamente a atribuição automática da role `Candidato` e qualquer dependência de `Role`/`Str` neste fluxo.

## Melhorias propostas

1. Substituir `updateOrCreate` por uma consulta exclusiva ao utilizador existente por email.
2. Encapsular a decisão de vínculo no serviço, mantendo o controller responsável apenas pelo redirect, tratamento de falhas e resposta HTTP.
3. Usar uma exceção específica para o email não autorizado e para conflito de identidade, com mensagem genérica ao utilizador e contexto técnico apenas no log.
4. Regenerar a sessão após `Auth::login()` para manter a proteção contra session fixation.
5. Manter a verificação de estado do Socialite baseada na sessão, pois estas são rotas web e não uma API stateless.
6. Cobrir o redirect, primeiro vínculo, login repetido, email inexistente, conflito de `google_id` e estado OAuth inválido.

## Mensagem de falha

Usar uma mensagem única para email inexistente e conflito de identidade:

> Não foi possível iniciar sessão com esta conta Google. Confirme que o seu email já está cadastrado e tente novamente.

A mensagem não revela se o email existe nem detalhes de OAuth.

## Arquivos previstos

- `app/Services/Auth/GoogleAuthService.php`: aplicar a regra de lookup, vínculo e autenticação.
- `app/Http/Controllers/Tenant/Auth/GoogleAuthController.php`: tratar a falha de acesso sem expor exceções internas.
- `app/Exceptions/UnauthorizedGoogleUserException.php`: representar a tentativa sem utilizador elegível.
- `tests/Feature/Auth/GoogleAuthTest.php`: substituir o caso de criação e adicionar os cenários de segurança.

## Critério de aceitação

Um callback Google para email não cadastrado nunca cria um `User`; somente um `User` previamente existente pode ser autenticado.
