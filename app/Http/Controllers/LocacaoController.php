<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Locacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Controller da API v2 de locacoes multi-item (cabeca + itens).
 *
 * Responsabilidade unica (SRP): orquestrar a cabeca `locacoes` e os itens
 * `locacao_item` vinculados por `locacao_id`.
 *
 * O endpoint legado (LocacaoItemController -> /api/locacoes) permanece
 * intocado e continua respondendo no formato antigo (um registro por item),
 * garantindo que o APK instalado nao quebre.
 */
class LocacaoController extends Controller
{
    private const STATUSES = ['ativo', 'cobranca', 'finalizado', 'cancelado'];

    private const RELATIONS = ['cliente', 'itens.item'];

    /** GET /api/v2/locacoes/paginated */
    public function paginated(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search  = trim((string) $request->input('search', ''));
        $sort    = $request->input('sort', 'inicio');
        $order   = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortable = ['inicio', 'fim', 'location', 'valor', 'status', 'nome'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'inicio';
        }

        $query = Locacao::with(self::RELATIONS)
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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('item_id')) {
            $query->whereHas('itens', fn ($q) => $q->where('item_id', $request->item_id));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$search}%"))
                  ->orWhereHas('itens.item', fn ($i) => $i->where('name', 'like', "%{$search}%"));
            });
        }

        return response()->json(
            $query->orderBy($sort, $order)->paginate($perPage)
        );
    }

    /** GET /api/v2/locacoes/faturamento */
    public function faturamento(Request $request)
    {
        $mesesPt = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $userId = $request->user()->id;
        $now = now();
        $base = fn () => Locacao::where('user_id', $userId)->where('status', 'finalizado');

        $mesAtual = $base()
            ->whereMonth('inicio', $now->month)
            ->whereYear('inicio', $now->year)
            ->sum('valor');

        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $total = $base()
                ->whereMonth('inicio', $date->month)
                ->whereYear('inicio', $date->year)
                ->sum('valor');
            $meses[] = [
                'mes'   => $date->format('m/Y'),
                'nome'  => $mesesPt[$date->month] . ' ' . $date->year,
                'total' => round((float) $total, 2),
            ];
        }

        $cobrancas = Locacao::with(self::RELATIONS)
            ->where('user_id', $userId)
            ->where('status', 'cobranca')
            ->orderBy('fim', 'asc')
            ->get();

        return response()->json([
            'mes_atual'       => round((float) $mesAtual, 2),
            'meses'           => $meses,
            'cobrancas'       => $cobrancas,
            'total_cobrancas' => round((float) $cobrancas->sum('valor'), 2),
        ]);
    }

    /** GET /api/v2/locacoes/{id} */
    public function show(Request $request, string $id)
    {
        $locacao = Locacao::with(self::RELATIONS)
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        return response()->json($locacao);
    }

    /** POST /api/v2/locacoes */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $locacao = DB::transaction(function () use ($data, $request) {
            $itens = $data['itens'];
            unset($data['itens']);

            $locacao = Locacao::create($this->normalizeHead($data, $request, $itens));

            $this->syncItens($locacao, $itens, $request);

            return $locacao;
        });

        return response()->json($locacao->load(self::RELATIONS), 201);
    }

    /** PUT /api/v2/locacoes/{id} */
    public function update(Request $request, string $id)
    {
        $locacao = Locacao::where('user_id', $request->user()->id)->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $data = $this->validated($request, $locacao);

        DB::transaction(function () use ($locacao, $data, $request) {
            $itens = $data['itens'];
            unset($data['itens']);

            $locacao->update($this->normalizeHead($data, $request, $itens, $locacao));

            $this->syncItens($locacao, $itens, $request, replacing: true);
        });

        return response()->json($locacao->load(self::RELATIONS));
    }

    /** PATCH /api/v2/locacoes/{id}/status */
    public function updateStatus(Request $request, string $id)
    {
        $locacao = Locacao::with(self::RELATIONS)
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $request->validate([
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ]);

        $data = ['status' => $request->status];
        if ($request->status === 'finalizado') {
            $data['fim'] = now();
        }

        $locacao->update($data);

        // Mantem os itens coerentes com o status da cabeca.
        $locacao->itens()->update($data);

        return response()->json($locacao->load(self::RELATIONS));
    }

    /** DELETE /api/v2/locacoes/{id} */
    public function destroy(Request $request, string $id)
    {
        $locacao = Locacao::where('user_id', $request->user()->id)->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        // cascadeOnDelete em locacao_item.locacao_id remove os itens.
        $locacao->delete();

        return response()->json(['message' => 'Locação excluída com sucesso']);
    }

    /**
     * GET /api/v2/locacoes/{id}/contrato
     *
     * Gera o contrato (.docx) a partir do modelo do usuario. Como a locacao
     * agora tem 1..N itens, os mesmos dados sao replicados por linha do
     * template (tabela repetivel). Campos esperados no modelo:
     *   ${item_nome} ${item_quantidade} ${item_valor_unit} ${item_valor_total}
     * Se o modelo nao tiver tabela repetivel, o primeiro item e usado nos
     * campos simples e os demais sao concatenados em `itens_descricao`.
     */
    public function gerarContrato(Request $request, string $id)
    {
        $locacao = Locacao::with(['cliente', 'itens.item'])
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$locacao) {
            return response()->json(['message' => 'Locação não encontrada'], 404);
        }

        $relativePath = 'templates/usuarios/' . $locacao->user_id . '.docx';
        $templatePath = \Illuminate\Support\Facades\Storage::disk('local')->path($relativePath);
        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Você ainda não possui um modelo de contrato. Envie seu modelo em "Meu Perfil" antes de gerar o contrato.',
                'needs_template' => true,
            ], 422);
        }

        $cliente = $locacao->cliente;
        $itens = $locacao->itens;

        $fmtData = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y H:i') : '';
        $fmtMoeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');

        $descricao = $itens->map(function ($linha) use ($fmtMoeda) {
            $nome = $linha->item->name ?? 'Item';
            $qtd = (int) ($linha->quantidade ?? 1);
            return "{$qtd}x {$nome} — {$fmtMoeda($linha->valor)}";
        })->implode('; ');

        // Normaliza os itens para o formato que o template espera.
        $linhas = $itens->map(fn ($l) => [
            'nome'        => $l->item->name ?? 'Item',
            'quantidade'  => (int) ($l->quantidade ?? 1),
            'valor_unit'  => $fmtMoeda($l->valor),
            'valor_total' => $fmtMoeda(
                (float) $l->valor * (int) ($l->quantidade ?? 1)
            ),
        ])->all();

        try {
            $tpl = new TemplateProcessor($templatePath);

            $tpl->setValues([
                'cliente_nome'      => $cliente->nome ?? '',
                'cliente_cpf'       => $cliente->documento ?? '',
                'cliente_telefone'  => $cliente->telefone ?? '',
                'cliente_endereco'  => $cliente->endereco ?? '',
                'data_evento'       => $fmtData($locacao->inicio),
                'retirada'          => $fmtData($locacao->inicio),
                'devolucao'         => $fmtData($locacao->fim),
                'valor_total'       => $fmtMoeda($locacao->valor),
                'itens_descricao'   => $descricao,
                'item_nome'         => $linhas[0]['nome'] ?? '',
                'item_quantidade'   => (string) ($linhas[0]['quantidade'] ?? ''),
                'item_valor_unit'   => $linhas[0]['valor_unit'] ?? '',
                'item_valor_total'  => $linhas[0]['valor_total'] ?? '',
            ]);

            // Tentativa de clonar uma linha repetivel, se existir no modelo.
            try {
                $tpl->cloneRowAndSetValues('item_nome', $linhas ?: [[]]);
            } catch (\Throwable $ignored) {
                // Modelo sem tabela repetivel: mantem apenas os campos simples.
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'contrato') . '.docx';
            $tpl->saveAs($tmpFile);

            return response()->download($tmpFile, 'contrato-locacao-' . $locacao->id . '.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Falha ao gerar o contrato: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function validated(Request $request, ?Locacao $locacao = null): array
    {
        $validator = Validator::make($request->all(), [
            'nome'         => 'nullable|string|max:255',
            'cliente_id'   => 'required|exists:clientes,id',
            'location'     => 'required|string|max:255',
            'inicio'       => 'required|date',
            'fim'          => 'required|date|after:inicio',
            'status'       => 'nullable|in:' . implode(',', self::STATUSES),
            'itens'        => 'required|array|min:1',
            'itens.*.id'   => 'nullable|integer',
            'itens.*.item_id'    => 'required|exists:items,id',
            'itens.*.valor'      => 'nullable|numeric|min:0',
            'itens.*.quantidade' => 'nullable|integer|min:1',
        ], [
            'itens.required' => 'Uma locação precisa ter pelo menos um item.',
            'itens.min'      => 'Uma locação precisa ter pelo menos um item.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Monta os campos da cabeca. `valor` (se nao informado) = soma dos itens
     * (valor unitario * quantidade).
     */
    private function normalizeHead(array $data, Request $request, array $itens, ?Locacao $locacao = null): array
    {
        $valorTotal = collect($itens)->sum(
            fn ($i) => ((float) ($i['valor'] ?? 0)) * ((int) ($i['quantidade'] ?? 1))
        );

        return [
            'user_id'    => $locacao->user_id ?? $request->user()->id,
            'cliente_id' => $data['cliente_id'],
            'nome'       => $data['nome'] ?? $locacao->nome ?? null,
            'location'   => $data['location'],
            'inicio'     => $data['inicio'],
            'fim'        => $data['fim'],
            'status'     => $data['status'] ?? $locacao->status ?? 'ativo',
            'valor'      => $request->filled('valor') ? $request->valor : $valorTotal,
        ];
    }

    /**
     * Sincroniza os itens da locacao.
     *
     * @param bool $replacing true = apaga os itens atuais e recria (PUT).
     */
    private function syncItens(Locacao $locacao, array $itens, Request $request, bool $replacing = false): void
    {
        if ($replacing) {
            $locacao->itens()->delete();
        }

        foreach ($itens as $linha) {
            $valor = (float) ($linha['valor'] ?? 0);
            if ($valor === 0.0) {
                $valor = (float) (Item::where('id', $linha['item_id'])->value('valor') ?? 0);
            }

            $locacao->itens()->create([
                'user_id'    => $locacao->user_id ?? $request->user()->id,
                'item_id'    => $linha['item_id'],
                'cliente_id' => $locacao->cliente_id,
                'location'   => $locacao->location,
                'inicio'     => $locacao->inicio,
                'fim'        => $locacao->fim,
                'status'     => $locacao->status,
                'valor'      => $valor,
                'quantidade' => (int) ($linha['quantidade'] ?? 1),
            ]);
        }
    }
}
