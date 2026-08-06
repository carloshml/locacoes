<template>
    <div class="mt-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Últimas Atividades</h2>
            <div class="flex gap-2">
                <button 
                    @click="activeTab = 'clientes'"
                    :class="[
                        'px-4 py-2 rounded-lg transition',
                        activeTab === 'clientes' 
                            ? 'bg-green-600 text-white' 
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    ]">
                    Últimos Clientes
                </button>
                <button 
                    @click="activeTab = 'locacoes'"
                    :class="[
                        'px-4 py-2 rounded-lg transition',
                        activeTab === 'locacoes' 
                            ? 'bg-orange-600 text-white' 
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    ]">
                    Últimas Locações
                </button>
                <button 
                    @click="activeTab = 'usuarios'"
                    :class="[
                        'px-4 py-2 rounded-lg transition',
                        activeTab === 'usuarios' 
                            ? 'bg-blue-600 text-white' 
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    ]">
                    Últimos Usuários
                </button>
            </div>
        </div>

        <!-- Lista de Pessoas -->
        <div v-if="activeTab === 'clientes'">
            <ul v-if="clientes.length > 0" class="space-y-3">
                <li v-for="pessoa in clientes" :key="pessoa.id"
                    class="flex justify-between items-center bg-gray-50 p-4 rounded-lg shadow-sm hover:shadow-md transition">
                    <a :href="`/clientes/${pessoa.id}`" class="flex-1">
                        <div>
                            <p class="text-gray-900 font-medium">{{ pessoa.nome }}</p>
                            <p class="text-gray-600 text-sm">{{ pessoa.idade }} anos - {{ formatDocument(pessoa.documento) }}</p>
                        </div>
                    </a>
                    <div class="flex gap-2">
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">Pessoa</span>
                    </div>
                </li>
            </ul>
            <p v-else class="text-gray-500 text-center py-8">Nenhum cliente cadastrado ainda.</p>
        </div>

        <!-- Lista de Locações -->
        <div v-if="activeTab === 'locacoes'">
            <ul v-if="locacoes.length > 0" class="space-y-3">
                <li v-for="locacao in locacoes" :key="locacao.id"
                    class="flex justify-between items-center bg-gray-50 p-4 rounded-lg shadow-sm hover:shadow-md transition">
                    <a :href="`/locacoes/${locacao.id}`" class="flex-1">
                        <div>
                            <p class="text-gray-900 font-medium">
                                {{ locacao.item ? locacao.item.name : 'Item removido' }}
                            </p>
                            <p class="text-gray-600 text-sm">
                                Cliente: {{ locacao.cliente ? locacao.cliente.nome : 'Cliente removido' }}
                            </p>
                            <p class="text-gray-500 text-xs">
                                {{ formatDate(locacao.inicio) }} → {{ formatDate(locacao.fim) }} · {{ locacao.location }}
                            </p>
                        </div>
                    </a>
                    <div class="flex gap-2">
                        <span :class="statusClass(locacao.status)" class="px-2 py-1 text-xs rounded-full">
                            {{ locacao.status }}
                        </span>
                    </div>
                </li>
            </ul>
            <p v-else class="text-gray-500 text-center py-8">Nenhuma locação registrada ainda.</p>
        </div>

        <!-- Lista de Usuários -->
        <div v-if="activeTab === 'usuarios'">
            <ul v-if="usuarios.length > 0" class="space-y-3">
                <li v-for="usuario in usuarios" :key="usuario.id"
                    class="flex justify-between items-center bg-gray-50 p-4 rounded-lg shadow-sm hover:shadow-md transition">
                    <a :href="`/usuarios/${usuario.id}`" class="flex-1">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center overflow-hidden">
                                <img v-if="usuario.avatar" :src="'/storage/' + usuario.avatar" class="w-full h-full object-cover">
                                <span v-else class="text-blue-700 font-semibold">
                                    {{ usuario.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div>
                                <p class="text-gray-900 font-medium">{{ usuario.name }}</p>
                                <p class="text-gray-600 text-sm">{{ usuario.email }}</p>
                                <p class="text-gray-500 text-xs">{{ usuario.position || 'Sem cargo definido' }}</p>
                            </div>
                        </div>
                    </a>
                    <div class="flex gap-2">
                        <span v-html="usuario.role_badge" class="text-xs"></span>
                    </div>
                </li>
            </ul>
            <p v-else class="text-gray-500 text-center py-8">Nenhum usuário cadastrado ainda.</p>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return { 
            clientes: [],
            usuarios: [],
            locacoes: [],
            activeTab: 'clientes'
        }
    },
    mounted() {
        this.fetchUltimosClientes();
        this.fetchUltimosUsuarios();
        this.fetchUltimasLocacoes();
    },
    methods: {
        fetchUltimosClientes() {
            const token = localStorage.getItem('api_token');
            const headers = {};

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            fetch('/api/clientes/', { headers })
                .then(res => {
                    if (res.status === 401) {
                        localStorage.removeItem('api_token');
                        window.location.href = '/login';
                        throw new Error('Não autorizado');
                    }
                    return res.json();
                })
                .then(data => {
                    this.clientes = data.slice(-5).reverse();
                })
                .catch(err => console.error(err));
        },

        fetchUltimosUsuarios() {
            const token = localStorage.getItem('api_token');
            const headers = {};

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            fetch('/api/usuarios', { headers })
                .then(res => {
                    if (res.status === 401) {
                        localStorage.removeItem('api_token');
                        window.location.href = '/login';
                        throw new Error('Não autorizado');
                    }
                    return res.json();
                })
                .then(data => {
                    this.usuarios = data.slice(-5).reverse();
                })
                .catch(err => console.error(err));
        },

        fetchUltimasLocacoes() {
            const token = localStorage.getItem('api_token');
            const headers = {};

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            fetch('/api/locacoes', { headers })
                .then(res => {
                    if (res.status === 401) {
                        localStorage.removeItem('api_token');
                        window.location.href = '/login';
                        throw new Error('Não autorizado');
                    }
                    return res.json();
                })
                .then(data => {
                    // A API já retorna ordenado por inicio desc
                    this.locacoes = data.slice(0, 5);
                })
                .catch(err => console.error(err));
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        statusClass(status) {
            switch (status) {
                case 'ativo': return 'bg-blue-100 text-blue-700';
                case 'cobranca': return 'bg-amber-100 text-amber-700';
                case 'finalizado': return 'bg-green-100 text-green-700';
                case 'cancelado': return 'bg-red-100 text-red-700';
                default: return 'bg-gray-100 text-gray-700';
            }
        },

        formatDocument(documento) {
            if (!documento) return '';
            const clean = documento.replace(/\D/g, '');
            if (clean.length === 11) {
                return clean.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (clean.length === 14) {
                return clean.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }
            return documento;
        }
    }
}
</script>