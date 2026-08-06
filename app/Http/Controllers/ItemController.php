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
        ]);

        $item = Item::create([
            'user_id'   => $request->user()->id,
            'name'      => $request->name,
            'valor'     => $request->valor ?? 0,
            'descricao' => $request->descricao,
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
        ]);

        $item->update([
            'name'      => $request->name,
            'valor'     => $request->valor ?? $item->valor,
            'descricao' => $request->descricao,
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
