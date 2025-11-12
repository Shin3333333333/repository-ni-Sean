import { ref } from 'vue';

const toasts = ref([]);
let idCounter = 1;

function pushToast(type, message, timeout = 3000) {
  const id = idCounter++;
  toasts.value.push({ id, type, message });
  setTimeout(() => {
    const idx = toasts.value.findIndex(t => t.id === id);
    if (idx !== -1) toasts.value.splice(idx, 1);
  }, timeout);
}

export function useToast() {
  return {
    toasts,
    success: (msg, t) => pushToast('success', msg, t),
    error: (msg, t) => pushToast('error', msg, t),
    info: (msg, t) => pushToast('info', msg, t),
  };
}