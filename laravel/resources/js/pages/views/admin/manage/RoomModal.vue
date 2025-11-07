<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <h3>{{ localForm.id ? 'Edit Room' : 'Add Room' }}</h3>

      <form @submit.prevent="handleSubmit" class="grid-row gap-4">
        <div class="form-group col-6">
          <label>Room Name:</label>
          <input v-model="localForm.name" type="text" required />
        </div>

        <div class="form-group col-6">
          <label>Type:</label>
          <select
            id="type"
            v-model="localForm.type"
            class="swal2-input"
            required
          >
            <option value="Lecture">Lecture</option>
            <option value="Laboratory">Laboratory</option>
          </select>
        </div>

        <div class="form-group col-6">
          <label>Status:</label>
          <select v-model="localForm.status" required>
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
          </select>
        </div>

        <div class="modal-buttons col-12 flex justify-end gap-2">
          <button type="button" @click="closeModal">Cancel</button>
          <button type="submit">{{ localForm.id ? 'Update' : 'Add' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import api from '~/axios';
import { useToast } from '../../../../composables/useToast';
import { useLoading } from '../../../../composables/useLoading';

export default {
  name: "RoomModal",
  props: {
    show: Boolean,
    form: {
      type: Object,
      default: () => ({ id: null, name: '', type: 'Lecture', status: 'Available' })
    }
  },
  setup() {
    const { show, hide } = useLoading();
    const { success, error } = useToast();
    return { showLoading: show, hideLoading: hide, success, error };
  },
  data() {
    return {
      localForm: {}
    };
  },
  watch: {
    form: {
      handler(newForm) {
        this.localForm = { ...newForm };
      },
      immediate: true,
      deep: true
    }
  },
  methods: {
    closeModal() {
      this.$emit("update:show", false);
    },
   async handleSubmit() {
    this.showLoading();
  try {
    const payload = {
      name: this.localForm.name?.trim() || "",
      type: this.localForm.type?.trim() || "",
      status: ["Available","Unavailable"].includes(this.localForm.status) ? this.localForm.status : "Available",
    };

    let res;
    if (this.localForm.id) {
      res = await api.put(`/rooms/${this.localForm.id}`, payload);
    } else {
      res = await api.post("/rooms", payload);
    }

    // ✅ Always emit consistent object (like FacultyModal)
    const savedRoom = {
      id: res.data.data?.id || res.data.id,
      name: res.data.data?.name || res.data.name,
      type: res.data.data?.type || res.data.type,
      status: res.data.data?.status || res.data.status,
    };

    this.$emit("submit", savedRoom);
    this.success('Room updated successfully!');
    this.closeModal();

  } catch (err) {
    this.error('Failed to update room. Check required fields!');
    console.error("Failed to save room:", err.response?.data || err);
  } finally {
    this.hideLoading();
  }
}

  }
};
</script>