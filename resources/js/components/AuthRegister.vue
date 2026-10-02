<template>
    <div class="w-full max-w-md mx-auto">
        <!-- Tela de conta pendente de ativacao -->
        <div v-if="pendingActivation" class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4">
                <h2 class="text-2xl font-bold text-white text-center">Conta criada</h2>
            </div>
            <div class="p-8 text-center">
                <!-- Ilustracao ( icone de relogio/aguardando) -->
                <div class="mx-auto w-24 h-24 rounded-full bg-amber-100 flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Aguardando ativação</h3>
                <p class="text-gray-600 mb-6">
                    Seu cadastro foi realizado com sucesso, mas sua conta ainda está
                    <span class="font-semibold">inativa</span>.
                    <br>
                    Entre em contato com o administrador para liberar seu acesso ao sistema.
                </p>
                <a href="/login"
                    class="inline-block bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-3 rounded-lg hover:from-green-700 transition font-semibold">
                    Ir para o login
                </a>
            </div>
        </div>

        <!-- Formulario de cadastro -->
        <div v-else class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h2 class="text-2xl font-bold text-white text-center">Criar Conta</h2>
            </div>

            <!-- Form -->
            <div class="p-8">
                <!-- Validation Errors -->
                <div v-if="errors.length" class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                    <p v-for="error in errors" :key="error" class="text-red-600 text-sm">{{ error }}</p>
                </div>

                <form @submit.prevent="handleRegister">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Nome</label>
                        <input type="text" v-model="form.name" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">E-mail</label>
                        <input type="email" v-model="form.email" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Senha</label>
                        <input type="password" v-model="form.password" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Confirmar Senha</label>
                        <input type="password" v-model="form.password_confirmation" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 rounded-lg hover:from-green-700 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="loading" class="inline-block animate-spin mr-2">⚡</span>
                        {{ loading ? 'Cadastrando...' : 'Cadastrar' }}
                    </button>
                </form>

                <!-- Link para login -->
                <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                    <p class="text-gray-600">
                        Já tem uma conta?
                        <a href="/login" class="text-green-600 hover:text-green-700 font-semibold hover:underline transition">
                            Faça login aqui
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AuthRegister',
    data() {
        return {
            form: {
                name: '',
                email: '',
                password: '',
                password_confirmation: ''
            },
            loading: false,
            errors: [],
            pendingActivation: false
        }
    },
    methods: {
        async handleRegister() {
            this.loading = true;
            this.errors = [];

            try {
                const response = await fetch('/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok) {
                    // Conta criada mas inativa: mostra tela "contate o administrador"
                    if (data.pending_activation) {
                        this.pendingActivation = true;
                        return;
                    }
                    // Conta ativa (primeiro usuario/admin): segue para o sistema
                    window.location.href = data.redirect || '/dashboard';
                } else {
                    if (data.errors) {
                        // Laravel validation errors
                        for (const key in data.errors) {
                            this.errors.push(...data.errors[key]);
                        }
                    } else {
                        this.errors.push(data.message || 'Erro ao cadastrar. Verifique os dados informados.');
                    }
                }
            } catch (error) {
                console.error('Register error:', error);
                this.errors.push('Erro ao tentar cadastrar. Tente novamente.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>