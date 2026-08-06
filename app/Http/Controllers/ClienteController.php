<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Illuminate\Support\Facades\Storage;

class ClienteController extends Controller
{
    public function list(Request $request)
    {
        $clientes = Cliente::where('user_id', $request->user()->id)->get();
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
            'idade' => 'required|integer',
            'documento' => 'required|string|unique:clientes',
            'endereco' => 'nullable|string|max:500',
            'telefone' => 'nullable|string|max:20',
            'foto' => 'nullable|string',
        ]);

        $path = null;
        if ($request->foto) {
            $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->foto));
            $filename = uniqid() . '.png';
            Storage::disk('public')->put("fotos/$filename", $image);
            $path = "fotos/$filename";
        }

        $cliente = Cliente::create([
            'user_id' => $request->user()->id,
            'nome' => $request->nome,
            'idade' => $request->idade,
            'documento' => $request->documento,
            'endereco' => $request->endereco,
            'telefone' => $request->telefone,
            'foto' => $path,
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
            'idade' => 'required|integer',
            'documento' => "required|string|unique:clientes,documento,$id",
            'endereco' => 'nullable|string|max:500',
            'telefone' => 'nullable|string|max:20',
            'foto' => 'nullable|string',
        ]);

        $path = $cliente->foto;
        if ($request->foto) {
            $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->foto));
            $filename = uniqid() . '.png';
            Storage::disk('public')->put("fotos/$filename", $image);
            $path = "fotos/$filename";
        }

        $cliente->update([
            'nome' => $request->nome,
            'idade' => $request->idade,
            'documento' => $request->documento,
            'endereco' => $request->endereco,
            'telefone' => $request->telefone,
            'foto' => $path,
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
