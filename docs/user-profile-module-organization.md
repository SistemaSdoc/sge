# Organização do módulo de perfil do utilizador

## Objetivo

O módulo de perfil permite consultar e actualizar os dados pessoais, consultar os dados académicos de um aluno, alterar o avatar e gerir a página de segurança. A organização separa HTTP, casos de uso e preparação de dados para manter o controller pequeno e preservar os contratos Inertia existentes.

## Estrutura

```text
app/
├── Actions/Tenant/UserProfile/
│   ├── UpdatePersonalProfile.php
│   └── UpdateProfileAvatar.php
├── Http/Controllers/Tenant/
│   └── UserProfileController.php
├── Http/Requests/Tenant/User/
│   ├── UpdatePersonalProfileRequest.php
│   └── UpdateProfileAvatarRequest.php
└── Services/Tenant/Users/
    ├── UserProfileService.php
    └── UserRoleProfileService.php

resources/js/pages/tenant/users/profile/
├── academic.jsx
├── show.jsx
└── components/
    ├── academic-data.jsx
    ├── personal-data.jsx
    ├── profile-field.jsx
    ├── read-only-field.jsx
    ├── read-only-section.jsx
    └── security-data.jsx
```

## Responsabilidades

### `UserProfileController`

Orquestra o pedido HTTP. É responsável por:

- confirmar que o utilizador autenticado está a consultar o próprio perfil;
- verificar que a página académica pertence a um aluno;
- chamar a Action ou o serviço apropriado;
- devolver a página Inertia ou o redirect com a mensagem existente.

Não deve conter queries de perfil, transacções, uploads ou montagem extensa de props.

### Actions

`UpdatePersonalProfile` actualiza `User` e o `Candidato` relacionado na mesma transacção tenant. Também mantém a invalidação de `email_verified_at` quando o email muda.

`UpdateProfileAvatar` guarda o ficheiro validado no disk público e actualiza o caminho do avatar. A validação e autorização continuam no Form Request.

As Actions recebem dados já validados e não devolvem respostas HTTP.

### `UserProfileService`

Prepara os contratos de leitura usados pelas páginas:

- dados básicos do cabeçalho do perfil;
- dados pessoais com separação entre aluno e utilizador comum;
- dados académicos e a turma activa mais recente;
- dados da página de segurança, incluindo passkeys e regras de password.

Os nomes e a forma dos props devem permanecer compatíveis com as páginas React existentes.

`UserRoleProfileService` permanece separado e é usado apenas pelas Actions de criação e actualização de utilizadores para sincronizar o perfil de Professor. O antigo `app/Services/Tenant/UserProfileService.php` foi removido porque não tinha referências; o serviço activo do perfil está em `app/Services/Tenant/Users/UserProfileService.php`.

### Form Requests

Os Form Requests validam e autorizam as operações de escrita:

- `UpdatePersonalProfileRequest` valida dados pessoais e confirma o próprio utilizador;
- `UpdateProfileAvatarRequest` valida o próprio utilizador e os formatos de imagem permitidos.

## Fluxo de segurança

1. O route model binding resolve o `User` no tenant actual.
2. O Form Request autoriza as operações de escrita do próprio utilizador.
3. O controller confirma também o próprio perfil antes de qualquer leitura ou escrita.
4. As Actions operam apenas nos modelos do tenant actual.
5. Nenhuma Action altera o guard, o tenant activo ou a conexão da aplicação.

## Contratos preservados

- Rotas e nomes Wayfinder permanecem iguais.
- As páginas `show`, `academic` e `security` mantêm os mesmos nomes Inertia.
- Os payloads `user`, `personalData`, `academicData` e os props de segurança mantêm a forma existente.
- As mensagens de sucesso e o comportamento de invalidação do email permanecem iguais.

## Verificação

Executar depois de alterações no módulo:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
npm run build
```
