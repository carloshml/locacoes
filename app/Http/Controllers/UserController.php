<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function list()
    {
        $users = User::with('profile')->get();
        return response()->json($users);
    }

    public function getById($id)
    {
        $user = User::with('profile')->find($id);
        
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }
        
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager,user',
            'phone' => 'nullable|string',
            'position' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'position' => $request->position,
            'is_active' => true,
        ]);

        // Criar perfil
        UserProfile::create([
            'user_id' => $user->id,
            'preferences' => ['theme' => 'light', 'notifications' => true]
        ]);

        // Log de atividade
        $this->logActivity($user->id, 'create', 'User', $user->id, 'Criou um novo usuário');

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,$id",
            'role' => 'required|in:admin,manager,user',
            'phone' => 'nullable|string',
            'position' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $user->toArray();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'phone' => $request->phone,
            'position' => $request->position,
            'is_active' => $request->is_active ?? $user->is_active,
        ]);

        if ($request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        // Log de atividade
        $this->logActivity($user->id, 'update', 'User', $user->id, 'Atualizou um usuário', $oldValues, $user->toArray());

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Não é possível excluir seu próprio usuário'], 403);
        }

        $this->logActivity(auth()->id(), 'delete', 'User', $user->id, 'Removeu um usuário');

        $user->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso']);
    }

    public function updateProfile(Request $request, $id)
    {
        // Apenas o proprio usuario ou um admin/manager pode alterar o perfil.
        if ((int) $id !== $request->user()->id && !$request->user()->isManager()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        $request->validate([
            'bio' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string|size:2',
            'zip_code' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'social_facebook' => 'nullable|url',
            'social_instagram' => 'nullable|url',
            'social_linkedin' => 'nullable|url',
        ]);

        $profile->update($request->only([
            'bio', 'address', 'city', 'state', 'zip_code', 'birth_date',
            'social_facebook', 'social_instagram', 'social_linkedin'
        ]));

        return response()->json($profile);
    }

    public function updateAvatar(Request $request, $id)
    {
        // Apenas o proprio usuario ou um admin/manager pode alterar o avatar.
        if ((int) $id !== $request->user()->id && !$request->user()->isManager()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'avatar' => 'required|string' // base64
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        // Valida que o conteudo base64 e realmente uma imagem suportada.
        if (!preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,#i', $request->avatar)) {
            return response()->json(['message' => 'Formato de imagem inválido.'], 422);
        }

        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->avatar), true);

        if ($image === false) {
            return response()->json(['message' => 'Imagem inválida.'], 422);
        }

        // Limite de ~2MB para o conteudo decodificado.
        if (strlen($image) > 2 * 1024 * 1024) {
            return response()->json(['message' => 'Imagem muito grande (máx. 2MB).'], 422);
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $filename = 'avatars/' . uniqid() . '.png';
        Storage::disk('public')->put($filename, $image);

        $user->update(['avatar' => $filename]);

        return response()->json(['avatar' => $filename]);
    }

    public function getActivities(Request $request, $id)
    {
        // Apenas o proprio usuario ou um admin/manager pode ver as atividades.
        if ((int) $id !== $request->user()->id && !$request->user()->isManager()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $logs = ActivityLog::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    public function getAllActivities()
    {
        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        return response()->json($logs);
    }

    private function logActivity($userId, $action, $modelType, $modelId, $description, $oldValues = null, $newValues = null)
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}