<template>
  <div v-if="show" class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Create Temporary Faculty Account</h2>
        <button @click="close" class="close-btn">&times;</button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="submitForm">
          <div class="form-group">
            <label for="email">Faculty Email</label>
            <input type="email" id="email" v-model="email" required>
          </div>
          <div class="form-actions">
            <button type="button" @click="close" class="cancel-btn">Cancel</button>
            <button type="submit" class="submit-btn">Create Account</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../../../../axios';
import { useToast } from '../../../../composables/useToast';
import { useLoading } from '../../../../composables/useLoading';

export default {
  props: {
    show: Boolean,
  },
  emits: ['update:show', 'submit'],
  setup(props, { emit }) {
    const { success, error } = useToast();
    const { show, hide } = useLoading();

    const close = () => {
      emit('update:show', false);
    };

    return { close, success, error, showLoading: show, hideLoading: hide };
  },
  data() {
    return {
      email: '',
    };
  },
  methods: {
    async submitForm() {
      this.showLoading();
      try {
        const response = await axios.post('/faculty/create-temporary-account', {
          email: this.email,
        });
        this.success(response.data.message);
        this.$emit('submit');
        this.close();
      } catch (err) {
        this.error(err.response?.data?.message || 'Failed to create account');
      } finally {
        this.hideLoading();
      }
    },
  },
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 5000;
}

.modal-content {
  background: #fff;
  padding: 2rem;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding-bottom: 1rem;
}

.modal-header h2 {
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.modal-body {
  padding-top: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}

.cancel-btn,
.submit-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.cancel-btn {
  background-color: #f0f0f0;
}

.submit-btn {
  background-color: #2563eb;
  color: #fff;
}
</style>