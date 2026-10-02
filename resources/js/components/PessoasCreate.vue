<template>

  <toast-message ref="toast"></toast-message>

  <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-xl">
    <div class="flex justify-between items-center text-white">
      {{ id > 0 ? 'Editar Pessoa' : 'Cadastrar Pessoa' }}
    </div>
  </div>

  <div class="w-full   bg-white shadow-xl rounded-xl p-8"> <!-- Form -->
    <form @submit.prevent="salvarPessoa" class="space-y-5">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Nome</label>
        <input v-model="novaPessoa.nome" type="text"
          class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400">
        <p v-if="erros.nome" class="text-red-600 text-sm mt-1">{{ erros.nome[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Idade</label>
        <input v-model="novaPessoa.idade" type="number"
          class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400">
        <p v-if="erros.idade" class="text-red-600 text-sm mt-1">{{ erros.idade[0] }}</p>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Documento</label>
        <input v-model="novaPessoa.documento" type="text"
          class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300 focus:border-blue-400">
        <p v-if="erros.documento" class="text-red-600 text-sm mt-1">{{ erros.documento[0] }}</p>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-4">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
          {{ id > 0 ? 'Atualizar Pessoa' : 'Salvar Pessoa' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import ToastMessage from './ToastMessage.vue';

export default {
  components: { ToastMessage },
  props: {
    id: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      novaPessoa: { nome: '', idade: '', documento: '' },
      erros: {}
    }
  },
  mounted() {
    if (this.id > 0) {
      // busca pessoa existente
      fetch(`/api/pessoas/${this.id}`)
        .then(res => res.json())
        .then(data => {
          this.novaPessoa = {
            nome: data.nome,
            idade: data.idade,
            documento: data.documento
          }
        })
        .catch(err => console.error(err));
    }
  },
  methods: {
    salvarPessoa() {
      const url = this.id > 0 ? `/api/pessoas/${this.id}` : '/api/pessoas';
      const method = this.id > 0 ? 'PUT' : 'POST';

      const token = localStorage.getItem('api_token');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const requisicao = {
        method,
        headers,
        body: JSON.stringify(this.novaPessoa)
      }

      console.log('requisicao :::: ', requisicao)

      fetch(url, requisicao)
        .then(async res => {
          if (res.status === 401) {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
            throw new Error('Não autorizado');
          }
          if (!res.ok) {
            const errorData = await res.json();
            this.erros = errorData.errors || {};
            throw new Error(errorData.message);
          }
          return res.json();
        })
        .then(() => {
          this.erros = {};
          this.$refs.toast.show(this.id > 0 ? 'Pessoa atualizada com sucesso!' : 'Pessoa cadastrada com sucesso!');
          setTimeout(() => { window.location.href = `/pessoas/`; }, 1500);
        })
        .catch(err => console.error(err));
    }
  }
}
</script>