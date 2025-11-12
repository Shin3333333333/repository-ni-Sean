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
          <select v-model="localForm.department" required>
            <option value="">Select Department</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
          </select>
        </div>

        <div class="form-group col-6">
          <label>Max Load:</label>
          <input v-model.number="localForm.max_load" type="number" min="1" required />
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
        <div class="form-group col-12">
          <label class="section-label">Time Unavailable:</label>
          
          <!-- Day Selection -->
          <div class="day-selection">
            <div class="day-grid">
              <div
                v-for="day in weekDays"
                :key="day.value"
                class="day-item"
                :class="{ 
                  'is-selected': selectedDays.includes(day.value),
                  'is-whole-day': wholeDayUnavailable.includes(day.value)
                }"
                @click="toggleDay(day.value)"
              >
                <span class="day-name">{{ day.name }}</span>
              </div>
            </div>
          </div>

          <!-- Per-day Time Range Editors -->
          <div v-if="selectedDays.length > 0" class="time-range-section">
            <div class="time-range-header">
              <span>Set time ranges per selected day (optional):</span>
            </div>
            <div class="unavailable-items">
              <div
                v-for="(item, index) in perDayItems"
                :key="item.dayValue"
                class="unavailable-item"
              >
                <div class="item-content">
                  <span class="day-name">{{ item.dayName }}</span>
                  <div class="checkbox-label">
                    <input type="checkbox" v-model="item.useSpecific" @change="setDayUseSpecific(item.dayValue, item.useSpecific)" />
                    <span>Use specific time range</span>
                  </div>
                  <div v-if="item.useSpecific" class="time-inputs">
                    <div class="time-input-group">
                      <label>From:</label>
                      <input type="time" class="time-input" v-model="item.start" @change="onDayTimeChanged(item.dayValue, item.start, item.end)" />
                    </div>
                    <div class="time-input-group">
                      <label>To:</label>
                      <input type="time" class="time-input" v-model="item.end" @change="onDayTimeChanged(item.dayValue, item.start, item.end)" />
                    </div>
                  </div>
                  <div class="time-info">Current: {{ item.isWholeDay ? '01:00 - 24:00' : `${item.start} - ${item.end}` }}</div>
                </div>
                <button type="button" class="remove-btn" @click="removeUnavailableByDay(item.dayValue)">×</button>
              </div>
            </div>
          </div>

          <!-- Selected Unavailable Times Display -->
          <div v-if="localForm.unavailableTimes.length" class="unavailable-display">
            <div class="unavailable-header">
              <span>Selected Unavailable Times:</span>
              <button type="button" class="clear-all-btn" @click="clearAllUnavailable">
                Clear All
              </button>
            </div>
            <div class="unavailable-items">
              <div
                v-for="(item, index) in localForm.unavailableTimes"
                :key="index"
                class="unavailable-item"
                :class="{ 'whole-day': item.isWholeDay }"
              >
                <div class="item-content">
                  <span class="day-name">{{ item.dayName }}</span>
                  <span class="time-info">
                    {{ item.isWholeDay ? '01:00 - 24:00' : `${item.start} - ${item.end}` }}
                  </span>
                </div>
                <button type="button" class="remove-btn" @click="removeUnavailable(index)">×</button>
              </div>
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
import api from "@/axios";
import departmentsData from "@ai/departments.json";
import { useToast } from '../../../../composables/useToast';
import { useLoading } from '../../../../composables/useLoading'; // ✅ import // ✅ import

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
    const { success, error } = useToast();
    return { showLoading, hideLoading, success, error };
  },

  data() {
    return {
      weekDays: [
        { name: "Mon", value: 1 },
        { name: "Tue", value: 2 },
        { name: "Wed", value: 3 },
        { name: "Thu", value: 4 },
        { name: "Fri", value: 5 },
        { name: "Sat", value: 6 }
      ],
      selectedDays: [],
      wholeDayUnavailable: [],
      localForm: {
        id: null,
        name: "",
        type: "",
        department: "",
        maxLoad: 1,
        status: "Active",
        unavailableTimes: [],
      },
    };
  },
  watch: {
    show: {
      immediate: true,
      handler(val) {
        if (val) {
          this.loadDepartments();

          // Deep clone to prevent prop mutation
          const formCopy = JSON.parse(JSON.stringify(this.form || {}));

          // Always parse from the source `time_unavailable` string if it exists
          const parsedItems = formCopy.time_unavailable
            ? this.parseTimeUnavailable(formCopy.time_unavailable)
            : [];
          
          const normalized = this.normalizeUnavailableItems(parsedItems);

          this.localForm = {
            id: formCopy.id || null,
            name: formCopy.name || "",
            type: formCopy.type || "",
            department: formCopy.department || "",
            max_load: Number(formCopy.max_load ?? 1),
            status: formCopy.status || "Active",
            unavailableTimes: normalized,
          };

          // Initialize day selection from existing data
          this.initializeDaySelection();
        }
      },
    },
  },
  methods: {
    closeModal() {
      this.$emit("update:show", false);
    },

    loadDepartments() {
      this.departments = Object.entries(departmentsData).map(([id, details]) => ({
        id,
        name: details.name,
      }));
    },
    
    // Parse existing time unavailable string
    parseTimeUnavailable(timeString) {
      if (!timeString) return [];
      
      const items = timeString.split(",").map(t => t.trim());
      return items.map(item => {
        const dayMatch = item.match(/^(\w+)$/);
        if (dayMatch) {
          const dayName = dayMatch[1];
          const dayValue = this.getDayValue(dayName);
          return {
            dayValue,
            dayName,
            isWholeDay: true,
            start: '01:00',
            end: '24:00',
            useSpecific: false
          };
        }
        
        const timeMatch = item.match(/^(\w+)\s+(\d{2}:\d{2})[–-](\d{2}:\d{2})$/);
        if (timeMatch) {
          const [, dayName, start, end] = timeMatch;
          const dayValue = this.getDayValue(dayName);
          const isWhole = start === '01:00' && end === '24:00';
          return {
            dayValue,
            dayName,
            isWholeDay: isWhole,
            start,
            end,
            useSpecific: !isWhole
          };
        }
        
        return null;
      }).filter(Boolean);
    },

    // Ensure items have required fields and defaults
    normalizeUnavailableItems(items) {
      return (items || []).map(it => {
        const isWholeDay = it.useSpecific === false ? true : !!it.isWholeDay;
        return {
          dayValue: it.dayValue,
          dayName: it.dayName ?? this.getDayName(it.dayValue),
          isWholeDay: isWholeDay,
          useSpecific: !isWholeDay,
          start: isWholeDay ? '01:00' : (it.start || '08:00'),
          end: isWholeDay ? '24:00' : (it.end || '17:00'),
        };
      });
    },
    
    // Get day value from day name
    getDayValue(dayName) {
      const mapping = { 
        "Mon": 1, "Tue": 2, "Wed": 3, "Thu": 4, 
        "Fri": 5, "Sat": 6, "Sun": 0,
        "Monday": 1, "Tuesday": 2, "Wednesday": 3, "Thursday": 4, 
        "Friday": 5, "Saturday": 6, "Sunday": 0
      };
      return mapping[dayName] !== undefined ? mapping[dayName] : 1;
    },
    
    // Get day name from day value
    getDayName(dayValue) {
      const mapping = { 0: "Sun", 1: "Mon", 2: "Tue", 3: "Wed", 4: "Thu", 5: "Fri", 6: "Sat" };
      return mapping[dayValue] || "Unknown";
    },
    
    // Initialize day selection from existing data
    initializeDaySelection() {
      this.selectedDays = [];
      this.wholeDayUnavailable = [];
      
      this.localForm.unavailableTimes.forEach(item => {
        if (typeof item === 'object' && item.dayValue !== undefined) {
          this.selectedDays.push(item.dayValue);
          if (item.isWholeDay) {
            this.wholeDayUnavailable.push(item.dayValue);
          }
        }
      });
    },

    // Computed-like helper to list per-day items in selected order
    
    // Toggle day selection
    toggleDay(dayValue) {
      const dayName = this.getDayName(dayValue);
      if (this.selectedDays.includes(dayValue)) {
        this.selectedDays = this.selectedDays.filter(d => d !== dayValue);
        this.wholeDayUnavailable = this.wholeDayUnavailable.filter(d => d !== dayValue);
        this.localForm.unavailableTimes = this.localForm.unavailableTimes.filter(it => it.dayValue !== dayValue);
      } else {
        this.selectedDays.push(dayValue);
        this.localForm.unavailableTimes.push({
          dayValue,
          dayName,
          isWholeDay: true,
          useSpecific: false,
          start: '01:00',
          end: '24:00'
        });
      }
    },

    // Set whether a day uses specific time or the default
    setDayUseSpecific(dayValue, useSpecific) {
      const item = this.localForm.unavailableTimes.find(it => it.dayValue === dayValue);
      if (!item) return;
      if (useSpecific) {
        item.isWholeDay = false;
        item.useSpecific = true;
        item.start = item.start || '08:00';
        item.end = item.end || '17:00';
        this.wholeDayUnavailable = this.wholeDayUnavailable.filter(d => d !== dayValue);
      } else {
        item.isWholeDay = true;
        item.useSpecific = false;
        item.start = '01:00';
        item.end = '24:00';
        if (!this.wholeDayUnavailable.includes(dayValue)) this.wholeDayUnavailable.push(dayValue);
      }
    },

    // Update a day's time and mark it as specific
    onDayTimeChanged(dayValue, start, end) {
      const item = this.localForm.unavailableTimes.find(it => it.dayValue === dayValue);
      if (!item) return;
      if (!start || !end) return;
      item.start = start;
      item.end = end;
      item.isWholeDay = false;
      item.useSpecific = true;
      this.wholeDayUnavailable = this.wholeDayUnavailable.filter(d => d !== dayValue);
    },

    // Remove day entry by day value
    removeUnavailableByDay(dayValue) {
      this.selectedDays = this.selectedDays.filter(d => d !== dayValue);
      this.wholeDayUnavailable = this.wholeDayUnavailable.filter(d => d !== dayValue);
      this.localForm.unavailableTimes = this.localForm.unavailableTimes.filter(it => it.dayValue !== dayValue);
    },

    // Clear all unavailable times
    clearAllUnavailable() {
      this.selectedDays = [];
      this.wholeDayUnavailable = [];
      this.localForm.unavailableTimes = [];
    },

    // Utility to format display strings
    formatDisplay(items) {
      return (items || []).map(i => `${i.dayName} ${i.start}–${i.end}`);
    },

    // Backward compat remove by index in display list
    removeUnavailable(index) {
      const item = this.localForm.unavailableTimes[index];
      if (!item) return;
      this.removeUnavailableByDay(item.dayValue);
    },
    async handleSubmit() {
      this.showLoading();
      try {
        const timeUnavailableString = this.perDayItems.map(item => {
          const dayName = item.dayName;
          if (item.isWholeDay) {
            return `${dayName} 01:00-24:00`;
          }
          return `${dayName} ${item.start}-${item.end}`;
        }).join(', ');

        const facultyData = {
          id: this.localForm.id,
          name: this.localForm.name,
          type: this.localForm.type,
          department: this.localForm.department,
          max_load: this.localForm.max_load,
          status: this.localForm.status,
          time_unavailable: timeUnavailableString,
        };

        this.$emit("submit", facultyData);
        this.success('Faculty saved successfully!');
        this.closeModal();
      } catch (err) {
        this.error('Failed to save faculty.');
        console.error("Error submitting faculty form:", err);
      } finally {
        this.hideLoading();
      }
    },
  },
  computed: {
    perDayItems() {
      const map = new Map(this.localForm.unavailableTimes.map(it => [it.dayValue, it]));
      return this.selectedDays.map(dv => map.get(dv)).filter(Boolean);
    }
  }
};
</script>


