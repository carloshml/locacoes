<template>
  <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-xl">
    <div class="flex justify-between items-center text-white">
      {{ id > 0 ? 'Editar Cliente' : 'Cadastrar Cliente' }}
    </div>
  </div>

  <div class="w-full bg-white shadow-xl rounded-xl p-8">
    <form @submit.prevent="salvarCliente" class="space-y-5">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Nome</label>
        <input v-model="cliente.nome" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400">
        <p v-if="erros.nome" class="text-red-600 text-sm mt-1">{{ erros.nome[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Idade</label>
        <input v-model="cliente.idade" type="number" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400">
        <p v-if="erros.idade" class="text-red-600 text-sm mt-1">{{ erros.idade[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Documento</label>
        <input v-model="cliente.documento" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400">
        <p v-if="erros.documento" class="text-red-600 text-sm mt-1">{{ erros.documento[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Endereço</label>
        <input v-model="cliente.endereco" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400" placeholder="Rua, número, bairro, cidade...">
        <p v-if="erros.endereco" class="text-red-600 text-sm mt-1">{{ erros.endereco[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Telefone</label>
        <input v-model="cliente.telefone" type="text" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400" placeholder="(00) 00000-0000">
        <p v-if="erros.telefone" class="text-red-600 text-sm mt-1">{{ erros.telefone[0] }}</p>
      </div>

      <div class="flex justify-end gap-4">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
          {{ id > 0 ? 'Atualizar Cliente' : 'Salvar Cliente' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  props: { id: { type: Number, default: 0 } },
  data() {
    return { cliente: { nome: '', idade: '', documento: '', endereco: '', telefone: '' }, erros: {} }
  },
  mounted() {
    if (this.id > 0) {
      const token = localStorage.getItem('api_token');
      const headers = { 'Accept': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;
      fetch(`/api/clientes/${this.id}`, { headers })
        .then(res => res.json())
        .then(data => { this.cliente = { nome: data.nome, idade: data.idade, documento: data.documento, endereco: data.endereco || '', telefone: data.telefone || '' }; });
    }
  },
  methods: {
    salvarCliente() {
      const url = this.id > 0 ? `/api/clientes/${this.id}` : '/api/clientes';
      const method = this.id > 0 ? 'PUT' : 'POST';
      const token = localStorage.getItem('api_token');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;
      if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

      fetch(url, { method, headers, body: JSON.stringify(this.cliente) })
        .then(async res => {
          if (res.status === 401) { localStorage.removeItem('api_token'); window.location.href = '/login'; return; }
          if (!res.ok) { const d = await res.json(); this.erros = d.errors || {}; return; }
          this.erros = {};
          alert(this.id > 0 ? 'Cliente atualizado!' : 'Cliente cadastrado!');
          window.location.href = '/clientes';
        });
    }
  }
}
</script>
