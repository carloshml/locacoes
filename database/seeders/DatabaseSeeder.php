<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Cria um usuario administrador inicial usando credenciais vindas do .env,
     * evitando senhas fixas no codigo-fonte (que fica versionado publicamente).
     *
     * Defina no .env antes de rodar `php artisan db:seed`:
     *   SEED_ADMIN_NAME="Administrador"
     *   SEED_ADMIN_EMAIL="voce@seudominio.com"
     *   SEED_ADMIN_PASSWORD="uma-senha-forte"
     */
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        // Sem credenciais definidas no .env, nao cria nada (evita usuarios fracos).
        if (!$email || !$password) {
            $this->command?->warn(
                'Seeder ignorado: defina SEED_ADMIN_EMAIL e SEED_ADMIN_PASSWORD no .env para criar o admin.'
            );
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SEED_ADMIN_NAME', 'Administrador'),
                'password' => $password, // cast 'hashed' no model aplica o hash
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->command?->info("Usuario admin garantido para: {$email}");
    }
}
