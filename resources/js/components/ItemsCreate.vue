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

      <div>
        <label class="block text-gray-700 font-medium mb-1">Foto</label>
        <div class="flex items-center gap-4">
          <div v-if="fotoPreview" class="w-20 h-20 rounded-lg overflow-hidden border-2 border-gray-200">
            <img :src="fotoPreview" class="w-full h-full object-cover" alt="Preview">
          </div>
          <div v-else class="w-20 h-20 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
          </div>
          <div class="flex-1">
            <input type="file" accept="image/*" @change="onFotoChange" class="w-full border rounded-lg p-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-teal-50 file:text-teal-700 file:font-medium hover:file:bg-teal-100">
            <p class="text-gray-500 text-xs mt-1">JPG, PNG ou GIF. A imagem será salva no banco.</p>
          </div>
        </div>
        <p v-if="erros.foto" class="text-red-600 text-sm mt-1">{{ erros.foto[0] }}</p>
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
    return { item: { name: '', valor: '', descricao: '', foto: '' }, fotoPreview: null, erros: {} }
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
        .then(data => {
          this.item = { name: data.name, valor: data.valor ?? '', descricao: data.descricao || '', foto: '' };
          if (data.foto) {
            this.fotoPreview = data.foto;
          }
        });
    },
    onFotoChange(e) {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (ev) => {
        this.fotoPreview = ev.target.result;
        this.item.foto = ev.target.result;
      };
      reader.readAsDataURL(file);
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
