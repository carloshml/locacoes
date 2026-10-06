<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Gerencia o MODELO de contrato (.docx) de cada usuario.
 * Cada usuario tem seu proprio modelo, guardado em
 * storage/app/templates/usuarios/{user_id}.docx (disco privado 'local').
 */
class ContratoTemplateController extends Controller
{
    private const DIR = 'templates/usuarios';

    /** Caminho relativo do modelo do usuario autenticado. */
    private function pathFor(int $userId): string
    {
        return self::DIR . '/' . $userId . '.docx';
    }

    /** Informa se o usuario ja possui um modelo e dados basicos. */
    public function status(Request $request)
    {
        $path = $this->pathFor($request->user()->id);
        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            return response()->json(['has_template' => false]);
        }

        return response()->json([
            'has_template' => true,
            'size' => $disk->size($path),
            'updated_at' => $disk->lastModified($path),
        ]);
    }

    /** Faz upload/substitui o modelo .docx do usuario. */
    public function upload(Request $request)
    {
        $request->validate([
            // 10 MB e suficiente para um .docx de contrato com imagens.
            'file' => 'required|file|mimes:docx|max:10240',
        ], [
            'file.mimes' => 'O modelo deve ser um arquivo .docx (Word).',
            'file.max' => 'O arquivo excede o limite de 10 MB.',
            'file.required' => 'Selecione um arquivo .docx.',
        ]);

        $userId = $request->user()->id;

        // Nome fixo por usuario (sem path traversal): {id}.docx
        $request->file('file')->storeAs(self::DIR, $userId . '.docx', 'local');

        return response()->json(['message' => 'Modelo de contrato enviado com sucesso.']);
    }

    /** Baixa o proprio modelo (para conferencia). */
    public function download(Request $request)
    {
        $path = $this->pathFor($request->user()->id);

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Você ainda não enviou um modelo.'], 404);
        }

        return Storage::disk('local')->download($path, 'meu-modelo-contrato.docx');
    }

    /** Remove o modelo do usuario. */
    public function destroy(Request $request)
    {
        $path = $this->pathFor($request->user()->id);

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        return response()->json(['message' => 'Modelo removido.']);
    }
}
