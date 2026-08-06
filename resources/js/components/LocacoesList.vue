<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <!-- Filtros Avançados -->
    <div class="p-4 bg-gray-50 border-b space-y-4">

      <!-- Filtros de Período, Cliente e Item -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 " >
        <!-- Filtro por período -->
        <div>
          <label class="block text-gray-600 text-xs font-medium mb-1">Período (Início)</label>
          <VueDatePicker v-model="filtros.inicio" :enable-time-picker="true" :formats="dateFormats"
            model-type="yyyy-MM-dd HH:mm:ss" :start-time="{ hours: 0, minutes: 0 }" auto-apply placeholder="De..."
            :clearable="true" :week-start="0" class="w-full" />
        </div>
        <div>
          <label class="block text-gray-600 text-xs font-medium mb-1">Período (Fim)</label>
          <VueDatePicker v-model="filtros.fim" :enable-time-picker="true" :formats="dateFormats"
            model-type="yyyy-MM-dd HH:mm:ss" :start-time="{ hours: 23, minutes: 59 }" auto-apply placeholder="Até..."
            :clearable="true" :week-start="0" class="w-full" />
        </div>
        <!-- Filtro por cliente -->
        <div>
          <label class="block text-gray-600 text-xs font-medium mb-1">Cliente</label>
          <select v-model="filtros.cliente_id" class="w-full border rounded-lg p-2 text-sm">
            <option value="">Todos</option>
            <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nome }}</option>
          </select>
        </div>
        <!-- Filtro por item -->
        <div>
          <label class="block text-gray-600 text-xs font-medium mb-1">Item</label>
          <select v-model="filtros.item_id" class="w-full border rounded-lg p-2 text-sm">
            <option value="">Todos</option>
            <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select>
        </div>
      </div>
      <!-- Botões de Filtro -->
      <div class="flex items-center gap-4 pb-6">
        <div>
          <select v-model="filtros.status" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">Todos os status</option>
            <option value="ativo">Ativo</option>
            <option value="cobranca">Cobrança</option>
            <option value="finalizado">Finalizado</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>
        <button @click="aplicarFiltros"
          class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition text-sm font-medium">
          Filtrar
        </button>
        <button @click="limparFiltros"
          class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
          Limpar
        </button>
      </div>

      <!-- SEÇÃO DE BUSCA E ITENS POR PÁGINA (ADICIONADA) -->
      <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
        <div class="relative flex-1 max-w-md">
          <input type="text" v-model="search" placeholder="Buscar por item, cliente, localização..."
            class="pl-10 pr-4 py-2 border rounded-lg w-full focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
        <select v-model="itemsPerPage" class="border rounded-lg px-3 py-2">
          <option :value="5">5 por página</option>
          <option :value="10">10 por página</option>
          <option :value="25">25 por página</option>
          <option :value="50">50 por página</option>
        </select>
      </div>



      
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center py-16">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-amber-600 mb-4"></div>
        <p class="text-gray-500">Carregando locações...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border-l-4 border-red-500 p-6 m-6 rounded-lg">
      <p class="text-red-600">{{ error }}</p>
      <button @click="fetchLocacoes" class="mt-2 text-red-700 text-sm font-medium hover:underline">Tentar
        novamente</button>
    </div>

    <!-- Results -->
    <div v-else>
      <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4">
        <div class="flex justify-between items-center text-white">
          <h2 class="text-xl font-bold">Locações de Itens</h2>
          <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium">Total: {{ filteredLocacoes.length
          }}</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b-2 border-gray-200">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Item</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Cliente Responsável</th>
              <th @click="sortBy('location')"
                class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">Localização <span v-if="sortKey === 'location'" class="text-xs">{{
                  sortOrder === 'asc' ? '↑' : '↓' }}</span></div>
              </th>
              <th @click="sortBy('inicio')"
                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">Início <span v-if="sortKey === 'inicio'" class="text-xs">{{
                  sortOrder === 'asc' ? '↑' : '↓' }}</span></div>
              </th>
              <th @click="sortBy('fim')"
                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">Fim <span v-if="sortKey === 'fim'" class="text-xs">{{
                  sortOrder === 'asc' ? '↑' : '↓' }}</span></div>
              </th>
              <th @click="sortBy('status')"
                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">Status <span v-if="sortKey === 'status'" class="text-xs">{{
                  sortOrder === 'asc' ? '↑' : '↓' }}</span></div>
              </th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="loc in paginatedLocacoes" :key="loc.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3 font-medium text-gray-900">{{ loc.item ? loc.item.name : '—' }}</td>
              <td class="px-4 py-3 text-gray-700">{{ loc.cliente ? loc.cliente.nome : '—' }} <span v-if="loc.cliente && loc.cliente.telefone" class="text-gray-500 text-sm">· {{ loc.cliente.telefone }}</span></td>
              <td class="px-4 py-3 text-gray-700">{{ loc.location }}</td>
              <td class="px-4 py-3 text-gray-600 text-sm">{{ formatDate(loc.inicio) }}</td>
              <td class="px-4 py-3 text-gray-600 text-sm">{{ formatDate(loc.fim) }}</td>
              <td class="px-4 py-3">
                <span :class="statusClass(loc.status)" class="px-2 py-1 rounded-full text-xs font-semibold">
                  {{ loc.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <button @click="verLoc(loc.id)" class="text-blue-600 hover:text-blue-800" title="Visualizar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </button>
                  <button @click="editLoc(loc.id)" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                      </path>
                    </svg>
                  </button>
                  <button v-if="loc.status === 'ativo'" @click="finalizarLoc(loc.id)"
                    class="text-green-600 hover:text-green-800" title="Finalizar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </button>
                  <button @click="deleteLoc(loc.id)" class="text-red-600 hover:text-red-800" title="Excluir">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                      </path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="filteredLocacoes.length === 0" class="text-center py-16">
        <p class="text-gray-500 text-lg">Nenhuma locação encontrada.</p>
      </div>

      <div v-if="totalPages > 1" class="px-6 py-4 border-t flex justify-between items-center">
        <span class="text-sm text-gray-600">Página {{ currentPage }} de {{ totalPages }}</span>
        <div class="flex gap-2">
          <button @click="currentPage--" :disabled="currentPage === 1"
            class="px-3 py-1 border rounded-lg disabled:opacity-50 hover:bg-gray-50">Anterior</button>
          <button @click="currentPage++" :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded-lg disabled:opacity-50 hover:bg-gray-50">Próxima</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
  components: { VueDatePicker },
  data() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
    const pad = (n) => String(n).padStart(2, '0');
    const inicioMes = `${firstDay.getFullYear()}-${pad(firstDay.getMonth() + 1)}-01 00:00:00`;
    const fimMes = `${lastDay.getFullYear()}-${pad(lastDay.getMonth() + 1)}-${pad(lastDay.getDate())} 23:59:00`;

    return {
      locacoes: [],
      clientes: [],
      items: [],
      loading: true,
      error: null,
      search: '',
      sortKey: 'location',
      sortOrder: 'asc',
      currentPage: 1,
      itemsPerPage: 10,
      dateFormats: {
        input: 'dd.MM.yyyy - HH:mm'
      },
      filtros: {
        inicio: inicioMes,
        fim: fimMes,
        cliente_id: '',
        item_id: '',
        status: ''
      }
    }
  },
  computed: {
    filteredLocacoes() {
      let filtered = this.locacoes;

      // Busca textual
      if (this.search) {
        const s = this.search.toLowerCase();
        filtered = filtered.filter(loc =>
          (loc.item && loc.item.name && loc.item.name.toLowerCase().includes(s)) ||
          (loc.cliente && loc.cliente.nome && loc.cliente.nome.toLowerCase().includes(s)) ||
          (loc.location && loc.location.toLowerCase().includes(s))
        );
      }

      // Ordenação
      filtered = [...filtered].sort((a, b) => {
        let aVal = a[this.sortKey];
        let bVal = b[this.sortKey];

        if (aVal === null || aVal === undefined) aVal = '';
        if (bVal === null || bVal === undefined) bVal = '';

        if (typeof aVal === 'string') {
          aVal = aVal.toLowerCase();
          bVal = bVal.toLowerCase();
        }

        if (this.sortKey === 'inicio' || this.sortKey === 'fim') {
          aVal = new Date(aVal).getTime();
          bVal = new Date(bVal).getTime();
        }

        if (aVal < bVal) return this.sortOrder === 'asc' ? -1 : 1;
        if (aVal > bVal) return this.sortOrder === 'asc' ? 1 : -1;
        return 0;
      });

      return filtered;
    },
    paginatedLocacoes() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredLocacoes.slice(start, end);
    },
    totalPages() {
      return Math.ceil(this.filteredLocacoes.length / this.itemsPerPage);
    }
  },
  watch: {
    search() {
      this.currentPage = 1;
    }
  },
  mounted() {
    this.fetchSelects();
    this.fetchLocacoes();
  },
  methods: {
    getHeaders() {
      const token = localStorage.getItem('api_token');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      const h = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
      if (token) h['Authorization'] = `Bearer ${token}`;
      if (csrfToken) h['X-CSRF-TOKEN'] = csrfToken;
      return h;
    },
    fetchSelects() {
      const h = this.getHeaders();
      Promise.all([
        fetch('/api/clientes', { headers: h }).then(r => r.json()),
        fetch('/api/items', { headers: h }).then(r => r.json()),
      ]).then(([clientes, items]) => {
        this.clientes = clientes;
        this.items = items;
      }).catch(err => console.error('Erro ao carregar selects:', err));
    },
    fetchLocacoes() {
      this.loading = true;
      this.error = null;
      const params = new URLSearchParams();

      if (this.filtros.inicio) {
        params.append('inicio', this.formatDateForApi(this.filtros.inicio));
      }
      if (this.filtros.fim) {
        params.append('fim', this.formatDateForApi(this.filtros.fim));
      }
      if (this.filtros.cliente_id) {
        params.append('cliente_id', this.filtros.cliente_id);
      }
      if (this.filtros.item_id) {
        params.append('item_id', this.filtros.item_id);
      }
      if (this.filtros.status) {
        params.append('status', this.filtros.status);
      }

      const url = '/api/locacoes' + (params.toString() ? '?' + params.toString() : '');

      fetch(url, { headers: this.getHeaders() })
        .then(res => {
          if (res.status === 401) {
            window.location.href = '/login';
            return;
          }
          if (!res.ok) {
            throw new Error('Erro ao carregar locações');
          }
          return res.json();
        })
        .then(data => {
          if (data) {
            this.locacoes = data;
          }
          this.loading = false;
        })
        .catch(err => {
          console.error('Erro:', err);
          this.error = 'Falha ao carregar locações';
          this.loading = false;
        });
    },
    aplicarFiltros() {
      this.currentPage = 1;
      this.fetchLocacoes();
    },
    limparFiltros() {
      this.filtros = {
        inicio: null,
        fim: null,
        cliente_id: '',
        item_id: '',
        status: ''
      };
      this.search = '';
      this.currentPage = 1;
      this.fetchLocacoes();
    },
    formatDateForApi(date) {
      if (!date) return null;
      const d = date instanceof Date ? date : new Date(date);
      const pad = (n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
    },
    formatDate(d) {
      if (!d) return '—';
      return new Date(d).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    statusClass(s) {
      if (s === 'ativo') return 'bg-green-100 text-green-800';
      if (s === 'cobranca') return 'bg-amber-100 text-amber-800';
      if (s === 'finalizado') return 'bg-gray-100 text-gray-800';
      return 'bg-red-100 text-red-800';
    },
    editLoc(id) {
      window.location.href = `/locacoes/${id}/edit`;
    },
    verLoc(id) {
      window.location.href = `/locacoes/${id}`;
    },
    finalizarLoc(id) {
      if (!confirm('Finalizar esta locação?')) return;

      fetch(`/api/locacoes/${id}/status`, {
        method: 'PATCH',
        headers: this.getHeaders(),
        body: JSON.stringify({ status: 'finalizado' })
      })
        .then(res => {
          if (res.ok) {
            this.fetchLocacoes();
            alert('Locação finalizada!');
          } else {
            alert('Erro ao finalizar locação');
          }
        })
        .catch(err => console.error('Erro:', err));
    },
    deleteLoc(id) {
      if (!confirm('Excluir esta locação?')) return;

      fetch(`/api/locacoes/${id}`, {
        method: 'DELETE',
        headers: this.getHeaders()
      })
        .then(res => {
          if (res.ok) {
            this.fetchLocacoes();
            alert('Locação excluída!');
          }
        })
        .catch(err => console.error('Erro:', err));
    },
    sortBy(key) {
      if (this.sortKey === key) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortKey = key;
        this.sortOrder = 'asc';
      }
    }
  }
}
</script>