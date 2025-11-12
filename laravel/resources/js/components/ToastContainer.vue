<template>
  <div class="toast-container">
    <div v-for="t in toasts" :key="t.id" class="toast" :class="t.type">
      {{ t.message }}
    </div>
  </div>
</template>

<script>
import { useToast } from '../composables/useToast';
export default {
  setup() {
    const { toasts } = useToast();
    return { toasts };
  }
};
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 10001;
}
.toast {
  min-width: 220px;
  max-width: 360px;
  padding: 10px 12px;
  border-radius: 8px;
  font-weight: 500;
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
  border: 1px solid #e5e7eb;
}
.toast.success { background: #eafaf1; color: #1e824c; }
.toast.error { background: #fdecea; color: #b71c1c; }
.toast.info { background: #eff6ff; color: #1d4ed8; }
</style>