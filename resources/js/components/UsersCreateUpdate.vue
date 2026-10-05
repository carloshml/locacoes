<!-- resources/js/components/UsersCreateUpdate.vue -->
<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <toast-message ref="toast"></toast-message>
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
      <h2 class="text-2xl font-bold text-white">
        {{ id > 0 ? 'Editar Usuário' : 'Cadastrar Usuário' }}
      </h2>
    </div>

    <div class="p-8">
      <div v-if="errors.length" class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded">
        <p v-for="error in errors" :key="error" class="text-red-600 text-sm">{{ error }}</p>
      </div>

      <form @submit.prevent="salvarUsuario" class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-gray-700 font-medium mb-1">Nome *</label>
            <input v-model="form.name" type="text" required
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
            <p v-if="erros.name" class="text-red-600 text-sm mt-1">{{ erros.name[0] }}</p>
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">E-mail *</label>
            <input v-model="form.email" type="email" required
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
            <p v-if="erros.email" class="text-red-600 text-sm mt-1">{{ erros.email[0] }}</p>
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">Telefone</label>
            <input v-model="form.phone" type="tel" placeholder="(00) 00000-0000"
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">Cargo</label>
            <input v-model="form.position" type="text" placeholder="Ex: Analista de Sistemas"
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">Perfil *</label>
            <select v-model="form.role" required class="w-full border rounded-lg p-2">
              <option value="user">Usuário</option>
              <option value="manager">Gerente</option>
              <option value="admin">Administrador</option>
            </select>
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">Status</label>
            <select v-model="form.is_active" class="w-full border rounded-lg p-2">
              <option :value="true">Ativo</option>
              <option :value="false">Inativo</option>
            </select>
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">Senha {{ id > 0 ? '(deixe em branco para manter)' : '*' }}</label>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" 
              :required="id === 0"
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
            <button type="button" @click="showPassword = !showPassword" class="text-sm text-blue-600 mt-1">
              {{ showPassword ? 'Ocultar' : 'Mostrar' }} senha
            </button>
          </div>

          <div v-if="id > 0">
            <label class="block text-gray-700 font-medium mb-1">Confirmar Senha</label>
            <input v-model="form.password_confirmation" type="password"
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
          </div>
        </div>

        <!-- Avatar Upload -->
        <div v-if="id > 0" class="border-t pt-5">
          <label class="block text-gray-700 font-medium mb-2">Foto/Avatar</label>
          <div class="flex items-center gap-4">
            <div class="w-24 h-24 bg-gray-100 rounded-full overflow-hidden">
              <img :src="avatarPreview" class="w-full h-full object-cover">
            </div>
            <div>
              <input type="file" @change="onFileChange" accept="image/*" class="hidden" ref="fileInput">
              <button type="button" @click="$refs.fileInput.click()" 
                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                Selecionar imagem
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-4 pt-5">
          <button type="button" @click="voltar" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400">
            Cancelar
          </button>
          <button type="submit" :disabled="loading"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
            {{ loading ? 'Salvando...' : (id > 0 ? 'Atualizar' : 'Cadastrar') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import ToastMessage from './ToastMessage.vue';

export default {
  components: { ToastMessage },
  props: {
    id: { type: Number, default: 0 }
  },
  data() {
    return {
      form: {
        name: '',
        email: '',
        phone: '',
        position: '',
        role: 'user',
        is_active: true,
        password: '',
        password_confirmation: ''
      },
      avatarFile: null,
      avatarPreview: '/default-avatar.png',
      loading: false,
      errors: [],
      erros: {},
      showPassword: false
    }
  },
  mounted() {
    if (this.id > 0) {
      this.fetchUsuario();
    } else {
      this.avatarPreview = `https://ui-avatars.com/api/?name=Novo&background=3b82f6&color=fff&size=96`;
    }
  },
  methods: {
    fetchUsuario() {
      const token = localStorage.getItem('api_token');
      const headers = {};

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      fetch(`/api/usuarios/${this.id}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
        .then(res => {
          if (res.status === 401) {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
            throw new Error('Não autorizado');
          }
          return res.json();
        })
        .then(data => {
          this.form = {
            name: data.name,
            email: data.email,
            phone: data.phone || '',
            position: data.position || '',
            role: data.role,
            is_active: data.is_active,
            password: '',
            password_confirmation: ''
          };
          this.avatarPreview = data.avatar ? data.avatar : `https://ui-avatars.com/api/?name=${data.name}&background=3b82f6&color=fff&size=96`;
        })
        .catch(err => console.error(err));
    },

    onFileChange(event) {
      const file = event.target.files[0];
      if (file) {
        // Limite de 1 MB (base64 vira ~1.33 MB, seguro no post_max_size).
        const MAX_BYTES = 1 * 1024 * 1024;
        if (file.size > MAX_BYTES) {
          this.$refs.toast.show('A imagem deve ter no máximo 1 MB.', 'error', 2500);
          event.target.value = '';
          return;
        }
        this.avatarFile = file;
        const reader = new FileReader();
        reader.onload = (e) => {
          this.avatarPreview = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    },

    async uploadAvatar() {
      if (!this.avatarFile) return;
      
      const token = localStorage.getItem('api_token');
      const reader = new FileReader();
      
      return new Promise((resolve) => {
        reader.onload = async (e) => {
          const base64 = e.target.result;
          await fetch(`/api/usuarios/${this.id}/avatar`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({ avatar: base64 })
          });
          resolve();
        };
        reader.readAsDataURL(this.avatarFile);
      });
    },

    salvarUsuario() {
      this.loading = true;
      this.errors = [];
      this.erros = {};

      const url = this.id > 0 ? `/api/usuarios/${this.id}` : '/api/usuarios';
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

      const dataToSend = { ...this.form };
      if (this.id === 0 || !dataToSend.password) {
        delete dataToSend.password;
        delete dataToSend.password_confirmation;
      }

      console.log('Enviando requisição:', { url, method, dataToSend });

      fetch(url, {
        method,
        headers,
        body: JSON.stringify(dataToSend)
      })
        .then(async res => {
          if (res.status === 401) {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
            throw new Error('Não autorizado');
          }
          if (!res.ok) {
            const errorData = await res.json();
            this.erros = errorData.errors || {};
            throw new Error(errorData.message || 'Erro ao salvar usuário');
          }
          return res.json();
        })
        .then(async () => {
          if (this.id > 0 && this.avatarFile) {
            await this.uploadAvatar();
          }
          this.$refs.toast.show(this.id > 0 ? 'Usuário atualizado com sucesso!' : 'Usuário cadastrado com sucesso!');
          setTimeout(() => { window.location.href = '/usuarios'; }, 1500);
        })
        .catch(err => {
          console.error('Erro:', err);
          this.errors.push(err.message || 'Erro ao salvar usuário');
        })
        .finally(() => {
          this.loading = false;
        });
    },

    voltar() {
      window.location.href = '/usuarios';
    }
  }
}
</script>