<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <h3>{{ form.id ? 'Edit Room' : 'Add Room' }}</h3>

      <form @submit.prevent="handleSubmit" class="grid-row gap-4">
        <div class="form-group col-6">
          <label>Room Name:</label>
          <input v-model="form.name" type="text" required />
        </div>

        <div class="form-group col-6">
          <label>Capacity:</label>
          <input v-model.number="form.capacity" type="number" min="1" required />
        </div>

        <div class="form-group col-6">
          <label>Type:</label>
          <input v-model="form.type" type="text" required />
        </div>

        <div class="form-group col-6">
          <label>Status:</label>
          <select v-model="form.status" required>
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
          </select>
        </div>

        <div class="modal-buttons col-12 flex justify-end gap-2">
          <button type="button" @click="closeModal">Cancel</button>
          <button type="submit">{{ form.id ? 'Update' : 'Add' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: "RoomModal",
  props: {
    show: Boolean,
    form: Object
  },
  methods: {
    closeModal() {
      this.$emit("update:show", false);
    },
    async handleSubmit() {
  try {
    const payload = {
      name: this.form.name,
      capacity: this.form.capacity,
      type: this.form.type,
      status: this.form.status,
    };

    let res;
    if (this.form.id) {
      res = await axios.put(`/api/rooms/${this.form.id}`, payload);
    } else {
      res = await axios.post("/api/rooms", payload);
    }

    // Emit the saved object from API, not the form
    this.$emit("submit", res.data);
    this.closeModal();
  } catch (err) {
    console.error(err.response?.data || err);
    alert("Failed to save room");
  }
}

  }
};
</script>
