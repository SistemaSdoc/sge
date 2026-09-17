import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { updatePersonal } from '@/actions/App/Http/Controllers/Tenant/UserProfileController';
import { Button } from '@/components/ui/button';
import { FieldGroup, FieldLegend, FieldSet } from '@/components/ui/field';
import { ReadOnlySection } from './read-only-section';
import { ReadOnlyField } from './read-only-field';
import { ProfileField } from './profile-field';

export function PersonalData({ user, data }) {
  const [isEditing, setIsEditing] = useState(false);
  const isAluno = data.isAluno;
  const form = useForm({
    nome: isAluno ? data.personal.nome : data.basic.nome,
    email: isAluno ? data.address.email : data.basic.email,
    bi: data.personal?.bi ?? '',
    genero: data.personal?.genero ?? '',
    data_nascimento: data.personal?.dataNascimento ?? '',
    nacionalidade: data.personal?.nacionalidade ?? '',
    naturalidade: data.personal?.naturalidade ?? '',
    nome_pai: data.parents?.pai ?? '',
    nome_mae: data.parents?.mae ?? '',
    morada: data.address?.morada ?? '',
    municipio: data.address?.municipio ?? '',
    telefone: isAluno ? data.address.telefone : data.basic.telefone,
  });

  const updateField = (name) => (event) => {
    form.setData(name, event.target.value);
  };

  if (!isEditing) {
    return (
      <div className="w-full max-w-5xl space-y-6">
        {isAluno ? (
          <>
            <ReadOnlySection title="Dados pessoais">
              <ReadOnlyField label="Nome completo" value={data.personal.nome} />

              <ReadOnlyField label="Email" value={data.address.email} />

              <ReadOnlyField label="Nº Bilhete" value={data.personal.bi} />

              <ReadOnlyField
                label="Data de nascimento"
                value={data.personal.dataNascimento}
              />

              <ReadOnlyField label="Género" value={data.personal.genero} />

              <ReadOnlyField
                label="Nacionalidade"
                value={data.personal.nacionalidade}
              />

              <ReadOnlyField
                label="Naturalidade"
                value={data.personal.naturalidade}
              />
            </ReadOnlySection>

            <ReadOnlySection title="Filiação e contacto">
              <ReadOnlyField label="Nome do pai" value={data.parents.pai} />

              <ReadOnlyField label="Nome da mãe" value={data.parents.mae} />

              <ReadOnlyField label="Morada" value={data.address.morada} />

              <ReadOnlyField label="Município" value={data.address.municipio} />

              <ReadOnlyField label="Telefone" value={data.address.telefone} />
            </ReadOnlySection>
          </>
        ) : (
          <ReadOnlySection title="Dados pessoais">
            <ReadOnlyField label="Nome" value={data.basic.nome} />

            <ReadOnlyField label="Email" value={data.basic.email} />

            <ReadOnlyField label="Telefone" value={data.basic.telefone} />
          </ReadOnlySection>
        )}

        <div className="flex justify-end">
          <Button type="button" onClick={() => setIsEditing(true)}>
            Editar as minhas informações
          </Button>
        </div>
      </div>
    );
  }

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        form.put(updatePersonal(user.id).url, {
          preserveScroll: true,
          onSuccess: () => setIsEditing(false),
        });
      }}
      className="w-full max-w-5xl space-y-8"
    >
      {isAluno ? (
        <FieldGroup>
          <FieldSet>
            <FieldLegend>Dados pessoais</FieldLegend>
            <div className="grid gap-6 md:grid-cols-2">
              <ProfileField
                label="Nome completo"
                name="nome"
                value={form.data.nome}
                onChange={updateField('nome')}
                error={form.errors.nome}
              />

              <ProfileField
                label="Email"
                name="email"
                type="email"
                value={form.data.email}
                onChange={updateField('email')}
                error={form.errors.email}
              />

              <ProfileField
                label="Nº Bilhete"
                name="bi"
                value={form.data.bi}
                onChange={updateField('bi')}
                error={form.errors.bi}
              />

              <ProfileField
                label="Data de nascimento"
                name="data_nascimento"
                type="date"
                value={form.data.data_nascimento}
                onChange={updateField('data_nascimento')}
                error={form.errors.data_nascimento}
              />

              <ProfileField
                label="Género"
                name="genero"
                value={form.data.genero}
                onChange={updateField('genero')}
                error={form.errors.genero}
              />

              <ProfileField
                label="Nacionalidade"
                name="nacionalidade"
                value={form.data.nacionalidade}
                onChange={updateField('nacionalidade')}
                error={form.errors.nacionalidade}
              />

              <ProfileField
                label="Naturalidade"
                name="naturalidade"
                value={form.data.naturalidade}
                onChange={updateField('naturalidade')}
                error={form.errors.naturalidade}
              />
            </div>
          </FieldSet>

          <FieldSet>
            <FieldLegend>Filiação e contacto</FieldLegend>

            <div className="grid gap-6 md:grid-cols-2">
              <ProfileField
                label="Filho de (Nome do pai)"
                name="nome_pai"
                value={form.data.nome_pai}
                onChange={updateField('nome_pai')}
                error={form.errors.nome_pai}
              />

              <ProfileField
                label="e de (Nome da mãe)"
                name="nome_mae"
                value={form.data.nome_mae}
                onChange={updateField('nome_mae')}
                error={form.errors.nome_mae}
              />

              <ProfileField
                label="Morada"
                name="morada"
                value={form.data.morada}
                onChange={updateField('morada')}
                error={form.errors.morada}
              />

              <ProfileField
                label="Município"
                name="municipio"
                value={form.data.municipio}
                onChange={updateField('municipio')}
                error={form.errors.municipio}
              />

              <ProfileField
                label="Telefone"
                name="telefone"
                value={form.data.telefone}
                onChange={updateField('telefone')}
                error={form.errors.telefone}
              />
            </div>
          </FieldSet>
        </FieldGroup>
      ) : (
        <FieldSet>
          <FieldLegend>Dados pessoais</FieldLegend>

          <div className="grid gap-6 md:grid-cols-2">
            <ProfileField
              label="Nome"
              name="nome"
              value={form.data.nome}
              onChange={updateField('nome')}
              error={form.errors.nome}
            />

            <ProfileField
              label="Email"
              name="email"
              type="email"
              value={form.data.email}
              onChange={updateField('email')}
              error={form.errors.email}
            />

            <ProfileField
              label="Telefone"
              name="telefone"
              value={form.data.telefone}
              onChange={updateField('telefone')}
              error={form.errors.telefone}
            />
          </div>
        </FieldSet>
      )}

      <div className="flex justify-end gap-3">
        <Button
          type="button"
          variant="outline"
          onClick={() => setIsEditing(false)}
        >
          Cancelar
        </Button>

        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'A salvar as alterações...' : 'Salvar alterações'}
        </Button>
      </div>
    </form>
  );
}
