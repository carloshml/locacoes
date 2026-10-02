<template>
  <!-- Toast reutilizavel: aparece, faz fade e some sozinho.
       Uso:
         <toast-message ref="toast"></toast-message>
         this.$refs.toast.show('Cliente atualizado!')
         this.$refs.toast.show('Falha ao salvar', 'error')
  -->
  <transition name="toast-fade">
    <div
      v-if="visible"
      class="fixed top-6 right-6 z-50 px-5 py-3 rounded-lg shadow-lg text-white font-medium"
      :class="tipoClasse"
      role="status"
      aria-live="polite">
      {{ mensagem }}
    </div>
  </transition>
</template>

<script>
export default {
  name: 'ToastMessage',
  data() {
    return {
      visible: false,
      mensagem: '',
      tipo: 'success',
      timer: null,
    };
  },
  computed: {
    tipoClasse() {
      const mapa = {
        success: 'bg-green-600',
        error: 'bg-red-600',
        info: 'bg-blue-600',
      };
      return mapa[this.tipo] || mapa.success;
    },
  },
  methods: {
    // msg: texto a exibir | tipo: 'success' | 'error' | 'info' | duracao em ms
    show(msg, tipo = 'success', duracao = 1500) {
      this.mensagem = msg;
      this.tipo = tipo;
      this.visible = true;

      if (this.timer) clearTimeout(this.timer);
      this.timer = setTimeout(() => {
        this.visible = false;
      }, duracao);
    },
  },
  beforeUnmount() {
    if (this.timer) clearTimeout(this.timer);
  },
};
</script>

<style scoped>
/* Fade in/out suave (equivalente ao fadeIn/fadeOut) */
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: opacity 0.3s ease;
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
}
</style>
