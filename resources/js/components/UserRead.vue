<template>
  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
      <h2 class="text-2xl font-bold text-white">Detalhes do Usuário</h2>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="user" class="p-8">
      <div class="flex flex-col md:flex-row gap-8">
        <!-- Avatar -->
        <div class="flex flex-col items-center">
          <div class="w-32 h-32 bg-gray-100 rounded-full overflow-hidden">
            <img :src="avatarUrl" class="w-full h-full object-cover">
          </div>
          <div class="mt-4 text-center">
            <div v-html="user.role_badge"></div>
            <div class="mt-2" v-html="user.status_badge"></div>
          </div>
        </div>

        <!-- Informações -->
        <div class="flex-1">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-gray-500 text-sm">Nome completo</label>
              <p class="text-gray-900 font-medium">{{ user.name }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">E-mail</label>
              <p class="text-gray-900">{{ user.email }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">Telefone</label>
              <p class="text-gray-900">{{ user.phone || 'Não informado' }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">Cargo</label>
              <p class="text-gray-900">{{ user.position || 'Não informado' }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">Cadastrado em</label>
              <p class="text-gray-900">{{ formatDate(user.created_at) }}</p>
            </div>
            <div>
              <label class="text-gray-500 text-sm">Último acesso</label>
              <p class="text-gray-900">{{ formatDate(user.last_login_at) || 'Nunca' }}</p>
            </div>
          </div>

          <!-- Perfil adicional -->
          <div v-if="profile" class="mt-6 pt-6 border-t">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações Adicionais</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-gray-500 text-sm">Bio</label>
                <p class="text-gray-900">{{ profile.bio || 'Não informado' }}</p>
              </div>
              <div>
                <label class="text-gray-500 text-sm">Data de Nascimento</label>
                <p class="text-gray-900">{{ profile.birth_date ? formatDate(profile.birth_date) : 'Não informado' }}</p>
              </div>
              <div class="md:col-span-2">
                <label class="text-gray-500 text-sm">Endereço</label>
                <p class="text-gray-900">{{ profile.address ? `${profile.address}, ${profile.city} - ${profile.state}` : 'Não informado' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-4 mt-8 pt-6 border-t">
        <a :href="`/usuarios/${user.id}/edit`" class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600">
          Editar
        </a>
        <a href="/usuarios" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
          Voltar
        </a>
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
      user: null,
      profile: null,
      loading: true
    }
  },
  computed: {
    avatarUrl() {
      // avatar e armazenado como base64 (data:image/...). Usa direto.
      if (this.user?.avatar) {
        return this.user.avatar;
      }
      return this.user ? `https://ui-avatars.com/api/?name=${this.user.name}&background=3b82f6&color=fff&size=128` : '';
    }
  },
  mounted() {
    this.fetchUser();
  },
  methods: {
    fetchUser() {
      const token = localStorage.getItem('api_token');
      Promise.all([
        fetch(`/api/usuarios/${this.id}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        }).then(res => res.json()),
        fetch(`/api/usuarios/${this.id}/profile`, {
          headers: { 'Authorization': `Bearer ${token}` }
        }).then(res => res.json()).catch(() => null)
      ])
        .then(([user, profile]) => {
          this.user = user;
          this.profile = profile;
          this.loading = false;
        })
        .catch(err => {
          console.error(err);
          this.loading = false;
        });
    },
    formatDate(date) {
      if (!date) return '';
      return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
}
</script>