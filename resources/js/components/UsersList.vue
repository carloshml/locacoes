<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <toast-message ref="toast"></toast-message>
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
      <div class="flex justify-between items-center text-white">
        <h2 class="text-xl font-bold">Usuários do Sistema</h2>
        <button @click="createUser" 
          class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Novo Usuário
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="p-4 bg-gray-50 border-b">
      <div class="flex flex-col sm:flex-row gap-4 justify-between">
        <div class="relative flex-1">
          <input type="text" v-model="search" placeholder="Buscar por nome, email ou cargo..."
            class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
        
        <div class="flex gap-2">
          <select v-model="filtroRole" class="border rounded-lg px-3 py-2">
            <option value="">Todos os perfis</option>
            <option value="admin">Administradores</option>
            <option value="manager">Gerentes</option>
            <option value="user">Usuários</option>
          </select>
          
          <select v-model="filtroStatus" class="border rounded-lg px-3 py-2">
            <option value="">Todos os status</option>
            <option value="active">Ativos</option>
            <option value="inactive">Inativos</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-500">Carregando usuários...</p>
      </div>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50 border-b-2 border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Usuário</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Perfil</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Status</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Último acesso</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50 transition">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center overflow-hidden">
                  <img v-if="user.avatar" :src="'/storage/' + user.avatar" class="w-full h-full object-cover">
                  <span v-else class="text-blue-700 font-semibold text-sm">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ user.name }}</div>
                  <div class="text-sm text-gray-500">{{ user.email }}</div>
                  <div class="text-xs text-gray-400">{{ user.position }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4" v-html="user.role_badge"></td>
            <td class="px-6 py-4" v-html="user.status_badge"></td>
            <td class="px-6 py-4 text-sm text-gray-600">
              {{ formatDate(user.last_login_at) }}
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <button @click="viewUser(user.id)" class="text-blue-600 hover:text-blue-800" title="Visualizar">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
                <button @click="editUser(user.id)" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                <button @click="deleteUser(user.id)" v-if="user.id !== currentUserId" class="text-red-600 hover:text-red-800" title="Excluir">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import ToastMessage from './ToastMessage.vue';

export default {
  components: { ToastMessage },
  data() {
    return {
      users: [],
      loading: true,
      search: '',
      filtroRole: '',
      filtroStatus: '',
      currentUserId: null
    }
  },
  computed: {
    filteredUsers() {
      let filtered = this.users;
      
      if (this.search) {
        const searchLower = this.search.toLowerCase();
        filtered = filtered.filter(user =>
          user.name.toLowerCase().includes(searchLower) ||
          user.email.toLowerCase().includes(searchLower) ||
          (user.position && user.position.toLowerCase().includes(searchLower))
        );
      }
      
      if (this.filtroRole) {
        filtered = filtered.filter(user => user.role === this.filtroRole);
      }
      
      if (this.filtroStatus === 'active') {
        filtered = filtered.filter(user => user.is_active);
      } else if (this.filtroStatus === 'inactive') {
        filtered = filtered.filter(user => !user.is_active);
      }
      
      return filtered;
    }
  },
  mounted() {
    this.currentUserId = document.querySelector('meta[name="user-id"]')?.content;
    this.fetchUsers();
  },
  methods: {
    fetchUsers() {
      const token = localStorage.getItem('api_token');
      fetch('/api/usuarios', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
        .then(res => res.json())
        .then(data => {
          this.users = data;
          this.loading = false;
        })
        .catch(err => {
          console.error(err);
          this.loading = false;
        });
    },
    
    createUser() {
      window.location.href = '/usuarios/create';
    },
    
    viewUser(id) {
      window.location.href = `/usuarios/${id}`;
    },
    
    editUser(id) {
      window.location.href = `/usuarios/${id}/edit`;
    },
    
    deleteUser(id) {
      if (confirm('Tem certeza que deseja excluir este usuário?')) {
        const token = localStorage.getItem('api_token');
        fetch(`/api/usuarios/${id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        })
          .then(res => res.json())
          .then(() => {
            this.fetchUsers();
            this.$refs.toast.show('Usuário excluído com sucesso!');
          })
          .catch(err => {
            console.error(err);
            this.$refs.toast.show('Erro ao excluir usuário', 'error');
          });
      }
    },
    
    formatDate(date) {
      if (!date) return 'Nunca';
      return new Date(date).toLocaleDateString('pt-BR');
    }
  }
}
</script>