<template>
  <div v-if="show" class="overlay" @click="$emit('cancel')">
    <div class="modal" @click.stop>
      <h3 class="title">{{ title || 'Confirm' }}</h3>
      <p class="message">{{ message }}</p>
      <div class="actions">
        <button class="btn" @click="$emit('cancel')">Cancel</button>
        <button class="btn primary" @click="$emit('confirm')">Confirm</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    show: Boolean,
    title: String,
    message: String,
  },
  emits: ['confirm','cancel']
};
</script>

<style scoped>
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 10002; }
.modal { background: #fff; width: 420px; border-radius: 12px; padding: 18px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.title { margin: 0 0 8px; font-weight: 700; font-size: 18px; }
.message { margin: 0 0 16px; color: #444; }
.actions { display: flex; justify-content: flex-end; gap: 10px; }
.btn { padding: 8px 12px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; cursor: pointer; }
.btn.primary { background: #111; color: #fff; border: none; }
</style>