<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999 !important;
}

.modal-content {
  position: relative;
  max-width: 700px;
  width: 95%;
  padding: 20px;
  background: white;
  border-radius: 12px;
  overflow-y: auto;
  z-index: 10000 !important;
}

.section-label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
}

/* Basic form styling */
.grid-row {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 16px;
}

.col-6 {
  grid-column: span 6;
}

.col-12 {
  grid-column: span 12;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-group label {
  font-weight: 500;
  color: #606266;
  font-size: 14px;
}

.form-group input,
.form-group select {
  padding: 8px 12px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #409eff;
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.1);
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 20px;
}

.modal-buttons button {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.modal-buttons button:first-child {
  background: #909399;
  color: white;
}

.modal-buttons button:last-child {
  background: #409eff;
  color: white;
}

.modal-buttons button:hover {
  opacity: 0.8;
}

/* Enhanced Day Selection Styling */
.day-selection {
  margin-bottom: 20px;
}

.day-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  gap: 8px;
  margin-bottom: 16px;
}

.day-item {
  background: #fff;
  border: 2px solid #e4e7ed;
  border-radius: 8px;
  padding: 12px 8px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.day-item:hover {
  border-color: #409eff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.15);
}

.day-item.is-selected {
  border-color: #409eff;
  background: linear-gradient(135deg, #409eff 0%, #66b1ff 100%);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(64, 158, 255, 0.25);
}

.day-item.is-whole-day {
  border-color: #f56c6c;
  background: linear-gradient(135deg, #f56c6c 0%, #f78989 100%);
  color: white;
}

.day-item.is-whole-day.is-selected {
  border-color: #f56c6c;
  background: linear-gradient(135deg, #f56c6c 0%, #f78989 100%);
}

.day-name {
  font-weight: 600;
  font-size: 14px;
}

/* Time Range Section */
.time-range-section {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.time-range-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-weight: 500;
  color: #495057;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 14px;
  color: #606266;
}

.checkbox-label input[type="checkbox"] {
  margin-right: 8px;
  width: 16px;
  height: 16px;
  accent-color: #409eff;
}

.time-inputs {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.time-input-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.time-input-group label {
  font-size: 12px;
  font-weight: 500;
  color: #606266;
}

.time-input {
  padding: 8px 12px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.time-input:focus {
  outline: none;
  border-color: #409eff;
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.1);
}

.apply-time-btn {
  background: #67c23a;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  align-self: flex-end;
}

.apply-time-btn:hover {
  background: #5daf34;
  transform: translateY(-1px);
}

/* Unavailable Display */
.unavailable-display {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 16px;
}

.unavailable-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-weight: 600;
  color: #495057;
}

.clear-all-btn {
  background: #f56c6c;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 6px 12px;
  font-size: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.clear-all-btn:hover {
  background: #f24545;
}

.unavailable-items {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.unavailable-item {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  transition: all 0.2s;
  min-width: 200px;
}

.unavailable-item:hover {
  border-color: #409eff;
  box-shadow: 0 4px 8px rgba(64, 158, 255, 0.1);
}

.unavailable-item.whole-day {
  border-color: #f56c6c;
  background: linear-gradient(135deg, #fef0f0 0%, #fde2e2 100%);
}

.item-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.item-content .day-name {
  font-weight: 600;
  color: #409eff;
  font-size: 14px;
}

.unavailable-item.whole-day .item-content .day-name {
  color: #f56c6c;
}

.time-info {
  font-size: 12px;
  color: #606266;
  font-family: 'Courier New', monospace;
}

.remove-btn {
  background: transparent;
  border: none;
  color: #909399;
  font-size: 18px;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.remove-btn:hover {
  color: #f56c6c;
  background: #fef0f0;
}
</style>