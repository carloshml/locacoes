<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <toast-message ref="toast"></toast-message>
    <!-- Filtros e Busca -->
    <div class="p-4 bg-gray-50 border-b">
      <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
        <div class="relative">
          <input type="text" v-model="search" placeholder="Buscar por nome, documento..."
            class="pl-10 pr-4 py-2 border rounded-lg w-64 focus:ring-2 focus:ring-green-500 focus:border-green-500">
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>

        <div class="flex gap-2">
          <select v-model="itemsPerPage" class="border rounded-lg px-3 py-2">
            <option :value="5">5 por página</option>
            <option :value="10">10 por página</option>
            <option :value="25">25 por página</option>
            <option :value="50">50 por página</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-16">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mb-4"></div>
        <p class="text-gray-500">Carregando pessoas...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border-l-4 border-red-500 p-6 m-6 rounded-lg">
      <div class="flex items-center gap-3">
        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
          <h3 class="text-red-800 font-semibold">Erro ao carregar dados</h3>
          <p class="text-red-600 text-sm">{{ error }}</p>
          <button @click="fetchPessoas" class="mt-2 text-red-700 text-sm font-medium hover:underline">
            Tentar novamente
          </button>
        </div>
      </div>
    </div>

    <!-- Success State -->
    <div v-else>
      <!-- Table Header with Stats -->
      <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
        <div class="flex justify-between items-center text-white">
          <h2 class="text-xl font-bold">Lista de Pessoas</h2>
          <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium">
            Total: {{ filteredPessoas.length }}
          </span>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b-2 border-gray-200">
            <tr>
              <th @click="sortBy('nome')"
                class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">
                  Nome
                  <span v-if="sortKey === 'nome'" class="text-xs">
                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
              <th @click="sortBy('idade')"
                class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">
                  Idade
                  <span v-if="sortKey === 'idade'" class="text-xs">
                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
              <th @click="sortBy('documento')"
                class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-1">
                  Documento
                  <span v-if="sortKey === 'documento'" class="text-xs">
                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="pessoa in paginatedPessoas" :key="pessoa.id"
              class="hover:bg-gray-50 transition-colors duration-150">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center">
                    <span class="text-green-700 font-semibold text-sm">
                      {{ pessoa.nome.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <span class="font-medium text-gray-900">{{ pessoa.nome }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  {{ pessoa.idade }} anos
                </span>
              </td>
              <td class="px-6 py-4">
                <code class="bg-gray-100 px-2 py-1 rounded text-sm text-gray-700">
                  {{ formatDocument(pessoa.documento) }}
                </code>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button @click="viewPessoa(pessoa.id)" class="text-blue-600 hover:text-blue-800 transition"
                    title="Visualizar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                      </path>
                    </svg>
                  </button>
                  <button @click="editPessoa(pessoa.id)" class="text-yellow-600 hover:text-yellow-800 transition"
                    title="Editar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                      </path>
                    </svg>
                  </button>
                  <button @click="deletePessoa(pessoa.id)" class="text-red-600 hover:text-red-800 transition"
                    title="Excluir">
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

      <!-- Pagination -->
      <div class="px-6 py-4 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-sm text-gray-600">
          Mostrando {{ startIndex + 1 }} a {{ endIndex }} de {{ filteredPessoas.length }} registros
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
                ? 'bg-green-600 text-white border-green-600'
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
      <div v-if="filteredPessoas.length === 0" class="text-center py-16">
        <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
          </path>
        </svg>
        <p class="text-gray-500 text-lg">Nenhuma pessoa encontrada.</p>
      </div>
    </div>
  </div>
</template>

<script>
import ToastMessage from './ToastMessage.vue';

export default {
  components: { ToastMessage },
  data() {
    return {
      pessoas: [],
      loading: true,
      error: null,
      search: '',
      sortKey: 'nome',
      sortOrder: 'asc',
      currentPage: 1,
      itemsPerPage: 5
    }
  },
  computed: {
    // Filtrar pessoas baseado na busca
    filteredPessoas() {
      let filtered = this.pessoas;

      if (this.search) {
        const searchLower = this.search.toLowerCase();
        filtered = filtered.filter(pessoa =>
          pessoa.nome.toLowerCase().includes(searchLower) ||
          pessoa.documento.toLowerCase().includes(searchLower) ||
          pessoa.idade.toString().includes(searchLower)
        );
      }

      // Ordenar
      filtered = [...filtered].sort((a, b) => {
        let aVal = a[this.sortKey];
        let bVal = b[this.sortKey];

        if (this.sortKey === 'idade') {
          aVal = Number(aVal);
          bVal = Number(bVal);
        }

        if (aVal < bVal) return this.sortOrder === 'asc' ? -1 : 1;
        if (aVal > bVal) return this.sortOrder === 'asc' ? 1 : -1;
        return 0;
      });

      return filtered;
    },

    // Paginação
    paginatedPessoas() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredPessoas.slice(start, end);
    },

    totalPages() {
      return Math.ceil(this.filteredPessoas.length / this.itemsPerPage);
    },

    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },

    endIndex() {
      const end = this.currentPage * this.itemsPerPage;
      return end > this.filteredPessoas.length ? this.filteredPessoas.length : end;
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
    itemsPerPage() {
      this.currentPage = 1;
    }
  },
  mounted() {
    this.fetchPessoas();
  },
  methods: {
    fetchPessoas() {
      this.loading = true;
      this.error = null;

      const token = localStorage.getItem('api_token');
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      fetch('/api/pessoas', { headers })
        .then(res => {
          if (res.status === 401) {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
            throw new Error('Não autorizado');
          }
          if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
          }
          return res.json();
        })
        .then(data => {
          this.pessoas = data;
          this.loading = false;
        })
        .catch(err => {
          console.error('Error fetching pessoas:', err);
          this.error = err.message || 'Falha ao carregar os dados';
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

    formatDocument(documento) {
      const clean = documento.replace(/\D/g, '');
      if (clean.length === 11) {
        return clean.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
      } else if (clean.length === 14) {
        return clean.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
      }
      return documento;
    },

    viewPessoa(id) {
      window.location.href = `/pessoas/${id}`;
    },

    editPessoa(id) {
      window.location.href = `/pessoas/${id}/edit`;
    },

    deletePessoa(id) {
      if (confirm('Tem certeza que deseja excluir esta pessoa?')) {
        const token = localStorage.getItem('api_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const headers = {};

        if (token) {
          headers['Authorization'] = `Bearer ${token}`;
        }

        if (csrfToken) {
          headers['X-CSRF-TOKEN'] = csrfToken;
        }

        fetch(`/api/pessoas/${id}`, {
          method: 'DELETE',
          headers
        })
          .then(res => {
            if (res.status === 401) {
              localStorage.removeItem('api_token');
              window.location.href = '/login';
              throw new Error('Não autorizado');
            }
            if (res.ok) {
              this.fetchPessoas();
              this.$refs.toast.show('Pessoa excluída com sucesso!');
            } else {
              throw new Error('Erro ao excluir');
            }
          })
          .catch(err => {
            console.error('Error deleting pessoa:', err);
            this.$refs.toast.show('Erro ao excluir pessoa. Tente novamente.', 'error');
          });
      }
    }
  }
}
</script>

<style scoped>
/* Optional: Add smooth transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>