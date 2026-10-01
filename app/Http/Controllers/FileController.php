<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /** Pasta (no disco 'local') onde os arquivos sao guardados. */
    private const DIR = 'files';

    /** Tamanho maximo por arquivo em KB (100 MB). */
    private const MAX_SIZE_KB = 102400;

    /** Extensoes bloqueadas por serem executaveis/perigosas no servidor. */
    private const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'pht',
        'sh', 'bash', 'bin', 'exe', 'com', 'cgi', 'pl', 'py',
        'htaccess', 'htpasswd', 'asp', 'aspx', 'jsp',
    ];

    /**
     * Lista os arquivos lendo diretamente do disco (nao do banco).
     */
    public function index()
    {
        $disk = Storage::disk('local');

        if (!$disk->exists(self::DIR)) {
            return response()->json([]);
        }

        $files = collect($disk->files(self::DIR))->map(function ($path) use ($disk) {
            $name = basename($path);
            return [
                'id' => $this->encodeId($name),      // identificador seguro p/ a API
                'name' => $name,
                'size' => $disk->size($path),
                'size_human' => $this->humanSize($disk->size($path)),
                'last_modified' => $disk->lastModified($path),
            ];
        })->sortByDesc('last_modified')->values();

        return response()->json($files);
    }

    /**
     * Salva um arquivo enviado no disco privado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:' . self::MAX_SIZE_KB,
        ], [
            'file.max' => 'O arquivo excede o limite de 100 MB.',
            'file.required' => 'Nenhum arquivo foi enviado.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return response()->json([
                'message' => 'Tipo de arquivo não permitido por motivos de segurança.',
            ], 422);
        }

        // Mantem o nome original, mas sanitizado, com sufixo aleatorio para
        // evitar colisao e caracteres perigosos (path traversal).
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = Str::slug($base) ?: 'arquivo';
        $suffix = Str::lower(Str::random(6));
        $storedName = $safeBase . '-' . $suffix . ($extension ? '.' . $extension : '');

        $file->storeAs(self::DIR, $storedName, 'local');

        // Log: quem criou o arquivo (usa a tabela activity_logs existente).
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'model_type' => 'File',
            'model_id' => null,
            'description' => 'Enviou o arquivo: ' . $storedName,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'id' => $this->encodeId($storedName),
            'name' => $storedName,
            'size' => $file->getSize(),
            'size_human' => $this->humanSize($file->getSize()),
        ], 201);
    }

    /**
     * Baixa um arquivo (passa pelo controller, com checagem de acesso).
     */
    public function download(Request $request, string $id)
    {
        $path = $this->resolvePath($id);

        if (!$path) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        return Storage::disk('local')->download($path);
    }

    /**
     * Remove um arquivo do disco.
     */
    public function destroy(Request $request, string $id)
    {
        $path = $this->resolvePath($id);

        if (!$path) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        Storage::disk('local')->delete($path);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'model_type' => 'File',
            'model_id' => null,
            'description' => 'Removeu o arquivo: ' . basename($path),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Arquivo excluído com sucesso.']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /** Codifica o nome do arquivo como id de URL (base64url). */
    private function encodeId(string $name): string
    {
        return rtrim(strtr(base64_encode($name), '+/', '-_'), '=');
    }

    /**
     * Converte o id de volta para um caminho valido no disco, impedindo
     * path traversal: so aceita um nome simples dentro da pasta DIR.
     */
    private function resolvePath(string $id): ?string
    {
        $name = base64_decode(strtr($id, '-_', '+/'), true);

        if ($name === false || $name === '' || basename($name) !== $name) {
            return null; // id invalido ou tentativa de traversal
        }

        $path = self::DIR . '/' . $name;

        return Storage::disk('local')->exists($path) ? $path : null;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
