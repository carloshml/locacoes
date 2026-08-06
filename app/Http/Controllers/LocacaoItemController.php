<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocacaoItem;

class LocacaoItemController extends Controller
{
    public function list(Request $request)
    {
        $query = LocacaoItem::with(['item', 'cliente']);

        if ($request->filled('inicio')) {
            $query->where('inicio', '>=', $request->inicio);
        }

        if ($request->filled('fim')) {
            $query->where('fim', '<=', $request->fim);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $locacoes = $query->orderBy('inicio', 'desc')->get();
        return response()->json($locacoes);
    }

    public function getById($id)
    {
        $locacao = LocacaoItem::with(['item', 'cliente'])->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        return response()->json($locacao);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id'    => 'required|exists:items,id',
            'cliente_id' => 'required|exists:clientes,id',
            'location'   => 'required|string|max:255',
            'valor'      => 'nullable|numeric|min:0',
            'inicio'     => 'required|date',
            'fim'        => 'required|date|after:inicio',
            'status'     => 'nullable|in:ativo,finalizado,cancelado',
        ]);

        $locacao = LocacaoItem::create([
            'item_id'    => $request->item_id,
            'cliente_id' => $request->cliente_id,
            'location'   => $request->location,
            'valor'      => $request->valor ?? 0,
            'inicio'     => $request->inicio,
            'fim'        => $request->fim,
            'status'     => $request->status ?? 'ativo',
        ]);

        return response()->json($locacao->load(['item', 'cliente']), 201);
    }

    public function update(Request $request, string $id)
    {
        $locacao = LocacaoItem::find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $request->validate([
            'item_id'    => 'required|exists:items,id',
            'cliente_id' => 'required|exists:clientes,id',
            'location'   => 'required|string|max:255',
            'valor'      => 'nullable|numeric|min:0',
            'inicio'     => 'required|date',
            'fim'        => 'required|date|after:inicio',
            'status'     => 'nullable|in:ativo,finalizado,cancelado',
        ]);

        $locacao->update([
            'item_id'    => $request->item_id,
            'cliente_id' => $request->cliente_id,
            'location'   => $request->location,
            'valor'      => $request->valor ?? $locacao->valor,
            'inicio'     => $request->inicio,
            'fim'        => $request->fim,
            'status'     => $request->status ?? $locacao->status,
        ]);

        return response()->json($locacao->load(['item', 'cliente']));
    }

    public function destroy(string $id)
    {
        $locacao = LocacaoItem::find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $locacao->delete();
        return response()->json(['message' => 'Locação excluída com sucesso']);
    }

    public function updateStatus(Request $request, string $id)
    {
        $locacao = LocacaoItem::find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $request->validate([
            'status' => 'required|in:ativo,finalizado,cancelado',
        ]);

        $dataToUpdate = ['status' => $request->status];

        // Se o status for 'finalizado', atualiza também o campo 'fim' com a data/hora atual
        if ($request->status === 'finalizado') {
            $dataToUpdate['fim'] = now();
        }

        // Se o status for 'ativo' e a locação estava finalizada, podemos opcionalmente redefinir o fim? 
        // (Comentado - depende da regra de negócio)
        // if ($request->status === 'ativo' && $locacao->status === 'finalizado') {
        //     $dataToUpdate['fim'] = null; // ou manter a data anterior
        // }

        $locacao->update($dataToUpdate);

        return response()->json($locacao->load(['item', 'cliente']));
    }
}
