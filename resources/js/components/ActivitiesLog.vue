<!-- resources/js/components/ActivitiesLog.vue -->
<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
      <h2 class="text-2xl font-bold text-white">Log de Atividades</h2>
    </div>

    <!-- Filters -->
    <div class="p-4 bg-gray-50 border-b">
      <div class="flex flex-col sm:flex-row gap-4 justify-between">
        <div class="relative flex-1">
          <input type="text" v-model="search" placeholder="Buscar por usuário, ação ou descrição..."
            class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-gray-500">
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
        
        <div class="flex gap-2">
          <select v-model="filtroAcao" class="border rounded-lg px-3 py-2">
            <option value="">Todas as ações</option>
            <option value="create">Criação</option>
            <option value="update">Atualização</option>
            <option value="delete">Exclusão</option>
            <option value="login">Login</option>
            <option value="logout">Logout</option>
          </select>
          
          <input type="date" v-model="filtroData" class="border rounded-lg px-3 py-2">
          
          <button @click="limparFiltros" class="bg-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            Limpar
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-600 mb-4"></div>
        <p class="text-gray-500">Carregando atividades...</p>
      </div>
    </div>

    <div v-else>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b-2 border-gray-200">
            <tr>
              <th @click="sortBy('created_at')" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">
                  Data/Hora
                  <span v-if="sortKey === 'created_at'" class="text-xs">
                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
              <th @click="sortBy('user_name')" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">
                  Usuário
                  <span v-if="sortKey === 'user_name'" class="text-xs">
                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
              <th @click="sortBy('action')" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">
                  Ação
                  <span v-if="sortKey === 'action'" class="text-xs">
                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Descrição</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-gray-50 transition">
              <td class="px-6 py-4 text-sm text-gray-600">
                <div class="whitespace-nowrap">
                  {{ formatDateTime(log.created_at) }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                    <img v-if="log.user?.avatar" :src="log.user.avatar" class="w-full h-full object-cover">
                    <span v-else class="text-xs font-semibold">
                      {{ log.user?.name?.charAt(0) || 'S' }}
                    </span>
                  </div>
                  <span class="text-sm font-medium">{{ log.user?.name || 'Sistema' }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getActionClass(log.action)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ getActionLabel(log.action) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-700 max-w-md">
                  {{ log.description }}
                  <button v-if="log.old_values || log.new_values" @click="showDetails(log)" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                    Ver detalhes
                  </button>
                </div>
              </td>
              <td class="px-6 py-4">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ log.ip_address || '-' }}</code>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="filteredLogs.length > 0" class="px-6 py-4 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-sm text-gray-600">
          Mostrando {{ startIndex + 1 }} a {{ endIndex }} de {{ filteredLogs.length }} registros
        </div>

        <div class="flex gap-2">
          <button @click="currentPage--" :disabled="currentPage === 1"
            class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
            Anterior
          </button>

          <div class="flex gap-1">
            <button v-for="page in visiblePages" :key="page" @click="currentPage = page" :class="[
              'px-3 py-1 border rounded-lg transition',
              currentPage === page
                ? 'bg-gray-600 text-white border-gray-600'
                : 'hover:bg-gray-50'
            ]">
              {{ page }}
            </button>
          </div>

          <button @click="currentPage++" :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
            Próxima
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredLogs.length === 0 && !loading" class="text-center py-16">
        <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="text-gray-500 text-lg">Nenhuma atividade encontrada.</p>
      </div>
    </div>

    <!-- Modal de Detalhes -->
    <div v-if="selectedLog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeModal">
      <div class="bg-white rounded-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto" @click.stop>
        <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4 flex justify-between items-center">
          <h3 class="text-xl font-bold text-white">Detalhes da Atividade</h3>
          <button @click="closeModal" class="text-white hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="p-6">
          <div class="mb-4">
            <label class="text-gray-500 text-sm">Descrição</label>
            <p class="text-gray-900">{{ selectedLog.description }}</p>
          </div>
          
          <div v-if="selectedLog.old_values" class="mb-4">
            <label class="text-gray-500 text-sm">Valores Anteriores</label>
            <pre class="bg-gray-100 p-3 rounded-lg text-sm overflow-x-auto">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
          </div>
          
          <div v-if="selectedLog.new_values" class="mb-4">
            <label class="text-gray-500 text-sm">Novos Valores</label>
            <pre class="bg-gray-100 p-3 rounded-lg text-sm overflow-x-auto">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-gray-500 text-sm">Usuário</label>
              <p class="text-gray-900">{{ selectedLog.user?.name || 'Sistema' }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">IP Address</label>
              <p class="text-gray-900">{{ selectedLog.ip_address || '-' }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">Data/Hora</label>
              <p class="text-gray-900">{{ formatDateTime(selectedLog.created_at) }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">Modelo</label>
              <p class="text-gray-900">{{ selectedLog.model_type || '-' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      logs: [],
      loading: true,
      search: '',
      filtroAcao: '',
      filtroData: '',
      sortKey: 'created_at',
      sortOrder: 'desc',
      currentPage: 1,
      itemsPerPage: 15,
      selectedLog: null
    }
  },
  computed: {
    filteredLogs() {
      let filtered = [...this.logs];
      
      // Filtro por busca
      if (this.search) {
        const searchLower = this.search.toLowerCase();
        filtered = filtered.filter(log =>
          (log.user?.name || '').toLowerCase().includes(searchLower) ||
          log.description.toLowerCase().includes(searchLower) ||
          log.action.toLowerCase().includes(searchLower)
        );
      }
      
      // Filtro por ação
      if (this.filtroAcao) {
        filtered = filtered.filter(log => log.action === this.filtroAcao);
      }
      
      // Filtro por data
      if (this.filtroData) {
        const dataFiltro = new Date(this.filtroData).toDateString();
        filtered = filtered.filter(log => 
          new Date(log.created_at).toDateString() === dataFiltro
        );
      }
      
      // Ordenação
      filtered.sort((a, b) => {
        let aVal = a[this.sortKey];
        let bVal = b[this.sortKey];
        
        if (this.sortKey === 'user_name') {
          aVal = a.user?.name || '';
          bVal = b.user?.name || '';
        }
        
        if (aVal < bVal) return this.sortOrder === 'asc' ? -1 : 1;
        if (aVal > bVal) return this.sortOrder === 'asc' ? 1 : -1;
        return 0;
      });
      
      return filtered;
    },
    
    paginatedLogs() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredLogs.slice(start, end);
    },
    
    totalPages() {
      return Math.ceil(this.filteredLogs.length / this.itemsPerPage);
    },
    
    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },
    
    endIndex() {
      const end = this.currentPage * this.itemsPerPage;
      return end > this.filteredLogs.length ? this.filteredLogs.length : end;
    },
    
    visiblePages() {
      const delta = 2;
      const range = [];
      const rangeWithDots = [];
      let l;
      
      for (let i = 1; i <= this.totalPages; i++) {
        if (i === 1 || i === this.totalPages || (i >= this.currentPage - delta && i <= this.currentPage + delta)) {
          range.push(i);
        }
      }
      
      range.forEach((i) => {
        if (l) {
          if (i - l === 2) {
            rangeWithDots.push(l + 1);
          } else if (i - l !== 1) {
            rangeWithDots.push('...');
          }
        }
        rangeWithDots.push(i);
        l = i;
      });
      
      return rangeWithDots;
    }
  },
  watch: {
    search() {
      this.currentPage = 1;
    },
    filtroAcao() {
      this.currentPage = 1;
    },
    filtroData() {
      this.currentPage = 1;
    }
  },
  mounted() {
    this.fetchActivities();
  },
  methods: {
    fetchActivities() {
      this.loading = true;
      const token = localStorage.getItem('api_token');
      
      fetch('/api/activities', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
        .then(res => {
          if (res.status === 401) {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
            throw new Error('Não autorizado');
          }
          return res.json();
        })
        .then(data => {
          this.logs = data;
          this.loading = false;
        })
        .catch(err => {
          console.error('Error fetching activities:', err);
          this.loading = false;
        });
    },
    
    sortBy(key) {
      if (this.sortKey === key) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortKey = key;
        this.sortOrder = 'asc';
      }
    },
    
    getActionLabel(action) {
      const labels = {
        'create': 'Criação',
        'update': 'Atualização',
        'delete': 'Exclusão',
        'login': 'Login',
        'logout': 'Logout'
      };
      return labels[action] || action;
    },
    
    getActionClass(action) {
      const classes = {
        'create': 'bg-green-100 text-green-800',
        'update': 'bg-blue-100 text-blue-800',
        'delete': 'bg-red-100 text-red-800',
        'login': 'bg-purple-100 text-purple-800',
        'logout': 'bg-gray-100 text-gray-800'
      };
      return classes[action] || 'bg-gray-100 text-gray-800';
    },
    
    formatDateTime(date) {
      if (!date) return '-';
      const d = new Date(date);
      return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR');
    },
    
    limparFiltros() {
      this.search = '';
      this.filtroAcao = '';
      this.filtroData = '';
      this.currentPage = 1;
    },
    
    showDetails(log) {
      this.selectedLog = log;
    },
    
    closeModal() {
      this.selectedLog = null;
    }
  }
}
</script>