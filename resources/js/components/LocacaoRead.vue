<template>
  <div v-if="loading" class="text-center py-8"><p class="text-gray-500">Carregando...</p></div>

  <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
    <p class="text-red-600 font-medium">{{ error }}</p>
    <a href="/locacoes" class="inline-block mt-4 text-amber-600 hover:text-amber-800 font-medium">← Voltar</a>
  </div>

  <div v-else>
    <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4 rounded-xl">
      <div class="text-white font-bold text-lg">Detalhes da Locação</div>
    </div>

    <div class="w-full bg-white shadow-xl rounded-xl p-8 mt-4 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-500 text-sm">Item</label>
          <p class="text-gray-900 font-medium text-lg">{{ locacao.item ? locacao.item.name : '—' }}</p>
        </div>
        <div>
          <label class="block text-gray-500 text-sm">Valor</label>
          <p class="text-gray-900 font-bold text-lg">R$ {{ formatCurrency(locacao.valor) }}</p>
        </div>
        <div>
          <label class="block text-gray-500 text-sm">Cliente Responsável</label>
          <p class="text-gray-900 font-medium">{{ locacao.cliente ? locacao.cliente.nome : '—' }}</p>
          <p v-if="locacao.cliente && locacao.cliente.telefone" class="text-gray-600 text-sm">
            📞 {{ locacao.cliente.telefone }}
          </p>
          <p v-if="locacao.cliente && locacao.cliente.endereco" class="text-gray-600 text-sm">
            📍 {{ locacao.cliente.endereco }}
          </p>
        </div>
        <div>
          <label class="block text-gray-500 text-sm">Localização</label>
          <p class="text-gray-900 font-medium">{{ locacao.location || '—' }}</p>
        </div>
        <div>
          <label class="block text-gray-500 text-sm">Status</label>
          <span :class="statusClass" class="px-3 py-1 rounded-full text-xs font-semibold">{{ statusLabel }}</span>
        </div>
        <div></div>
        <div>
          <label class="block text-gray-500 text-sm">Início</label>
          <p class="text-gray-900">{{ formatDate(locacao.inicio) }}</p>
        </div>
        <div>
          <label class="block text-gray-500 text-sm">Fim</label>
          <p class="text-gray-900">{{ formatDate(locacao.fim) }}</p>
        </div>
      </div>

      <div class="flex justify-end gap-4 pt-4 border-t">
        <a :href="`/locacoes/${id}/edit`" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-6 rounded-lg transition">Editar</a>
        <a href="/locacoes" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-lg transition">Voltar</a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: { id: { type: Number, required: true } },
  data() { return { locacao: null, loading: true, error: null } },
  computed: {
    statusClass() {
      if (!this.locacao) return '';
      const s = this.locacao.status;
      if (s === 'ativo') return 'bg-green-100 text-green-800';
      if (s === 'cobranca') return 'bg-amber-100 text-amber-800';
      if (s === 'finalizado') return 'bg-gray-100 text-gray-800';
      return 'bg-red-100 text-red-800';
    },
    statusLabel() {
      if (!this.locacao) return '';
      const labels = { ativo: 'Ativo', cobranca: 'Cobrança', finalizado: 'Finalizado', cancelado: 'Cancelado' };
      return labels[this.locacao.status] || this.locacao.status;
    }
  },
  mounted() {
    const token = localStorage.getItem('api_token');
    const headers = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    fetch(`/api/locacoes/${this.id}`, { headers })
      .then(res => {
        if (res.status === 404) { this.error = 'Locação não encontrada'; this.loading = false; return; }
        if (res.status === 401) { window.location.href = '/login'; return; }
        return res.json();
      })
      .then(data => { if (data) { this.locacao = data; this.loading = false; } })
      .catch(() => { this.error = 'Erro ao carregar'; this.loading = false; });
  },
  methods: {
    formatDate(d) {
      if (!d) return '—';
      return new Date(d).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    },
    formatCurrency(value) {
      return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  }
}
</script>
