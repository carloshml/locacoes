<template>
  <toast-message ref="toast"></toast-message>

  <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4 rounded-xl">
    <div class="text-white font-bold text-lg">{{ id > 0 ? 'Editar Locação (Multi-item)' : 'Nova Locação (Multi-item)' }}</div>
  </div>

  <div class="w-full bg-white shadow-xl rounded-xl p-8 mt-4">
    <form @submit.prevent="salvar" class="space-y-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-gray-700 font-medium mb-1">Nome / Referência da Locação</label>
          <input v-model="form.nome" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-amber-300"
            placeholder="Ex: Evento Casamento Silva">
          <p v-if="erros.nome" class="text-red-600 text-sm mt-1">{{ erros.nome[0] }}</p>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Cliente Responsável</label>
          <div class="flex items-center gap-3">
            <select v-model="form.cliente_id" class="w-full border rounded-lg p-2 focus:ring focus:ring-amber-300">
              <option value="">Selecione um cliente</option>
              <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nome }}</option>
            </select>
            <span v-if="clienteSelecionado && clienteSelecionado.telefone" class="text-gray-600 text-sm whitespace-nowrap">
              📞 {{ clienteSelecionado.telefone }}
            </span>
          </div>
          <p v-if="erros.cliente_id" class="text-red-600 text-sm mt-1">{{ erros.cliente_id[0] }}</p>
        </div>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Localização</label>
        <input v-model="form.location" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-amber-300"
          placeholder="Ex: Sala 301, Prédio A, Depósito">
        <p v-if="erros.location" class="text-red-600 text-sm mt-1">{{ erros.location[0] }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-gray-700 font-medium mb-1">Início</label>
          <VueDatePicker v-model="form.inicio" :enable-time-picker="true" :formats="dateFormats"
            model-type="yyyy-MM-dd HH:mm:ss" :start-time="{ hours: 0, minutes: 0 }" auto-apply
            placeholder="Selecione data e hora" class="w-full" />
          <p v-if="erros.inicio" class="text-red-600 text-sm mt-1">{{ erros.inicio[0] }}</p>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Fim</label>
          <VueDatePicker v-model="form.fim" :enable-time-picker="true" :formats="dateFormats"
            model-type="yyyy-MM-dd HH:mm:ss" :start-time="{ hours: 23, minutes: 59 }" auto-apply
            placeholder="Selecione data e hora" class="w-full" />
          <p v-if="erros.fim" class="text-red-600 text-sm mt-1">{{ erros.fim[0] }}</p>
        </div>
      </div>

      <div v-if="id > 0">
        <label class="block text-gray-700 font-medium mb-1">Status</label>
        <select v-model="form.status" class="w-full border rounded-lg p-2 focus:ring focus:ring-amber-300">
          <option value="ativo">Ativo</option>
          <option value="cobranca">Cobrança</option>
          <option value="finalizado">Finalizado</option>
          <option value="cancelado">Cancelado</option>
        </select>
      </div>

      <!-- Itens da locação -->
      <div class="border-t pt-4">
        <div class="flex justify-between items-center mb-3">
          <h3 class="text-lg font-semibold text-gray-800">Itens da Locação</h3>
          <button type="button" @click="adicionarItem"
            class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition text-sm font-medium">
            + Adicionar item
          </button>
        </div>

        <p v-if="erros.itens" class="text-red-600 text-sm mb-2">{{ erros.itens[0] }}</p>

        <div v-if="form.itens.length === 0" class="text-center py-8 bg-gray-50 rounded-lg text-gray-500">
          Nenhum item adicionado. Uma locação precisa de pelo menos um item.
        </div>

        <div v-for="(linha, index) in form.itens" :key="index"
          class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end bg-gray-50 rounded-lg p-3 mb-2">
          <div class="md:col-span-5">
            <label class="block text-gray-600 text-xs font-medium mb-1">Item</label>
            <select v-model="linha.item_id" @change="onItemChange(linha)"
              class="w-full border rounded-lg p-2 text-sm focus:ring focus:ring-amber-300">
              <option value="">Selecione um item</option>
              <option v-for="item in items" :key="item.id" :value="item.id">
                {{ item.name }} — R$ {{ Number(item.valor || 0).toFixed(2) }}
              </option>
            </select>
            <p v-if="erros[`itens.${index}.item_id`]" class="text-red-600 text-xs mt-1">
              {{ erros[`itens.${index}.item_id`][0] }}
            </p>
          </div>
          <div class="md:col-span-2">
            <label class="block text-gray-600 text-xs font-medium mb-1">Qtde</label>
            <input v-model.number="linha.quantidade" type="number" min="1"
              class="w-full border rounded-lg p-2 text-sm focus:ring focus:ring-amber-300" />
            <p v-if="erros[`itens.${index}.quantidade`]" class="text-red-600 text-xs mt-1">
              {{ erros[`itens.${index}.quantidade`][0] }}
            </p>
          </div>
          <div class="md:col-span-3">
            <label class="block text-gray-600 text-xs font-medium mb-1">Valor unitário (R$)</label>
            <input v-model.number="linha.valor" type="number" step="0.01" min="0"
              class="w-full border rounded-lg p-2 text-sm focus:ring focus:ring-amber-300" />
            <p v-if="erros[`itens.${index}.valor`]" class="text-red-600 text-xs mt-1">
              {{ erros[`itens.${index}.valor`][0] }}
            </p>
          </div>
          <div class="md:col-span-1 text-right text-sm text-gray-700 font-medium">
            R$ {{ formatCurrency(subtotal(linha)) }}
          </div>
          <div class="md:col-span-1">
            <button type="button" @click="removerItem(index)"
              class="w-full bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition text-sm font-bold"
              title="Remover item">
              ✕
            </button>
          </div>
        </div>

        <div v-if="form.itens.length > 0" class="flex justify-end mt-3 text-gray-900 font-bold">
          Total da locação: R$ {{ formatCurrency(valorTotal) }}
        </div>
      </div>

      <div class="flex justify-end pt-4 border-t">
        <button type="submit" :disabled="salvando"
          class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700 transition disabled:opacity-50">
          {{ salvando ? 'Salvando...' : (id > 0 ? 'Atualizar' : 'Salvar') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import ToastMessage from './ToastMessage.vue';

export default {
  components: { VueDatePicker, ToastMessage },
  props: { id: { type: Number, default: 0 } },
  computed: {
    clienteSelecionado() {
      if (!this.form.cliente_id) return null;
      return this.clientes.find(c => c.id == this.form.cliente_id) || null;
    },
    valorTotal() {
      return this.form.itens.reduce((sum, l) => sum + this.subtotal(l), 0);
    }
  },
  data() {
    return {
      form: {
        nome: '',
        cliente_id: '',
        location: '',
        inicio: null,
        fim: null,
        status: 'ativo',
        itens: []
      },
      dateFormats: {
        input: 'dd.MM.yyyy - HH:mm'
      },
      items: [], clientes: [], erros: {}, salvando: false
    }
  },
  mounted() {
    this.fetchSelects();
    if (this.id > 0) this.fetchData();
    else this.adicionarItem();
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
        fetch('/api/items', { headers: h }).then(r => r.json()),
        fetch('/api/clientes', { headers: h }).then(r => r.json()),
      ]).then(([items, clientes]) => {
        this.items = items;
        this.clientes = clientes;
      });
    },
    adicionarItem() {
      this.form.itens.push({ id: null, item_id: '', quantidade: 1, valor: '' });
    },
    removerItem(index) {
      this.form.itens.splice(index, 1);
    },
    onItemChange(linha) {
      const item = this.items.find(i => i.id == linha.item_id);
      if (item) {
        linha.valor = Number(item.valor || 0);
      }
    },
    subtotal(linha) {
      return (Number(linha.valor) || 0) * (Number(linha.quantidade) || 0);
    },
    formatCurrency(value) {
      return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    fetchData() {
      fetch(`/api/v2/locacoes/${this.id}`, { headers: this.getHeaders() })
        .then(r => r.json())
        .then(data => {
          this.form = {
            nome: data.nome || '',
            cliente_id: data.cliente_id,
            location: data.location,
            inicio: data.inicio || null,
            fim: data.fim || null,
            status: data.status,
            itens: (data.itens || []).map(i => ({
              id: i.id,
              item_id: i.item_id,
              quantidade: i.quantidade ?? 1,
              valor: i.valor ?? ''
            }))
          };
        });
    },
    salvar() {
      if (this.form.itens.length === 0) {
        this.$refs.toast.show('Adicione pelo menos um item.', 'error');
        return;
      }

      const url = this.id > 0 ? `/api/v2/locacoes/${this.id}` : '/api/v2/locacoes';
      const method = this.id > 0 ? 'PUT' : 'POST';
      this.salvando = true;

      fetch(url, { method, headers: this.getHeaders(), body: JSON.stringify(this.form) })
        .then(async res => {
          if (!res.ok) {
            const d = await res.json().catch(() => ({}));
            this.erros = d.errors || {};
            const msg = d.message || 'Falha ao salvar a locação.';
            this.$refs.toast.show(msg, 'error', 5000);
            return;
          }
          this.erros = {};
          this.$refs.toast.show(this.id > 0 ? 'Locação atualizada!' : 'Locação criada!');
          setTimeout(() => { window.location.href = '/locacoes'; }, 1200);
        })
        .catch(() => this.$refs.toast.show('Erro de rede ao salvar.', 'error'))
        .finally(() => { this.salvando = false; });
    }
  }
}
</script>
