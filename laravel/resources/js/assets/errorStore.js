import { reactive } from "vue";

export const errorStore = reactive({
  errors: [],

  setErrors(newErrors) {
    
    this.errors = (newErrors || []).map(e => ({ ...e }));
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
