<template>
  <div v-if="isLoading" class="loading-overlay">
    <div class="loading-modal">
      <div class="spinner"></div>
      <p>Loading...</p>
    </div>
  </div>
</template>

<script>
import { isLoading } from '../composables/useLoading';

export default {
  setup() {
    return { isLoading };
  }
};
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-modal {
  background-color: #fff;
  padding: 30px 40px;
  border-radius: 10px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.loading-modal p {
  margin-top: 15px;
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid #f3f3f3;
  border-top: 5px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
