<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocacaoItem;
use PhpOffice\PhpWord\TemplateProcessor;

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

    /**
     * Listagem paginada no servidor (mesmos filtros do list() + busca + paginacao).
     * Query params: page, per_page, search, sort, order, inicio, fim, cliente_id, item_id, status
     */
    public function paginated(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'inicio');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortable = ['inicio', 'fim', 'location', 'valor', 'status'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'inicio';
        }

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

        // Busca textual por location, nome do cliente ou nome do item.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                  ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$search}%"))
                  ->orWhereHas('item', fn ($i) => $i->where('name', 'like', "%{$search}%"));
            });
        }

        $locacoes = $query->orderBy($sort, $order)->paginate($perPage);

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

    /**
     * Gera o contrato em Word (.docx) preenchido com os dados da locacao,
     * a partir do modelo em storage/app/templates/contrato.docx.
     */
    public function gerarContrato(Request $request, string $id)
    {
        $locacao = LocacaoItem::with(['item', 'cliente'])
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $templatePath = storage_path('app/templates/contrato.docx');
        if (!file_exists($templatePath)) {
            return response()->json(['message' => 'Modelo de contrato não encontrado no servidor.'], 500);
        }

        $cliente = $locacao->cliente;

        $fmtData = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y H:i') : '';
        $fmtMoeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');

        try {
            $tpl = new TemplateProcessor($templatePath);

            // Preenche apenas os campos que o sistema possui. Os demais ficam
            // em branco no modelo para preenchimento manual.
            $tpl->setValues([
                'cliente_nome'      => $cliente->nome ?? '',
                'cliente_cpf'       => $cliente->documento ?? '',
                'cliente_telefone'  => $cliente->telefone ?? '',
                'cliente_endereco'  => $cliente->endereco ?? '',
                'data_evento'       => $fmtData($locacao->inicio),
                'retirada'          => $fmtData($locacao->inicio),
                'devolucao'         => $fmtData($locacao->fim),
                'valor_total'       => $fmtMoeda($locacao->valor),
            ]);

            $tmpFile = tempnam(sys_get_temp_dir(), 'contrato') . '.docx';
            $tpl->saveAs($tmpFile);

            $nomeArquivo = 'contrato-locacao-' . $locacao->id . '.docx';

            return response()->download($tmpFile, $nomeArquivo, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Falha ao gerar o contrato: ' . $e->getMessage(),
            ], 500);
        }
    }
}
