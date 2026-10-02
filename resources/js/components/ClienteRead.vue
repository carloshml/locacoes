<template>
  <div v-if="loading" class="text-center py-8"><p class="text-gray-500">Carregando...</p></div>

  <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
    <p class="text-red-600 font-medium">{{ error }}</p>
    <a href="/clientes" class="inline-block mt-4 text-green-600 hover:text-green-800 font-medium">← Voltar</a>
  </div>

  <div v-else>
    <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-xl">
      <div class="text-white font-bold text-lg">Cliente</div>
    </div>

    <div class="w-full bg-white shadow-xl rounded-xl p-8 mt-4">
      <form class="space-y-5">
        <div>
          <label class="block text-gray-700 font-medium mb-1">Foto</label>
          <div v-if="cliente.foto" class="w-32 h-32 rounded-lg overflow-hidden border-2 border-gray-200">
            <img :src="cliente.foto" class="w-full h-full object-cover" alt="Foto do cliente">
          </div>
          <div v-else class="w-32 h-32 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
          </div>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Nome</label>
          <input disabled :value="cliente.nome" type="text" class="w-full border rounded-lg p-2 bg-gray-50">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Idade</label>
          <input disabled :value="cliente.idade" type="text" class="w-full border rounded-lg p-2 bg-gray-50">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Documento</label>
          <input disabled :value="cliente.documento" type="text" class="w-full border rounded-lg p-2 bg-gray-50">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Endereço</label>
          <input disabled :value="cliente.endereco" type="text" class="w-full border rounded-lg p-2 bg-gray-50">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Telefone</label>
          <input disabled :value="cliente.telefone" type="text" class="w-full border rounded-lg p-2 bg-gray-50">
        </div>
        <div class="flex justify-end gap-4">
          <a :href="`/clientes/${id}/edit`" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition">Editar</a>
          <a href="/clientes" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-lg transition">Voltar</a>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  props: { id: { type: Number, required: true } },
  data() { return { cliente: null, loading: true, error: null } },
  mounted() {
    const token = localStorage.getItem('api_token');
    const headers = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;
    fetch(`/api/clientes/${this.id}`, { headers })
      .then(res => { if (res.status === 404) { this.error = 'Cliente não encontrado'; this.loading = false; return; } return res.json(); })
      .then(data => { if (data) { this.cliente = data; this.loading = false; } })
      .catch(() => { this.error = 'Erro ao carregar'; this.loading = false; });
  }
}
</script>
