<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;

class ClienteController extends Controller
{
    public function list(Request $request)
    {
        $clientes = Cliente::where('user_id', $request->user()->id)->get();
        return response()->json($clientes);
    }

    /**
     * Listagem paginada no servidor (busca + ordenacao + paginacao).
     * Endpoint separado do list() para nao afetar quem consome a lista completa.
     *
     * Query params: page, per_page, search, sort, order (asc|desc)
     */
    public function paginated(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'nome');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Apenas colunas permitidas para ordenar (evita SQL injection no orderBy).
        $sortable = ['nome', 'idade', 'documento', 'created_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'nome';
        }

        $query = Cliente::where('user_id', $request->user()->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%");
            });
        }

        $clientes = $query->orderBy($sort, $order)->paginate($perPage);

        return response()->json($clientes);
    }

    public function index(Request $request)
    {
        $clientes = Cliente::where('user_id', $request->user()->id)->get();
        return view('clientes', compact('clientes'));
    }

    public function getById(Request $request, $id)
    {
        $cliente = Cliente::where('user_id', $request->user()->id)->find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        return response()->json($cliente);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'idade' => 'required|integer|min:0|max:150',
            'documento' => 'required|string|unique:clientes',
            'endereco' => 'nullable|string|max:500',
            'telefone' => 'nullable|string|max:20',
            // base64 de uma imagem de ~1MB tem ~1.4M caracteres; teto com folga
            'foto' => 'nullable|string|max:1500000',
        ], [
            'foto.max' => 'A imagem é muito grande. Use uma imagem de até 1 MB.',
        ]);

        $cliente = Cliente::create([
            'user_id' => $request->user()->id,
            'nome' => $request->nome,
            'idade' => $request->idade,
            'documento' => $request->documento,
            'endereco' => $request->endereco,
            'telefone' => $request->telefone,
            'foto' => $request->foto ?: null,
        ]);

        return response()->json($cliente, 201);
    }

    public function update(Request $request, string $id)
    {
        $cliente = Cliente::where('user_id', $request->user()->id)->find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'idade' => 'required|integer|min:0|max:150',
            'documento' => "required|string|unique:clientes,documento,$id",
            'endereco' => 'nullable|string|max:500',
            'telefone' => 'nullable|string|max:20',
            // base64 de uma imagem de ~1MB tem ~1.4M caracteres; teto com folga
            'foto' => 'nullable|string|max:1500000',
        ], [
            'foto.max' => 'A imagem é muito grande. Use uma imagem de até 1 MB.',
        ]);

        $cliente->update([
            'nome' => $request->nome,
            'idade' => $request->idade,
            'documento' => $request->documento,
            'endereco' => $request->endereco,
            'telefone' => $request->telefone,
            'foto' => $request->foto ?: $cliente->foto,
        ]);

        return response()->json($cliente);
    }

    public function destroy(Request $request, string $id)
    {
        $cliente = Cliente::where('user_id', $request->user()->id)->find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $cliente->delete();
        return response()->json(['message' => 'Cliente excluído com sucesso']);
    }
}
