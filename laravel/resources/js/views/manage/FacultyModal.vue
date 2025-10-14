<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <h3>{{ localForm.id ? 'Edit Faculty' : 'Add Faculty' }}</h3>

      <form @submit.prevent="handleSubmit" class="grid-row gap-4">
        <!-- Basic Info -->
        <div class="form-group col-6">
          <label>Faculty Name:</label>
          <input v-model="localForm.name" type="text" required />
        </div>

        <div class="form-group col-6">
          <label>Type:</label>
          <select v-model="localForm.type" required>
            <option value="">Select Type</option>
            <option value="Full-time">Full-time</option>
            <option value="Part-time">Part-time</option>
          </select>
        </div>

        <div class="form-group col-6">
          <label>Department:</label>
          <input v-model="localForm.department" type="text" required />
        </div>

        <div class="form-group col-6">
          <label>Max Load:</label>
          <input v-model.number="localForm.maxLoad" type="number" min="1" required />
        </div>

        <div class="form-group col-6">
          <label>Status:</label>
          <select v-model="localForm.status" required>
            <option value="">Select Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>

        <!-- Time Unavailable Picker -->
        <div class="form-group col-6">
          <label class="section-label">Time Unavailable:</label>

          <div class="picker-row">
            <select v-model="newUnavailable.day">
              <option value="">Select Day</option>
              <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
            </select>

            <input v-model="newUnavailable.start" type="time" />
            <input v-model="newUnavailable.end" type="time" />

            <button type="button" class="add-btn" @click="addUnavailable">Add</button>
          </div>

          <div v-if="localForm.unavailableTimes.length" class="unavailable-list">
            <div
              v-for="(item, index) in localForm.unavailableTimes"
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
          <button type="submit">{{ localForm.id ? 'Update' : 'Add' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { useLoading } from '../../composables/useLoading'; // ✅ import

export default {
  name: "FacultyModal",
  props: {
    show: Boolean,
    form: {
      type: Object,
      default: () => ({ unavailableTimes: [] }),
    },
  },
setup() {
  const { show: showLoading, hide: hideLoading } = useLoading();
  return { showLoading, hideLoading };
},

  data() {
  return {
    localForm: {
      id: null,
      name: "",
      type: "Full-time",
      department: "",
      maxLoad: 1,
      status: "Active",
      unavailableTimes: [],
    },
    newUnavailable: { day: "", start: "", end: "" },
    days: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
  };
},
 watch: {
  form: {
    immediate: true,
    handler(val) {
      if (val) {
        this.localForm = {
          id: val.id || null,
          name: val.name || "",
          type: val.type || "Full-time",
          department: val.department || "",
          maxLoad: Number(val.max_load ?? val.maxLoad ?? 1),
          status: val.status || "Active",
          unavailableTimes: Array.isArray(val.unavailableTimes)
            ? [...val.unavailableTimes]
            : val.time_unavailable
              ? val.time_unavailable.split(",").map(t => t.trim())
              : [],
        };
      }
    },
  },
},

  methods: {
    closeModal() {
      this.$emit("update:show", false);
    },
    addUnavailable() {
      if (!this.newUnavailable.day || !this.newUnavailable.start || !this.newUnavailable.end) {
        alert("Please fill all fields before adding.");
        return;
      }
      const timeStr = `${this.newUnavailable.day} ${this.newUnavailable.start}–${this.newUnavailable.end}`;
      this.localForm.unavailableTimes.push(timeStr);
      this.newUnavailable = { day: "", start: "", end: "" };
    },
    removeUnavailable(index) {
      this.localForm.unavailableTimes.splice(index, 1);
    },
    async handleSubmit() {
      try {
       this.showLoading(); // show global loading

        const payload = {
          name: this.localForm.name,
          type: this.localForm.type,
          department: this.localForm.department,
          max_load: this.localForm.maxLoad,
          status: this.localForm.status,
          time_unavailable: this.localForm.unavailableTimes.join(", "),
        };

        let res;
        if (this.localForm.id) {
          res = await axios.put(`/api/professors/${this.localForm.id}`, payload);
        } else {
          res = await axios.post("/api/professors", payload);
        }

        const savedFaculty = {
          id: res.data.data?.id || res.data.id,
          name: res.data.data?.name || res.data.name,
          type: res.data.data?.type || res.data.type,
          department: res.data.data?.department || res.data.department,
          maxLoad: res.data.data?.max_load || res.data.max_load,
          status: res.data.data?.status || res.data.status,
          unavailableTimes: (res.data.data?.time_unavailable || res.data.time_unavailable || "")
                              .split(",")
                              .map(s => s.trim())
        };


        this.$emit("submit", savedFaculty);
        this.closeModal();
      } catch (err) {
        console.error(err);
        alert("Failed to save faculty");
      } finally {
         this.hideLoading(); // hide global loading
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
