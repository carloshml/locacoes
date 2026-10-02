<template>
  <toast-message ref="toast"></toast-message>

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
        <input
          v-model="cliente.idade"
          type="number"
          inputmode="numeric"
          min="0"
          max="150"
          step="1"
          @keydown="bloquearNaoNumerico"
          @input="sanitizarIdade"
          @paste="onPasteIdade"
          class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400">
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
            <input type="file" accept="image/*" @change="onFotoChange" class="w-full border rounded-lg p-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-medium hover:file:bg-blue-100">
            <p class="text-gray-500 text-xs mt-1">JPG, PNG ou GIF. A imagem será salva no banco.</p>
          </div>
        </div>
        <p v-if="erros.foto" class="text-red-600 text-sm mt-1">{{ erros.foto[0] }}</p>
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
import ToastMessage from './ToastMessage.vue';

export default {
  components: { ToastMessage },
  props: { id: { type: Number, default: 0 } },
  data() {
    return { cliente: { nome: '', idade: '', documento: '', endereco: '', telefone: '', foto: '' }, fotoPreview: null, erros: {} }
  },
  mounted() {
    if (this.id > 0) {
      const token = localStorage.getItem('api_token');
      const headers = { 'Accept': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;
      fetch(`/api/clientes/${this.id}`, { headers })
        .then(res => res.json())
        .then(data => {
          this.cliente = { nome: data.nome, idade: data.idade, documento: data.documento, endereco: data.endereco || '', telefone: data.telefone || '', foto: '' };
          if (data.foto) {
            this.fotoPreview = data.foto;
          }
        });
    }
  },
  methods: {
    // Bloqueia os caracteres que o input type=number aceita mas nao sao digitos
    // (e, E, +, -, .) e permite teclas de controle (backspace, setas, tab...)
    bloquearNaoNumerico(e) {
      const teclasPermitidas = ['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
      if (teclasPermitidas.includes(e.key) || e.ctrlKey || e.metaKey) return;
      if (!/^[0-9]$/.test(e.key)) {
        e.preventDefault();
      }
    },
    // Remove qualquer caractere nao numerico que escape (ex.: colagem via mouse)
    sanitizarIdade(e) {
      const apenasDigitos = String(e.target.value).replace(/\D/g, '');
      this.cliente.idade = apenasDigitos;
    },
    onPasteIdade(e) {
      e.preventDefault();
      const texto = (e.clipboardData || window.clipboardData).getData('text');
      this.cliente.idade = String(texto).replace(/\D/g, '');
    },
    onFotoChange(e) {
      const file = e.target.files[0];
      if (!file) return;

      // Limite de 1 MB para a imagem (em base64 vira ~1.33 MB no envio,
      // seguro dentro do post_max_size do servidor).
      const MAX_BYTES = 1 * 1024 * 1024;
      if (file.size > MAX_BYTES) {
        this.$refs.toast.show('A imagem deve ter no máximo 1 MB.', 'error', 2500);
        e.target.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (ev) => {
        this.fotoPreview = ev.target.result;
        this.cliente.foto = ev.target.result;
      };
      reader.readAsDataURL(file);
    },
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
          this.$refs.toast.show(this.id > 0 ? 'Cliente atualizado!' : 'Cliente cadastrado!');
          // aguarda a mensagem aparecer antes de redirecionar
          setTimeout(() => { window.location.href = '/clientes'; }, 1500);
        });
    }
  }
}
</script>
