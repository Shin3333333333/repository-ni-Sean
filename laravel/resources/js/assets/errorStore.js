import { reactive } from "vue";

export const errorStore = reactive({
  errors: [],

  setErrors(newErrors) {
    
    this.errors = (newErrors || []).map(e => ({ ...e }));
  },

  updateError(updatedError) {
    const index = this.errors.findIndex(e => e.id === updatedError.id);
    if (index !== -1) {
      this.errors[index] = { ...this.errors[index], ...updatedError };
    }
  },

  markResolved(id) {
    const idx = this.errors.findIndex(e => e.id === id);
    if (idx !== -1) {
      
      this.errors[idx].resolved = true;
    }
  },

  get unresolvedCount() {
    return this.errors.filter(e => !e.resolved).length;
  }
});