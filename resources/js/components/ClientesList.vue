<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <toast-message ref="toast"></toast-message>
    <div class="p-4 bg-gray-50 border-b">
      <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
        <div class="relative">
          <input type="text" v-model="search" placeholder="Buscar por nome, documento..."
            class="pl-10 pr-4 py-2 border rounded-lg w-64 focus:ring-2 focus:ring-green-500 focus:border-green-500">
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
        <select v-model="itemsPerPage" class="border rounded-lg px-3 py-2">
          <option :value="5">5 por página</option>
          <option :value="10">10 por página</option>
          <option :value="25">25 por página</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-16">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mb-4"></div>
        <p class="text-gray-500">Carregando clientes...</p>
      </div>
    </div>

    <div v-else-if="error" class="bg-red-50 border-l-4 border-red-500 p-6 m-6 rounded-lg">
      <p class="text-red-600">{{ error }}</p>
      <button @click="fetchClientes" class="mt-2 text-red-700 text-sm font-medium hover:underline">Tentar novamente</button>
    </div>

    <div v-else>
      <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
        <div class="flex justify-between items-center text-white">
          <h2 class="text-xl font-bold">Lista de Clientes</h2>
          <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium">Total: {{ filteredClientes.length }}</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b-2 border-gray-200">
            <tr>
              <th @click="sortBy('nome')" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">Nome <span v-if="sortKey === 'nome'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span></div>
              </th>
              <th @click="sortBy('idade')" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">Idade <span v-if="sortKey === 'idade'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span></div>
              </th>
              <th @click="sortBy('documento')" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">Documento <span v-if="sortKey === 'documento'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span></div>
              </th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="cliente in paginatedClientes" :key="cliente.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center">
                    <span class="text-green-700 font-semibold text-sm">{{ cliente.nome.charAt(0).toUpperCase() }}</span>
                  </div>
                  <span class="font-medium text-gray-900">{{ cliente.nome }}</span>
                </div>
              </td>
              <td class="px-6 py-4">{{ cliente.idade }} anos</td>
              <td class="px-6 py-4"><code class="bg-gray-100 px-2 py-1 rounded text-sm text-gray-700">{{ formatDocument(cliente.documento) }}</code></td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button @click="viewCliente(cliente.id)" class="text-blue-600 hover:text-blue-800" title="Visualizar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                  </button>
                  <button @click="editCliente(cliente.id)" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                  </button>
                  <button @click="deleteCliente(cliente.id)" class="text-red-600 hover:text-red-800" title="Excluir">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="filteredClientes.length === 0" class="text-center py-16">
        <p class="text-gray-500 text-lg">Nenhum cliente encontrado.</p>
      </div>

      <div v-if="totalPages > 1" class="px-6 py-4 border-t flex justify-between items-center">
        <span class="text-sm text-gray-600">Página {{ currentPage }} de {{ totalPages }}</span>
        <div class="flex gap-2">
          <button @click="currentPage--" :disabled="currentPage === 1" class="px-3 py-1 border rounded-lg disabled:opacity-50">Anterior</button>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1 border rounded-lg disabled:opacity-50">Próxima</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ToastMessage from './ToastMessage.vue';

export default {
  components: { ToastMessage },
  data() {
    return { clientes: [], loading: true, error: null, search: '', sortKey: 'nome', sortOrder: 'asc', currentPage: 1, itemsPerPage: 10 }
  },
  computed: {
    filteredClientes() {
      let filtered = this.clientes;
      if (this.search) {
        const s = this.search.toLowerCase();
        filtered = filtered.filter(c => c.nome.toLowerCase().includes(s) || c.documento.toLowerCase().includes(s));
      }
      filtered = [...filtered].sort((a, b) => {
        let aV = a[this.sortKey], bV = b[this.sortKey];
        if (this.sortKey === 'idade') { aV = Number(aV); bV = Number(bV); }
        if (aV < bV) return this.sortOrder === 'asc' ? -1 : 1;
        if (aV > bV) return this.sortOrder === 'asc' ? 1 : -1;
        return 0;
      });
      return filtered;
    },
    paginatedClientes() { return this.filteredClientes.slice((this.currentPage-1)*this.itemsPerPage, this.currentPage*this.itemsPerPage); },
    totalPages() { return Math.ceil(this.filteredClientes.length / this.itemsPerPage); }
  },
  watch: { search() { this.currentPage = 1; } },
  mounted() { this.fetchClientes(); },
  methods: {
    fetchClientes() {
      this.loading = true;
      const token = localStorage.getItem('api_token');
      const headers = { 'Accept': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;
      fetch('/api/clientes', { headers })
        .then(res => { if (res.status === 401) { window.location.href = '/login'; } return res.json(); })
        .then(data => { this.clientes = data; this.loading = false; })
        .catch(() => { this.error = 'Falha ao carregar'; this.loading = false; });
    },
    sortBy(key) { if (this.sortKey === key) this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc'; else { this.sortKey = key; this.sortOrder = 'asc'; } },
    formatDocument(doc) { const c = doc.replace(/\D/g,''); if (c.length===11) return c.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,'$1.$2.$3-$4'); if (c.length===14) return c.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5'); return doc; },
    viewCliente(id) { window.location.href = `/clientes/${id}`; },
    editCliente(id) { window.location.href = `/clientes/${id}/edit`; },
    deleteCliente(id) {
      if (!confirm('Excluir este cliente?')) return;
      const token = localStorage.getItem('api_token');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      const headers = {};
      if (token) headers['Authorization'] = `Bearer ${token}`;
      if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
      fetch(`/api/clientes/${id}`, { method: 'DELETE', headers })
        .then(res => { if (res.ok) { this.fetchClientes(); this.$refs.toast.show('Cliente excluído!'); } });
    }
  }
}
</script>
