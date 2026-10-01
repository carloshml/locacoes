<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <!-- Upload -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-5">
      <div class="flex flex-col sm:flex-row items-center gap-4">
        <input
          ref="fileInput"
          type="file"
          @change="onFileSelected"
          class="flex-1 w-full text-sm text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-white file:text-purple-700 file:font-semibold hover:file:bg-purple-50">
        <button
          @click="upload"
          :disabled="!selectedFile || uploading"
          class="w-full sm:w-auto bg-white text-purple-700 font-semibold px-6 py-2 rounded-lg hover:bg-purple-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
          {{ uploading ? `Enviando... ${progress}%` : 'Enviar' }}
        </button>
      </div>
      <p v-if="erro" class="text-red-100 text-sm mt-2">{{ erro }}</p>
      <p v-if="uploading" class="mt-2">
        <span class="block w-full bg-purple-900/40 rounded-full h-2 overflow-hidden">
          <span class="block bg-white h-2 transition-all" :style="{ width: progress + '%' }"></span>
        </span>
      </p>
    </div>

    <!-- Lista -->
    <div class="p-6">
      <div v-if="loading" class="text-center py-10 text-gray-500">Carregando arquivos...</div>

      <div v-else-if="files.length === 0" class="text-center py-10 text-gray-500">
        Nenhum arquivo enviado ainda.
      </div>

      <table v-else class="w-full">
        <thead>
          <tr class="text-left text-sm font-semibold text-gray-600 border-b">
            <th class="py-3 px-2">Nome</th>
            <th class="py-3 px-2 w-28">Tamanho</th>
            <th class="py-3 px-2 w-40 text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="f in files" :key="f.id" class="border-b hover:bg-gray-50">
            <td class="py-3 px-2 text-gray-800 break-all">{{ f.name }}</td>
            <td class="py-3 px-2 text-gray-600 text-sm">{{ f.size_human }}</td>
            <td class="py-3 px-2 text-right space-x-2">
              <button @click="download(f)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Baixar</button>
              <button @click="remover(f)" class="text-red-600 hover:text-red-800 text-sm font-medium">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      files: [],
      selectedFile: null,
      uploading: false,
      progress: 0,
      loading: true,
      erro: '',
    };
  },
  mounted() {
    this.carregar();
  },
  methods: {
    authHeaders() {
      const token = localStorage.getItem('api_token');
      const headers = { 'Accept': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;
      return headers;
    },
    carregar() {
      this.loading = true;
      fetch('/api/arquivos', { headers: this.authHeaders() })
        .then(res => this.handleAuth(res))
        .then(data => { this.files = data || []; })
        .catch(() => { this.erro = 'Falha ao carregar arquivos.'; })
        .finally(() => { this.loading = false; });
    },
    onFileSelected(e) {
      this.erro = '';
      this.selectedFile = e.target.files[0] || null;
      // Limite de 100 MB no front (checagem real e no back-end)
      if (this.selectedFile && this.selectedFile.size > 100 * 1024 * 1024) {
        this.erro = 'O arquivo excede o limite de 100 MB.';
        this.selectedFile = null;
        this.$refs.fileInput.value = '';
      }
    },
    upload() {
      if (!this.selectedFile) return;
      this.uploading = true;
      this.progress = 0;
      this.erro = '';

      const formData = new FormData();
      formData.append('file', this.selectedFile);

      // XMLHttpRequest para acompanhar o progresso do upload
      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/api/arquivos');
      const token = localStorage.getItem('api_token');
      if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`);
      xhr.setRequestHeader('Accept', 'application/json');

      xhr.upload.onprogress = (ev) => {
        if (ev.lengthComputable) {
          this.progress = Math.round((ev.loaded / ev.total) * 100);
        }
      };

      xhr.onload = () => {
        this.uploading = false;
        if (xhr.status === 401) { localStorage.removeItem('api_token'); window.location.href = '/login'; return; }
        if (xhr.status >= 200 && xhr.status < 300) {
          this.selectedFile = null;
          this.$refs.fileInput.value = '';
          this.carregar();
        } else {
          try { this.erro = JSON.parse(xhr.responseText).message || 'Falha no envio.'; }
          catch { this.erro = 'Falha no envio.'; }
        }
      };
      xhr.onerror = () => { this.uploading = false; this.erro = 'Erro de rede no envio.'; };
      xhr.send(formData);
    },
    download(f) {
      fetch(`/api/arquivos/${f.id}/download`, { headers: this.authHeaders() })
        .then(res => {
          if (!res.ok) throw new Error();
          return res.blob();
        })
        .then(blob => {
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = f.name;
          document.body.appendChild(a);
          a.click();
          a.remove();
          window.URL.revokeObjectURL(url);
        })
        .catch(() => { this.erro = 'Falha ao baixar o arquivo.'; });
    },
    remover(f) {
      if (!confirm(`Excluir "${f.name}"? Esta ação não pode ser desfeita.`)) return;
      fetch(`/api/arquivos/${f.id}`, { method: 'DELETE', headers: this.authHeaders() })
        .then(res => this.handleAuth(res))
        .then(() => this.carregar())
        .catch(() => { this.erro = 'Falha ao excluir o arquivo.'; });
    },
    handleAuth(res) {
      if (res.status === 401) { localStorage.removeItem('api_token'); window.location.href = '/login'; return Promise.reject(); }
      return res.json();
    },
  },
};
</script>
