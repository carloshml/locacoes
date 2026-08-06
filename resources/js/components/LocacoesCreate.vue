<template>
  <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4 rounded-xl">
    <div class="text-white font-bold text-lg">{{ id > 0 ? 'Editar Locação' : 'Nova Locação' }}</div>
  </div>

  <div class="w-full bg-white shadow-xl rounded-xl p-8 mt-4">
    <form @submit.prevent="salvar" class="space-y-5">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Item</label>
        <select v-model="form.item_id" @change="onItemChange" class="w-full border rounded-lg p-2 focus:ring focus:ring-amber-300">
          <option value="">Selecione um item</option>
          <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }} — R$ {{ Number(item.valor || 0).toFixed(2) }}</option>
        </select>
        <p v-if="erros.item_id" class="text-red-600 text-sm mt-1">{{ erros.item_id[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Cliente Responsável</label>
        <div class="flex items-center gap-3">
          <select v-model="form.cliente_id" class="w-[60%] border rounded-lg p-2 focus:ring focus:ring-amber-300">
            <option value="">Selecione um cliente</option>
            <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nome }}</option>
          </select>
          <span v-if="clienteSelecionado && clienteSelecionado.telefone" class="w-[40%] text-gray-600 text-sm">
            📞 {{ clienteSelecionado.telefone }}
          </span>
        </div>
        <p v-if="erros.cliente_id" class="text-red-600 text-sm mt-1">{{ erros.cliente_id[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Localização</label>
        <input v-model="form.location" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-amber-300"
          placeholder="Ex: Sala 301, Prédio A, Depósito">
        <p v-if="erros.location" class="text-red-600 text-sm mt-1">{{ erros.location[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Valor (R$)</label>
        <div class="flex items-center gap-2">
          <input v-model="form.valor" type="number" step="0.01" min="0"
            class="flex-1 border rounded-lg p-2 focus:ring focus:ring-amber-300"
            placeholder="0,00">
          <button type="button" @click="ajustarValor(-5)"
            class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition font-bold">
            -5
          </button>
          <button type="button" @click="ajustarValor(5)"
            class="bg-green-500 text-white px-3 py-2 rounded-lg hover:bg-green-600 transition font-bold">
            +5
          </button>
        </div>
        <p v-if="erros.valor" class="text-red-600 text-sm mt-1">{{ erros.valor[0] }}</p>
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

      <div class="flex justify-end">
        <button type="submit" class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700 transition">
          {{ id > 0 ? 'Atualizar' : 'Salvar' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
  components: { VueDatePicker },
  props: { id: { type: Number, default: 0 } },
  computed: {
    clienteSelecionado() {
      if (!this.form.cliente_id) return null;
      return this.clientes.find(c => c.id == this.form.cliente_id) || null;
    }
  },
  data() {
    return {
      form: { item_id: '', cliente_id: '', location: '', valor: '', inicio: null, fim: null, status: 'ativo' },
      dateFormats: {
        input: 'dd.MM.yyyy - HH:mm'
      },
      items: [], clientes: [], erros: {}
    }
  },
  mounted() {
    this.fetchSelects();
    if (this.id > 0) this.fetchData();
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
    onItemChange() {
      if (this.id > 0) return; // Não sobrescrever valor ao editar
      const item = this.items.find(i => i.id == this.form.item_id);
      if (item) {
        this.form.valor = Number(item.valor || 0);
      }
    },
    ajustarValor(delta) {
      const atual = Number(this.form.valor) || 0;
      const novo = atual + delta;
      this.form.valor = novo < 0 ? 0 : novo;
    },
    fetchData() {
      fetch(`/api/locacoes/${this.id}`, { headers: this.getHeaders() })
        .then(r => r.json())
        .then(data => {
          this.form = {
            item_id: data.item_id,
            cliente_id: data.cliente_id,
            location: data.location,
            valor: data.valor ?? '',
            inicio: data.inicio || null,
            fim: data.fim || null,
            status: data.status
          };
        });
    },
    formatDateForApi(date) {
      if (!date) return null;
      const d = date instanceof Date ? date : new Date(date);
      const pad = (n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
    },
    salvar() {
      const url = this.id > 0 ? `/api/locacoes/${this.id}` : '/api/locacoes';
      const method = this.id > 0 ? 'PUT' : 'POST';

      fetch(url, { method, headers: this.getHeaders(), body: JSON.stringify(this.form) })
        .then(async res => {
          if (!res.ok) { const d = await res.json(); this.erros = d.errors || {}; return; }
          this.erros = {};
          alert(this.id > 0 ? 'Locação atualizada!' : 'Locação criada!');
          window.location.href = '/locacoes';
        });
    }
  }
}
</script>
