<template>
  <div v-if="show" class="modal-overlay" @click="$emit('update:show', false)">
    <div class="modal-content" @click.stop>
      <h3>{{ form.id ? 'Edit Faculty' : 'Add Faculty' }}</h3>
      <form @submit.prevent="submitForm" class="grid-row gap-4">
        <div class="form-group col-6">
          <label>Faculty Name:</label>
          <input v-model="form.name" type="text" required />
        </div>
        <div class="form-group col-6">
          <label>Type:</label>
          <select v-model="form.type" required>
            <option value="">Select Type</option>
            <option value="Full-time">Full-time</option>
            <option value="Part-time">Part-time</option>
          </select>
        </div>
        <div class="form-group col-6">
          <label>Department:</label>
          <input v-model="form.department" type="text" required />
        </div>
        <div class="form-group col-6">
          <label>Max Load:</label>
          <input v-model.number="form.maxLoad" type="number" min="1" required />
        </div>
        <div class="form-group col-6">
          <label>Status:</label>
          <select v-model="form.status" required>
            <option value="">Select Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
        <div class="form-group col-6">
          <label>Time Unavailable:</label>
          <div class="grid-row gap-2">
            <select class="col-6" v-model="form.day">
              <option value="">Select Day</option>
              <option value="M">Monday</option>
              <option value="T">Tuesday</option>
              <option value="W">Wednesday</option>
              <option value="Th">Thursday</option>
              <option value="F">Friday</option>
              <option value="Sat">Saturday</option>
            </select>
            <input class="col-6" v-model="form.time" type="text" placeholder="e.g., 1-3PM" />
          </div>
        </div>
        <div class="modal-buttons col-12 flex justify-end gap-2">
          <button type="button" @click="$emit('update:show', false)">Cancel</button>
          <button type="submit">{{ form.id ? 'Update' : 'Add' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: "FacultyModal",
  props: {
    show: Boolean,
    form: Object
  },
  methods: {
    async submitForm() {
      try {
        const payload = {
          name: this.form.name,
          type: this.form.type,
          department: this.form.department,
          max_load: this.form.maxLoad,
          status: this.form.status,
          time_unavailable: `${this.form.day} ${this.form.time}`.trim(),
        };

        let res;
        if (this.form.id) {
          res = await axios.put(`/api/professors/${this.form.id}`, payload);
        } else {
          res = await axios.post("/api/professors", payload);
        }

        this.$emit("submit", res.data);
        this.$emit("update:show", false);
      } catch (err) {
        console.error(err);
        alert("Failed to save faculty");
      }
    }
  }
};
</script>
