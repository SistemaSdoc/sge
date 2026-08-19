<?php

namespace App\Http\Controllers\Central;

use App\Ai\Agents\ResumoDirector;
use App\Ai\Agents\Teste;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Imports\UsersImport;
use App\Models\Central\Instituicao;
use App\Models\Central\Role;
use App\Models\Central\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function importarForm(Request $request)
    {
        return view('importar');
    }

    public function importar(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new UsersImport;

        Excel::import($import, $request->file('file'), null, \Maatwebsite\Excel\Excel::XLSX);

        return redirect()->back();
    }

    public function index()
    {
        // Carrega usuários com instituição e roles (para mostrar na listagem)
        $users = User::paginate(10);

        $reponses = (new Teste)->prompt('Analisa estes dados e de a tua opinião sobre eles. Stelvio é full stack developer e usa laravel + nextjs para desenvolvimento web.');

        $resumo = (new ResumoDirector)->prompt('dsfdsfsfsf');

        return response()->json($users);
    }

    public function create()
    {
        $instituicoes = Instituicao::all();
        $roles = Role::all();

        return response()->json(
            [
                'instituicoes' => $instituicoes,
                'roles' => $roles,
            ],
            status: 202
        );

    }

    public function store(UserRequest $request)
    {
        $request->validated();

        $user = User::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'instituicao_id' => $request->instituicao_id,
        ]);

        // Atribui roles
        if ($request->roles) {
            $user->roles()->sync($request->roles);
        }

        return response()->json($user, status: 201);
    }

    public function edit(User $user)
    {
        $instituicoes = Instituicao::all();
        $roles = Role::all();

        return response()->json(
            [
                'user' => $user,
                'instituicoes' => $instituicoes,
                'roles' => $roles,
            ],
            status: 200
        );
    }

    public function update(UserRequest $request, User $user)
    {
        $request->validated();
        $user->update([
            'nome' => $request->nome,
            'email' => $request->email,
            'instituicao_id' => $request->instituicao_id,
        ]);

        if ($request->password) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // Atualiza roles
        $user->roles()->sync($request->roles ?? []);

        return response()->json($user, status: 200);
    }

    public function destroy(User $user)
    {
        $user->roles()->detach();
        $user->delete();

        return response()->json(
            ['message' => 'Usuário removido com sucesso!'],
            status: 200
        );
    }
}
