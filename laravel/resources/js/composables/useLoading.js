import { ref } from 'vue';

export const isLoading = ref(false);

export function useLoading() {
  const show = () => isLoading.value = true;
  const hide = () => isLoading.value = false;
  return { isLoading, show, hide };
}
