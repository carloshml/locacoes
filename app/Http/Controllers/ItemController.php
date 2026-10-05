<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class ItemController extends Controller
{
    public function list(Request $request)
    {
        $items = Item::with('locacaoAtiva')
            ->where('user_id', $request->user()->id)
            ->get();
        return response()->json($items);
    }

    /**
     * Listagem paginada no servidor (busca + ordenacao + paginacao).
     * Query params: page, per_page, search, sort, order (asc|desc)
     */
    public function paginated(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'name');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortable = ['name', 'valor', 'created_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'name';
        }

        $query = Item::with('locacaoAtiva')
            ->where('user_id', $request->user()->id);

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->orderBy($sort, $order)->paginate($perPage);

        return response()->json($items);
    }

    public function getById(Request $request, $id)
    {
        $item = Item::with('locacoes.cliente')
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        return response()->json($item);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'valor'     => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string|max:500',
            'foto'      => 'nullable|string',
        ]);

        $item = Item::create([
            'user_id'   => $request->user()->id,
            'name'      => $request->name,
            'valor'     => $request->valor ?? 0,
            'descricao' => $request->descricao,
            'foto'      => $request->foto ?: null,
        ]);

        return response()->json($item, 201);
    }

    public function update(Request $request, string $id)
    {
        $item = Item::where('user_id', $request->user()->id)->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'valor'     => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string|max:500',
            'foto'      => 'nullable|string',
        ]);

        $item->update([
            'name'      => $request->name,
            'valor'     => $request->valor ?? $item->valor,
            'descricao' => $request->descricao,
            'foto'      => $request->foto ?: $item->foto,
        ]);

        return response()->json($item);
    }

    public function destroy(Request $request, string $id)
    {
        $item = Item::where('user_id', $request->user()->id)->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Item excluído com sucesso']);
    }
}
