<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocacaoItem;

class LocacaoItemController extends Controller
{
    public function list(Request $request)
    {
        $query = LocacaoItem::with(['item', 'cliente'])
            ->where('user_id', $request->user()->id);

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

    public function faturamento(Request $request)
    {
        $mesesPt = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $userId = $request->user()->id;
        $now = now();

        // Faturamento: apenas locações finalizadas
        $mesAtual = LocacaoItem::where('user_id', $userId)
            ->where('status', 'finalizado')
            ->whereMonth('inicio', $now->month)
            ->whereYear('inicio', $now->year)
            ->sum('valor');

        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $total = LocacaoItem::where('user_id', $userId)
                ->where('status', 'finalizado')
                ->whereMonth('inicio', $date->month)
                ->whereYear('inicio', $date->year)
                ->sum('valor');
            $meses[] = [
                'mes' => $date->format('m/Y'),
                'nome' => $mesesPt[$date->month] . ' ' . $date->year,
                'total' => round((float) $total, 2),
            ];
        }

        // Cobranças pendentes: locações com status 'cobranca'
        $cobrancas = LocacaoItem::with(['item', 'cliente'])
            ->where('user_id', $userId)
            ->where('status', 'cobranca')
            ->orderBy('fim', 'asc')
            ->get();

        $totalCobrancas = $cobrancas->sum('valor');

        return response()->json([
            'mes_atual' => round((float) $mesAtual, 2),
            'meses' => $meses,
            'cobrancas' => $cobrancas,
            'total_cobrancas' => round((float) $totalCobrancas, 2),
        ]);
    }

    public function getById(Request $request, $id)
    {
        $locacao = LocacaoItem::with(['item', 'cliente'])
            ->where('user_id', $request->user()->id)
            ->find($id);

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
            'status'     => 'nullable|in:ativo,cobranca,finalizado,cancelado',
        ]);

        $locacao = LocacaoItem::create([
            'user_id'    => $request->user()->id,
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
        $locacao = LocacaoItem::where('user_id', $request->user()->id)->find($id);

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
            'status'     => 'nullable|in:ativo,cobranca,finalizado,cancelado',
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

    public function destroy(Request $request, string $id)
    {
        $locacao = LocacaoItem::where('user_id', $request->user()->id)->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $locacao->delete();
        return response()->json(['message' => 'Locação excluída com sucesso']);
    }

    public function updateStatus(Request $request, string $id)
    {
        $locacao = LocacaoItem::where('user_id', $request->user()->id)->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $request->validate([
            'status' => 'required|in:ativo,cobranca,finalizado,cancelado',
        ]);

        $dataToUpdate = ['status' => $request->status];

        if ($request->status === 'finalizado') {
            $dataToUpdate['fim'] = now();
        }

        $locacao->update($dataToUpdate);

        return response()->json($locacao->load(['item', 'cliente']));
    }
}
