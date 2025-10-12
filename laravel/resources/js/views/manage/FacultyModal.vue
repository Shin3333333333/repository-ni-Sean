<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <h3>{{ form.id ? 'Edit Faculty' : 'Add Faculty' }}</h3>

      <form @submit.prevent="handleSubmit" class="grid-row gap-4">
        <!-- Basic Info -->
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

        <!-- ✅ Replace checkbox grid with a safe day+time picker -->
        <div class="form-group col-3">
          <label class="section-label">Time Unavailable:</label>
          
          <div class="picker-row">
            <select v-model="newUnavailable.day">
              <option value="">Select Day</option>
              <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
            </select>

            <input v-model="newUnavailable.start" type="time" />
            <input v-model="newUnavailable.end" type="time" />
            
            <button type="button" class="add-btn" @click="addUnavailable">
              Add
            </button>
          </div>

          <div v-if="form.unavailableTimes && form.unavailableTimes.length" class="unavailable-list">
            <div
              v-for="(item, index) in form.unavailableTimes"
              :key="index"
              class="unavailable-item"
            >
              {{ item }}
              <button type="button" class="remove-btn" @click="removeUnavailable(index)">×</button>
            </div>
          </div>
        </div>

        <!-- Buttons -->
        <div class="modal-buttons col-12 flex justify-end gap-2">
          <button type="button" @click="closeModal">Cancel</button>
          <button type="submit">{{ form.id ? 'Update' : 'Add' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "FacultyModal",
  props: {
    show: Boolean,
    form: {
      type: Object,
      default: () => ({
        unavailableTimes: [],
      }),
    },
  },
  data() {
    return {
      days: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
      newUnavailable: { day: "", start: "", end: "" },
    };
  },
  methods: {
    closeModal() {
      this.$emit("update:show", false);
    },
    addUnavailable() {
      if (!Array.isArray(this.form.unavailableTimes)) {
        this.form.unavailableTimes = [];
      }
      if (this.newUnavailable.day && this.newUnavailable.start && this.newUnavailable.end) {
        const timeStr = `${this.newUnavailable.day} ${this.newUnavailable.start}–${this.newUnavailable.end}`;
        this.form.unavailableTimes.push(timeStr);
        this.newUnavailable = { day: "", start: "", end: "" };
      } else {
        alert("Please fill all fields before adding.");
      }
    },
    removeUnavailable(index) {
      this.form.unavailableTimes.splice(index, 1);
    },
    async handleSubmit() {
      try {
        const payload = {
          name: this.form.name,
          type: this.form.type,
          department: this.form.department,
          max_load: this.form.maxLoad,
          status: this.form.status,
          time_unavailable: (this.form.unavailableTimes || []).join(", "),
        };

        let res;
        if (this.form.id) {
          res = await axios.put(`/api/professors/${this.form.id}`, payload);
        } else {
          res = await axios.post("/api/professors", payload);
        }

        this.$emit("submit", res.data);
        this.closeModal();
      } catch (err) {
        console.error(err);
        alert("Failed to save faculty");
      }
    },
  },
};
</script>

<style scoped>
.modal-content {
  max-width: 700px;
  width: 95%;
  padding: 20px;
  background: white;
  border-radius: 12px;
  overflow-y: auto;
}

.section-label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
}

/* ✅ Day+Time Picker styling */
.picker-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 10px;
}

.picker-row select,
.picker-row input {
  padding: 6px 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
}

.add-btn {
  background: #4f46e5;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 6px 10px;
  cursor: pointer;
  transition: 0.2s;
}

.add-btn:hover {
  background: #3730a3;
}

.unavailable-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}

.unavailable-item {
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.remove-btn {
  background: transparent;
  border: none;
  color: #6b7280;
  font-size: 1rem;
  cursor: pointer;
}

.remove-btn:hover {
  color: #ef4444;
}
</style>
