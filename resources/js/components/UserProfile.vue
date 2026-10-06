<!-- resources/js/components/UserProfile.vue -->
<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
      <h2 class="text-2xl font-bold text-white">Meu Perfil</h2>
    </div>

    <div class="p-8">
      <div v-if="successMessage" class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 rounded">
        <p class="text-green-600 text-sm">{{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded">
        <p class="text-red-600 text-sm">{{ errorMessage }}</p>
      </div>

      <form @submit.prevent="salvarPerfil" class="space-y-5">
        <div class="flex flex-col items-center mb-6">
          <div class="relative">
            <div class="w-32 h-32 bg-gray-100 rounded-full overflow-hidden">
              <img :src="avatarPreview" class="w-full h-full object-cover">
            </div>
            <button type="button" @click="$refs.fileInput.click()" 
              class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </button>
            <input type="file" @change="onFileChange" accept="image/*" class="hidden" ref="fileInput">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-gray-700 font-medium mb-1">Nome</label>
            <input v-model="form.name" type="text" class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-1">E-mail</label>
            <input v-model="form.email" type="email" class="w-full border rounded-lg p-2 bg-gray-100" disabled>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-1">Telefone</label>
            <input v-model="form.phone" type="tel" class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-1">Cargo</label>
            <input v-model="form.position" type="text" class="w-full border rounded-lg p-2">
          </div>
        </div>

        <div class="border-t pt-5 mt-5">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações Adicionais</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
              <label class="block text-gray-700 font-medium mb-1">Bio</label>
              <textarea v-model="profile.bio" rows="3" class="w-full border rounded-lg p-2"></textarea>
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-1">Data de Nascimento</label>
              <input v-model="profile.birth_date" type="date" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-1">CEP</label>
              <input v-model="profile.zip_code" type="text" class="w-full border rounded-lg p-2">
            </div>
            <div class="md:col-span-2">
              <label class="block text-gray-700 font-medium mb-1">Endereço</label>
              <input v-model="profile.address" type="text" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-1">Cidade</label>
              <input v-model="profile.city" type="text" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-1">Estado</label>
              <input v-model="profile.state" type="text" maxlength="2" class="w-full border rounded-lg p-2">
            </div>
          </div>
        </div>

        <div class="border-t pt-5 mt-5">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Redes Sociais</h3>
          <div class="grid grid-cols-1 gap-5">
            <div>
              <label class="block text-gray-700 font-medium mb-1">Facebook</label>
              <input v-model="profile.social_facebook" type="url" placeholder="https://facebook.com/seu-perfil" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-1">Instagram</label>
              <input v-model="profile.social_instagram" type="url" placeholder="https://instagram.com/seu-perfil" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-1">LinkedIn</label>
              <input v-model="profile.social_linkedin" type="url" placeholder="https://linkedin.com/in/seu-perfil" class="w-full border rounded-lg p-2">
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-4 pt-5">
          <button type="submit" :disabled="loading"
            class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition disabled:opacity-50">
            {{ loading ? 'Salvando...' : 'Salvar Alterações' }}
          </button>
        </div>
      </form>

      <!-- Modelo de Contrato (por usuario) -->
      <div class="border-t pt-5 mt-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-1">Modelo de Contrato</h3>
        <p class="text-gray-500 text-sm mb-4">
          Envie seu modelo de contrato em Word (.docx). Ele será usado ao gerar o
          contrato de uma locação. Use os marcadores
          <code>${'{'}cliente_nome{'}'}</code>, <code>${'{'}cliente_cpf{'}'}</code>,
          <code>${'{'}cliente_telefone{'}'}</code>, <code>${'{'}cliente_endereco{'}'}</code>,
          <code>${'{'}data_evento{'}'}</code>, <code>${'{'}retirada{'}'}</code>,
          <code>${'{'}devolucao{'}'}</code>, <code>${'{'}valor_total{'}'}</code>
          onde quiser que os dados sejam preenchidos.
        </p>

        <div v-if="contratoMsg" class="mb-3 p-3 bg-green-50 border-l-4 border-green-500 rounded">
          <p class="text-green-600 text-sm">{{ contratoMsg }}</p>
        </div>
        <div v-if="contratoErro" class="mb-3 p-3 bg-red-50 border-l-4 border-red-500 rounded">
          <p class="text-red-600 text-sm">{{ contratoErro }}</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
          <div class="flex-1">
            <span v-if="temModelo" class="inline-flex items-center gap-2 text-green-700 text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Modelo enviado.
            </span>
            <span v-else class="inline-flex items-center gap-2 text-amber-700 text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
              </svg>
              Você ainda não enviou um modelo.
            </span>
          </div>

          <input type="file" ref="contratoInput" accept=".docx" @change="onContratoSelected" class="hidden">
          <button type="button" @click="$refs.contratoInput.click()" :disabled="contratoUploading"
            class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
            {{ contratoUploading ? 'Enviando...' : (temModelo ? 'Substituir modelo' : 'Enviar modelo (.docx)') }}
          </button>
          <button v-if="temModelo" type="button" @click="baixarModelo"
            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            Baixar
          </button>
          <button v-if="temModelo" type="button" @click="removerModelo"
            class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition">
            Remover
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    id: { type: Number, required: true }
  },
  data() {
    return {
      form: {
        name: '',
        email: '',
        phone: '',
        position: ''
      },
      profile: {
        bio: '',
        birth_date: '',
        address: '',
        city: '',
        state: '',
        zip_code: '',
        social_facebook: '',
        social_instagram: '',
        social_linkedin: ''
      },
      avatarFile: null,
      avatarPreview: '',
      loading: false,
      successMessage: '',
      errorMessage: '',
      // Modelo de contrato do usuario
      temModelo: false,
      contratoUploading: false,
      contratoMsg: '',
      contratoErro: '',
    }
  },
  mounted() {
    this.fetchUserData();
    this.carregarStatusContrato();
  },
  methods: {
    headersAuth(extra = {}) {
      const token = localStorage.getItem('api_token');
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
      const h = { 'Accept': 'application/json', ...extra };
      if (token) h['Authorization'] = `Bearer ${token}`;
      if (csrf) h['X-CSRF-TOKEN'] = csrf;
      return h;
    },
    carregarStatusContrato() {
      fetch('/api/meu-contrato', { headers: this.headersAuth() })
        .then(res => res.json())
        .then(data => { this.temModelo = !!data.has_template; })
        .catch(() => {});
    },
    onContratoSelected(e) {
      this.contratoMsg = '';
      this.contratoErro = '';
      const file = e.target.files[0];
      if (!file) return;
      if (!file.name.toLowerCase().endsWith('.docx')) {
        this.contratoErro = 'O modelo deve ser um arquivo .docx (Word).';
        e.target.value = '';
        return;
      }
      this.contratoUploading = true;
      const fd = new FormData();
      fd.append('file', file);
      fetch('/api/meu-contrato', { method: 'POST', headers: this.headersAuth(), body: fd })
        .then(async res => {
          const d = await res.json().catch(() => ({}));
          if (!res.ok) throw new Error(d.message || 'Falha ao enviar o modelo.');
          this.temModelo = true;
          this.contratoMsg = d.message || 'Modelo enviado com sucesso.';
        })
        .catch(err => { this.contratoErro = err.message; })
        .finally(() => { this.contratoUploading = false; if (this.$refs.contratoInput) this.$refs.contratoInput.value = ''; });
    },
    baixarModelo() {
      fetch('/api/meu-contrato/download', { headers: this.headersAuth() })
        .then(res => { if (!res.ok) throw new Error(); return res.blob(); })
        .then(blob => {
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url; a.download = 'meu-modelo-contrato.docx';
          document.body.appendChild(a); a.click(); a.remove();
          window.URL.revokeObjectURL(url);
        })
        .catch(() => { this.contratoErro = 'Falha ao baixar o modelo.'; });
    },
    removerModelo() {
      if (!confirm('Remover seu modelo de contrato?')) return;
      fetch('/api/meu-contrato', { method: 'DELETE', headers: this.headersAuth() })
        .then(async res => {
          const d = await res.json().catch(() => ({}));
          if (!res.ok) throw new Error(d.message || 'Falha ao remover.');
          this.temModelo = false;
          this.contratoMsg = d.message || 'Modelo removido.';
        })
        .catch(err => { this.contratoErro = err.message; });
    },
    fetchUserData() {
      const token = localStorage.getItem('api_token');
      
      Promise.all([
        fetch(`/api/usuarios/${this.id}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        }).then(res => res.json()),
        fetch(`/api/usuarios/${this.id}/profile`, {
          headers: { 'Authorization': `Bearer ${token}` }
        }).then(res => res.json()).catch(() => ({}))
      ])
        .then(([user, profile]) => {
          this.form = {
            name: user.name,
            email: user.email,
            phone: user.phone || '',
            position: user.position || ''
          };
          this.profile = { ...this.profile, ...profile };
          this.avatarPreview = user.avatar ? user.avatar : `https://ui-avatars.com/api/?name=${user.name}&background=8b5cf6&color=fff&size=128`;
        })
        .catch(err => console.error(err));
    },

    onFileChange(event) {
      const file = event.target.files[0];
      if (file) {
        // Limite de 1 MB (base64 vira ~1.33 MB, seguro no post_max_size).
        if (file.size > 1 * 1024 * 1024) {
          this.errorMessage = 'A imagem deve ter no máximo 1 MB.';
          event.target.value = '';
          return;
        }
        this.errorMessage = '';
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
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      const reader = new FileReader();

      return new Promise((resolve, reject) => {
        reader.onload = async (e) => {
          const base64 = e.target.result;
          const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          };
          if (token) headers['Authorization'] = `Bearer ${token}`;
          if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

          try {
            const res = await fetch(`/api/usuarios/${this.id}/avatar`, {
              method: 'POST',
              headers,
              body: JSON.stringify({ avatar: base64 }),
            });
            if (!res.ok) {
              const d = await res.json().catch(() => ({}));
              reject(new Error(d.message || 'Falha ao salvar a imagem.'));
              return;
            }
            resolve();
          } catch (err) {
            reject(err);
          }
        };
        reader.readAsDataURL(this.avatarFile);
      });
    },

    async salvarPerfil() {
      this.loading = true;
      this.successMessage = '';
      this.errorMessage = '';
      
      const token = localStorage.getItem('api_token');

      try {
        // Atualizar dados básicos
        await fetch(`/api/usuarios/${this.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify(this.form)
        });

        // Atualizar perfil
        await fetch(`/api/usuarios/${this.id}/profile`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify(this.profile)
        });

        // Upload avatar se houver
        if (this.avatarFile) {
          await this.uploadAvatar();
        }

        this.successMessage = 'Perfil atualizado com sucesso!';
        setTimeout(() => { this.successMessage = ''; }, 3000);
        
        // Recarregar dados
        this.fetchUserData();
      } catch (error) {
        this.errorMessage = 'Erro ao salvar alterações. Tente novamente.';
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>