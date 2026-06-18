import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export function TurmaAlunos({ data }) {
  const alunos = data?.alunos ?? [];

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Nome</TableHead>
          <TableHead>Matrícula</TableHead>
          <TableHead>Email</TableHead>
          <TableHead>Telefone</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {alunos.map((aluno) => (
          <TableRow key={aluno.id}>
            <TableCell>{aluno.nome}</TableCell>
            <TableCell>{aluno.matricula}</TableCell>
            <TableCell>{aluno.email}</TableCell>
            <TableCell>{aluno.telefone}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}