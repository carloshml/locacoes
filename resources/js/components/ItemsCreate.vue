<template>
  <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-4 rounded-xl">
    <div class="text-white font-bold text-lg">{{ id > 0 ? 'Editar Item' : 'Cadastrar Item' }}</div>
  </div>

  <div class="w-full bg-white shadow-xl rounded-xl p-8 mt-4">
    <form @submit.prevent="salvarItem" class="space-y-5">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Nome</label>
        <input v-model="item.name" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-teal-300 focus:border-teal-400">
        <p v-if="erros.name" class="text-red-600 text-sm mt-1">{{ erros.name[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Valor (R$)</label>
        <input v-model="item.valor" type="number" step="0.01" min="0" class="w-full border rounded-lg p-2 focus:ring focus:ring-teal-300 focus:border-teal-400" placeholder="0,00">
        <p v-if="erros.valor" class="text-red-600 text-sm mt-1">{{ erros.valor[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Descrição</label>
        <textarea v-model="item.descricao" rows="3" class="w-full border rounded-lg p-2 focus:ring focus:ring-teal-300 focus:border-teal-400" placeholder="Descreva o item..."></textarea>
        <p v-if="erros.descricao" class="text-red-600 text-sm mt-1">{{ erros.descricao[0] }}</p>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition">
          {{ id > 0 ? 'Atualizar Item' : 'Salvar Item' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  props: { id: { type: Number, default: 0 } },
  data() {
    return { item: { name: '', valor: '', descricao: '' }, erros: {} }
  },
  mounted() {
    if (this.id > 0) this.fetchItem();
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
    fetchItem() {
      fetch(`/api/items/${this.id}`, { headers: this.getHeaders() })
        .then(res => res.json())
        .then(data => { this.item = { name: data.name, valor: data.valor ?? '', descricao: data.descricao || '' }; });
    },
    salvarItem() {
      const url = this.id > 0 ? `/api/items/${this.id}` : '/api/items';
      const method = this.id > 0 ? 'PUT' : 'POST';

      fetch(url, { method, headers: this.getHeaders(), body: JSON.stringify(this.item) })
        .then(async res => {
          if (res.status === 401) {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
            return;
          }
          if (!res.ok) {
            const d = await res.json();
            console.error('Erro ao salvar item:', d);
            this.erros = d.errors || {};
            return;
          }
          this.erros = {};
          alert(this.id > 0 ? 'Item atualizado!' : 'Item cadastrado!');
          window.location.href = '/itens';
        })
        .catch(err => console.error('Erro de rede:', err));
    }
  }
}
</script>
