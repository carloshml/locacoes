<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class ItemController extends Controller
{
    public function list()
    {
        $items = Item::with('locacaoAtiva')->get();
        return response()->json($items);
    }

    public function getById($id)
    {
        $item = Item::with('locacoes.cliente')->find($id);

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
        ]);

        $item = Item::create([
            'name'      => $request->name,
            'valor'     => $request->valor ?? 0,
            'descricao' => $request->descricao,
        ]);

        return response()->json($item, 201);
    }

    public function update(Request $request, string $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'valor'     => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string|max:500',
        ]);

        $item->update([
            'name'      => $request->name,
            'valor'     => $request->valor ?? $item->valor,
            'descricao' => $request->descricao,
        ]);

        return response()->json($item);
    }

    public function destroy(string $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Item excluído com sucesso']);
    }
}
