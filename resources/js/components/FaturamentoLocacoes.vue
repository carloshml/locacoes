<template>
  <div>
    <!-- Cards destaque -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <!-- Faturamento mês atual -->
      <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-8 shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-emerald-600 text-sm font-semibold uppercase">Faturamento do Mês Atual</p>
            <p class="text-4xl font-bold text-emerald-900 mt-2">R$ {{ formatCurrency(mesAtual) }}</p>
            <p class="text-emerald-600 text-xs mt-1">Apenas locações finalizadas</p>
          </div>
          <div class="bg-emerald-500 rounded-full p-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
              </path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Cobranças pendentes total -->
      <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-8 shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-amber-600 text-sm font-semibold uppercase">Cobranças Pendentes</p>
            <p class="text-4xl font-bold text-amber-900 mt-2">R$ {{ formatCurrency(totalCobrancas) }}</p>
            <p class="text-amber-600 text-xs mt-1">{{ cobrancas.length }} locação(ões) a cobrar</p>
          </div>
          <div class="bg-amber-500 rounded-full p-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z">
              </path>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Cobranças Pendentes -->
    <div v-if="cobrancas.length > 0" class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
      <div class="px-6 py-4 bg-amber-50 border-b">
        <h3 class="text-lg font-semibold text-amber-800">Cobranças a Realizar</h3>
      </div>
      <div class="p-6">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b">
              <th class="pb-3 text-gray-600 font-semibold">Item</th>
              <th class="pb-3 text-gray-600 font-semibold">Cliente</th>
              <th class="pb-3 text-gray-600 font-semibold">Período</th>
              <th class="pb-3 text-gray-600 font-semibold text-right">Valor (R$)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in cobrancas" :key="c.id" class="border-b last:border-0 hover:bg-amber-50 transition cursor-pointer" @click="verLocacao(c.id)">
              <td class="py-3 text-gray-800">{{ c.item ? c.item.name : '—' }}</td>
              <td class="py-3 text-gray-800">{{ c.cliente ? c.cliente.nome : '—' }}</td>
              <td class="py-3 text-gray-500 text-sm">{{ formatDate(c.inicio) }} → {{ formatDate(c.fim) }}</td>
              <td class="py-3 text-right text-amber-700 font-bold">R$ {{ formatCurrency(c.valor) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="border-t-2">
              <td colspan="3" class="pt-4 text-gray-800 font-bold">Total Cobranças</td>
              <td class="pt-4 text-right text-amber-700 font-bold text-lg">R$ {{ formatCurrency(totalCobrancas) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Tabela dos últimos 6 meses -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Faturamento dos Últimos 6 Meses</h3>
      </div>
      <div class="p-6">
        <div v-if="loading" class="text-center py-8 text-gray-500">Carregando...</div>
        <table v-else class="w-full">
          <thead>
            <tr class="text-left border-b">
              <th class="pb-3 text-gray-600 font-semibold">Mês</th>
              <th class="pb-3 text-gray-600 font-semibold text-right">Total (R$)</th>
              <th class="pb-3 text-gray-600 font-semibold text-right">Proporção</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="mes in meses" :key="mes.mes" class="border-b last:border-0 hover:bg-gray-50 transition">
              <td class="py-4 text-gray-800 font-medium">{{ mes.nome }}</td>
              <td class="py-4 text-right text-gray-900 font-bold">R$ {{ formatCurrency(mes.total) }}</td>
              <td class="py-4 text-right w-48">
                <div class="flex items-center justify-end gap-2">
                  <div class="w-32 bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                      :style="{ width: getBarWidth(mes.total) + '%' }"></div>
                  </div>
                  <span class="text-xs text-gray-500 w-10 text-right">{{ getBarWidth(mes.total) }}%</span>
                </div>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="border-t-2">
              <td class="pt-4 text-gray-800 font-bold">Total 6 meses</td>
              <td class="pt-4 text-right text-emerald-700 font-bold text-lg">R$ {{ formatCurrency(totalGeral) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      mesAtual: 0,
      meses: [],
      cobrancas: [],
      totalCobrancas: 0,
      loading: true
    }
  },
  computed: {
    totalGeral() {
      return this.meses.reduce((sum, m) => sum + m.total, 0);
    },
    maxMes() {
      return Math.max(...this.meses.map(m => m.total), 1);
    }
  },
  mounted() {
    this.fetchFaturamento();
  },
  methods: {
    getHeaders() {
      const token = localStorage.getItem('api_token');
      const h = { 'Accept': 'application/json' };
      if (token) h['Authorization'] = `Bearer ${token}`;
      return h;
    },
    fetchFaturamento() {
      fetch('/api/locacoes/faturamento', { headers: this.getHeaders() })
        .then(res => {
          if (res.status === 401) {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
            throw new Error('Não autorizado');
          }
          return res.json();
        })
        .then(data => {
          this.mesAtual = data.mes_atual;
          this.meses = data.meses;
          this.cobrancas = data.cobrancas;
          this.totalCobrancas = data.total_cobrancas;
          this.loading = false;
        })
        .catch(err => {
          console.error(err);
          this.loading = false;
        });
    },
    formatCurrency(value) {
      return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },
    getBarWidth(total) {
      if (this.maxMes === 0) return 0;
      return Math.round((total / this.maxMes) * 100);
    },
    verLocacao(id) {
      window.location.href = `/locacoes/${id}`;
    }
  }
}
</script>
