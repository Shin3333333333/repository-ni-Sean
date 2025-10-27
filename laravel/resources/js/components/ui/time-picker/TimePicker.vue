<template>
  <div class="time-picker-container">
    <div class="time-picker-input" :class="{ 'is-range': isRange, 'is-focused': isFocused, 'arrow-control': arrowControl }" @click="togglePicker">
      <div v-if="isRange" class="time-range-input">
        <input
          ref="startInput"
          v-model="startTimeDisplay"
          :placeholder="startPlaceholder"
          readonly
          class="time-input"
          @focus="handleStartFocus"
          @blur="handleStartBlur"
        />
        <span class="range-separator">{{ rangeSeparator }}</span>
        <input
          ref="endInput"
          v-model="endTimeDisplay"
          :placeholder="endPlaceholder"
          readonly
          class="time-input"
          @focus="handleEndFocus"
          @blur="handleEndBlur"
        />
      </div>
      <div v-else class="single-time-input">
        <input
          ref="singleInput"
          v-model="singleTimeDisplay"
          :placeholder="placeholder"
          readonly
          class="time-input"
          @focus="handleSingleFocus"
          @blur="handleSingleBlur"
        />
      </div>
      <div class="time-picker-icon">
        <svg v-if="!arrowControl" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12,6 12,12 16,14"/>
        </svg>
        <div v-else class="arrow-controls">
          <svg class="arrow-up" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18,15 12,9 6,15"/>
          </svg>
          <svg class="arrow-down" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6,9 12,15 18,9"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Time Picker Dropdown -->
    <div v-if="showPicker" class="time-picker-dropdown" :class="{ 'is-range': isRange }">
      <!-- Day Picker Section -->
      <div v-if="showDayPicker" class="day-picker-section">
        <div class="day-picker-header">
          <span>Select Days</span>
          <button type="button" class="day-picker-toggle" @click="toggleAllDays">
            {{ allDaysSelected ? 'Clear All' : 'Select All' }}
          </button>
        </div>
        <div class="day-picker-content">
          <div
            v-for="(day, index) in weekDays"
            :key="day.value"
            class="day-picker-item"
            :class="{ 'is-selected': selectedDays.includes(day.value), 'is-unavailable': unavailableDays.includes(day.value) }"
            @click="toggleDay(day.value)"
          >
            <span class="day-name">{{ day.name }}</span>
            <span class="day-status">{{ unavailableDays.includes(day.value) ? 'Unavailable' : 'Available' }}</span>
          </div>
        </div>
      </div>

      <!-- Time Picker Section -->
      <div v-if="!showDayPicker" class="time-picker-main">
        <div v-if="isRange" class="time-picker-range">
          <!-- Start Time Picker -->
          <div class="time-picker-section">
            <div class="time-picker-header">Start Time</div>
            <div class="time-picker-content">
              <div class="time-picker-hours">
                <div class="time-picker-scroll" ref="startHoursScroll">
                  <div
                    v-for="hour in hours"
                    :key="`start-${hour}`"
                    class="time-picker-item"
                    :class="{ 'is-selected': startTime.hour === hour }"
                    @click="selectStartHour(hour)"
                  >
                    {{ hour.toString().padStart(2, '0') }}
                  </div>
                </div>
              </div>
              <div class="time-picker-minutes">
                <div class="time-picker-scroll" ref="startMinutesScroll">
                  <div
                    v-for="minute in minutes"
                    :key="`start-${minute}`"
                    class="time-picker-item"
                    :class="{ 'is-selected': startTime.minute === minute }"
                    @click="selectStartMinute(minute)"
                  >
                    {{ minute.toString().padStart(2, '0') }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- End Time Picker -->
          <div class="time-picker-section">
            <div class="time-picker-header">End Time</div>
            <div class="time-picker-content">
              <div class="time-picker-hours">
                <div class="time-picker-scroll" ref="endHoursScroll">
                  <div
                    v-for="hour in hours"
                    :key="`end-${hour}`"
                    class="time-picker-item"
                    :class="{ 'is-selected': endTime.hour === hour }"
                    @click="selectEndHour(hour)"
                  >
                    {{ hour.toString().padStart(2, '0') }}
                  </div>
                </div>
              </div>
              <div class="time-picker-minutes">
                <div class="time-picker-scroll" ref="endMinutesScroll">
                  <div
                    v-for="minute in minutes"
                    :key="`end-${minute}`"
                    class="time-picker-item"
                    :class="{ 'is-selected': endTime.minute === minute }"
                    @click="selectEndMinute(minute)"
                  >
                    {{ minute.toString().padStart(2, '0') }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Single Time Picker -->
        <div v-else class="time-picker-single">
          <div class="time-picker-content">
            <div class="time-picker-hours">
              <div class="time-picker-scroll" ref="singleHoursScroll">
                <div
                  v-for="hour in hours"
                  :key="`single-${hour}`"
                  class="time-picker-item"
                  :class="{ 'is-selected': singleTime.hour === hour }"
                  @click="selectSingleHour(hour)"
                >
                  {{ hour.toString().padStart(2, '0') }}
                </div>
              </div>
            </div>
            <div class="time-picker-minutes">
              <div class="time-picker-scroll" ref="singleMinutesScroll">
                <div
                  v-for="minute in minutes"
                  :key="`single-${minute}`"
                  class="time-picker-item"
                  :class="{ 'is-selected': singleTime.minute === minute }"
                  @click="selectSingleMinute(minute)"
                >
                  {{ minute.toString().padStart(2, '0') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="time-picker-actions">
        <button type="button" class="time-picker-btn time-picker-btn-cancel" @click="cancelPicker">
          Cancel
        </button>
        <button v-if="showDayPicker" type="button" class="time-picker-btn time-picker-btn-secondary" @click="switchToTimePicker">
          Next: Set Time
        </button>
        <button v-if="!showDayPicker" type="button" class="time-picker-btn time-picker-btn-secondary" @click="switchToDayPicker">
          Back: Select Days
        </button>
        <button type="button" class="time-picker-btn time-picker-btn-confirm" @click="confirmPicker">
          OK
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: [Date, Array, Object],
    default: null
  },
  isRange: {
    type: Boolean,
    default: false
  },
  rangeSeparator: {
    type: String,
    default: 'To'
  },
  startPlaceholder: {
    type: String,
    default: 'Start time'
  },
  endPlaceholder: {
    type: String,
    default: 'End time'
  },
  placeholder: {
    type: String,
    default: 'Select time'
  },
  arrowControl: {
    type: Boolean,
    default: false
  },
  showDayPicker: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

// Refs
const startInput = ref(null)
const endInput = ref(null)
const singleInput = ref(null)
const startHoursScroll = ref(null)
const startMinutesScroll = ref(null)
const endHoursScroll = ref(null)
const endMinutesScroll = ref(null)
const singleHoursScroll = ref(null)
const singleMinutesScroll = ref(null)

// State
const showPicker = ref(false)
const isFocused = ref(false)
const showDayPicker = ref(props.showDayPicker)

// Time data
const hours = Array.from({ length: 24 }, (_, i) => i)
const minutes = Array.from({ length: 60 }, (_, i) => i)

// Day data
const weekDays = [
  { name: 'Monday', value: 1 },
  { name: 'Tuesday', value: 2 },
  { name: 'Wednesday', value: 3 },
  { name: 'Thursday', value: 4 },
  { name: 'Friday', value: 5 },
  { name: 'Saturday', value: 6 },
  { name: 'Sunday', value: 0 }
]

// Day selection state
const selectedDays = ref([])
const unavailableDays = ref([])

// Time objects
const startTime = ref({ hour: 8, minute: 0 })
const endTime = ref({ hour: 9, minute: 0 })
const singleTime = ref({ hour: 8, minute: 0 })

// Display values
const startTimeDisplay = ref('')
const endTimeDisplay = ref('')
const singleTimeDisplay = ref('')

// Computed
const allDaysSelected = computed(() => {
  return weekDays.every(day => selectedDays.value.includes(day.value))
})

const formatTime = (time) => {
  return `${time.hour.toString().padStart(2, '0')}:${time.minute.toString().padStart(2, '0')}`
}

const parseTime = (timeString) => {
  if (!timeString) return { hour: 0, minute: 0 }
  const [hour, minute] = timeString.split(':').map(Number)
  return { hour: hour || 0, minute: minute || 0 }
}

// Methods
const togglePicker = () => {
  showPicker.value = !showPicker.value
  if (showPicker.value) {
    nextTick(() => {
      scrollToSelectedTime()
    })
  }
}

const scrollToSelectedTime = () => {
  if (props.isRange) {
    scrollToTime(startHoursScroll.value, startTime.value.hour)
    scrollToTime(startMinutesScroll.value, startTime.value.minute)
    scrollToTime(endHoursScroll.value, endTime.value.hour)
    scrollToTime(endMinutesScroll.value, endTime.value.minute)
  } else {
    scrollToTime(singleHoursScroll.value, singleTime.value.hour)
    scrollToTime(singleMinutesScroll.value, singleTime.value.minute)
  }
}

const scrollToTime = (scrollElement, value) => {
  if (!scrollElement) return
  const itemHeight = 32
  const scrollTop = value * itemHeight
  scrollElement.scrollTop = scrollTop
}

const selectStartHour = (hour) => {
  startTime.value.hour = hour
  updateStartTimeDisplay()
}

const selectStartMinute = (minute) => {
  startTime.value.minute = minute
  updateStartTimeDisplay()
}

const selectEndHour = (hour) => {
  endTime.value.hour = hour
  updateEndTimeDisplay()
}

const selectEndMinute = (minute) => {
  endTime.value.minute = minute
  updateEndTimeDisplay()
}

const selectSingleHour = (hour) => {
  singleTime.value.hour = hour
  updateSingleTimeDisplay()
}

const selectSingleMinute = (minute) => {
  singleTime.value.minute = minute
  updateSingleTimeDisplay()
}

const updateStartTimeDisplay = () => {
  startTimeDisplay.value = formatTime(startTime.value)
}

const updateEndTimeDisplay = () => {
  endTimeDisplay.value = formatTime(endTime.value)
}

const updateSingleTimeDisplay = () => {
  singleTimeDisplay.value = formatTime(singleTime.value)
}

// Day picker methods
const toggleDay = (dayValue) => {
  if (unavailableDays.value.includes(dayValue)) {
    // If day is unavailable, make it available and select it
    unavailableDays.value = unavailableDays.value.filter(d => d !== dayValue)
    if (!selectedDays.value.includes(dayValue)) {
      selectedDays.value.push(dayValue)
    }
  } else if (selectedDays.value.includes(dayValue)) {
    // If day is selected, deselect it
    selectedDays.value = selectedDays.value.filter(d => d !== dayValue)
  } else {
    // If day is not selected, select it
    selectedDays.value.push(dayValue)
  }
}

const toggleAllDays = () => {
  if (allDaysSelected.value) {
    selectedDays.value = []
  } else {
    selectedDays.value = weekDays.map(day => day.value)
  }
}

const switchToTimePicker = () => {
  showDayPicker.value = false
  nextTick(() => {
    scrollToSelectedTime()
  })
}

const switchToDayPicker = () => {
  showDayPicker.value = true
}

const confirmPicker = () => {
  const result = {
    selectedDays: [...selectedDays.value],
    unavailableDays: [...unavailableDays.value],
    timeRange: null,
    singleTime: null
  }

  if (props.isRange) {
    const startDate = new Date()
    startDate.setHours(startTime.value.hour, startTime.value.minute, 0, 0)
    const endDate = new Date()
    endDate.setHours(endTime.value.hour, endTime.value.minute, 0, 0)
    result.timeRange = [startDate, endDate]
  } else {
    const date = new Date()
    date.setHours(singleTime.value.hour, singleTime.value.minute, 0, 0)
    result.singleTime = date
  }

  emit('update:modelValue', result)
  emit('change', result)
  showPicker.value = false
  isFocused.value = false
}

const cancelPicker = () => {
  showPicker.value = false
  isFocused.value = false
  // Reset to original values
  initializeFromModelValue()
}

const initializeFromModelValue = () => {
  if (props.modelValue && typeof props.modelValue === 'object') {
    // Handle new data structure with days and time
    if (props.modelValue.selectedDays) {
      selectedDays.value = [...props.modelValue.selectedDays]
    }
    if (props.modelValue.unavailableDays) {
      unavailableDays.value = [...props.modelValue.unavailableDays]
    }
    if (props.modelValue.timeRange && Array.isArray(props.modelValue.timeRange) && props.modelValue.timeRange.length === 2) {
      const [start, end] = props.modelValue.timeRange
      startTime.value = { hour: start.getHours(), minute: start.getMinutes() }
      endTime.value = { hour: end.getHours(), minute: end.getMinutes() }
      updateStartTimeDisplay()
      updateEndTimeDisplay()
    } else if (props.modelValue.singleTime) {
      singleTime.value = { hour: props.modelValue.singleTime.getHours(), minute: props.modelValue.singleTime.getMinutes() }
      updateSingleTimeDisplay()
    }
  } else if (props.isRange && Array.isArray(props.modelValue) && props.modelValue.length === 2) {
    // Handle legacy array format
    const [start, end] = props.modelValue
    startTime.value = { hour: start.getHours(), minute: start.getMinutes() }
    endTime.value = { hour: end.getHours(), minute: end.getMinutes() }
    updateStartTimeDisplay()
    updateEndTimeDisplay()
  } else if (!props.isRange && props.modelValue) {
    // Handle legacy single date format
    singleTime.value = { hour: props.modelValue.getHours(), minute: props.modelValue.getMinutes() }
    updateSingleTimeDisplay()
  }
}

// Event handlers
const handleStartFocus = () => {
  isFocused.value = true
}

const handleStartBlur = () => {
  // Delay to allow clicking on dropdown
  setTimeout(() => {
    if (!showPicker.value) {
      isFocused.value = false
    }
  }, 150)
}

const handleEndFocus = () => {
  isFocused.value = true
}

const handleEndBlur = () => {
  setTimeout(() => {
    if (!showPicker.value) {
      isFocused.value = false
    }
  }, 150)
}

const handleSingleFocus = () => {
  isFocused.value = true
}

const handleSingleBlur = () => {
  setTimeout(() => {
    if (!showPicker.value) {
      isFocused.value = false
    }
  }, 150)
}

// Click outside to close
const handleClickOutside = (event) => {
  if (!event.target.closest('.time-picker-container')) {
    showPicker.value = false
    isFocused.value = false
  }
}

// Watchers
watch(() => props.modelValue, () => {
  initializeFromModelValue()
}, { immediate: true })

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  initializeFromModelValue()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.time-picker-container {
  position: relative;
  display: inline-block;
  width: 100%;
}

.time-picker-input {
  position: relative;
  display: flex;
  align-items: center;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background-color: #fff;
  transition: all 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  cursor: pointer;
  min-height: 32px;
  font-size: 14px;
  line-height: 1.5;
}

.time-picker-input:hover {
  border-color: #c0c4cc;
}

.time-picker-input.is-focused {
  border-color: #409eff;
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.2);
}

.time-picker-input.arrow-control .time-picker-icon {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 0 8px;
}

.time-picker-input.arrow-control .arrow-up,
.time-picker-input.arrow-control .arrow-down {
  cursor: pointer;
  color: #c0c4cc;
  transition: color 0.2s;
}

.time-picker-input.arrow-control .arrow-up:hover,
.time-picker-input.arrow-control .arrow-down:hover {
  color: #409eff;
}

.time-range-input {
  display: flex;
  align-items: center;
  flex: 1;
  padding: 0 8px;
}

.single-time-input {
  display: flex;
  align-items: center;
  flex: 1;
  padding: 0 8px;
}

.time-input {
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: #606266;
  flex: 1;
  cursor: pointer;
}

.time-input::placeholder {
  color: #c0c4cc;
}

.range-separator {
  margin: 0 8px;
  color: #606266;
  font-size: 14px;
  white-space: nowrap;
}

.time-picker-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  color: #c0c4cc;
  cursor: pointer;
}

.time-picker-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 2000;
  background: #fff;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
  margin-top: 4px;
  overflow: hidden;
  min-width: 300px;
}

/* Day Picker Styles */
.day-picker-section {
  padding: 16px;
}

.day-picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: 14px;
  font-weight: 500;
  color: #303133;
}

.day-picker-toggle {
  background: none;
  border: none;
  color: #409eff;
  font-size: 12px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.day-picker-toggle:hover {
  background-color: #ecf5ff;
}

.day-picker-content {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.day-picker-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  background: #fff;
}

.day-picker-item:hover {
  border-color: #409eff;
  background-color: #f0f9ff;
}

.day-picker-item.is-selected {
  border-color: #409eff;
  background-color: #409eff;
  color: #fff;
}

.day-picker-item.is-unavailable {
  border-color: #f56c6c;
  background-color: #fef0f0;
  color: #f56c6c;
}

.day-picker-item.is-unavailable.is-selected {
  border-color: #67c23a;
  background-color: #67c23a;
  color: #fff;
}

.day-name {
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 4px;
}

.day-status {
  font-size: 12px;
  opacity: 0.8;
}

.time-picker-main {
  min-height: 200px;
}

.time-picker-range {
  display: flex;
}

.time-picker-section {
  flex: 1;
  border-right: 1px solid #e4e7ed;
}

.time-picker-section:last-child {
  border-right: none;
}

.time-picker-header {
  padding: 8px 12px;
  background: #f5f7fa;
  border-bottom: 1px solid #e4e7ed;
  font-size: 12px;
  color: #909399;
  text-align: center;
  font-weight: 500;
}

.time-picker-single .time-picker-header {
  display: none;
}

.time-picker-content {
  display: flex;
  height: 200px;
}

.time-picker-hours,
.time-picker-minutes {
  flex: 1;
  border-right: 1px solid #e4e7ed;
  position: relative;
}

.time-picker-minutes {
  border-right: none;
}

.time-picker-hours::after,
.time-picker-minutes::after {
  content: '';
  position: absolute;
  top: 50%;
  right: 0;
  width: 1px;
  height: 32px;
  background: #e4e7ed;
  transform: translateY(-50%);
}

.time-picker-minutes::after {
  display: none;
}

.time-picker-scroll {
  height: 100%;
  overflow-y: auto;
  scroll-behavior: smooth;
}

.time-picker-scroll::-webkit-scrollbar {
  width: 6px;
}

.time-picker-scroll::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.time-picker-scroll::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.time-picker-scroll::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.time-picker-item {
  height: 32px;
  line-height: 32px;
  text-align: center;
  font-size: 14px;
  color: #606266;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.time-picker-item:hover {
  background-color: #f5f7fa;
  color: #409eff;
}

.time-picker-item.is-selected {
  background-color: #409eff;
  color: #fff;
  font-weight: 500;
}

.time-picker-item.is-selected:hover {
  background-color: #409eff;
  color: #fff;
}

.time-picker-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 8px 12px;
  background: #f5f7fa;
  border-top: 1px solid #e4e7ed;
}

.time-picker-btn {
  padding: 6px 16px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fff;
  color: #606266;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
  min-width: 60px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.time-picker-btn:hover {
  color: #409eff;
  border-color: #c6e2ff;
  background-color: #ecf5ff;
}

.time-picker-btn-secondary {
  background: #fff;
  color: #409eff;
  border-color: #409eff;
}

.time-picker-btn-secondary:hover {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}

.time-picker-btn-confirm {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}

.time-picker-btn-confirm:hover {
  background: #66b1ff;
  border-color: #66b1ff;
  color: #fff;
}

/* Arrow control styles */
.time-picker-input.arrow-control .time-picker-icon {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.time-picker-input.arrow-control .time-picker-icon svg {
  width: 12px;
  height: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .time-picker-range {
    flex-direction: column;
  }
  
  .time-picker-section {
    border-right: none;
    border-bottom: 1px solid #e4e7ed;
  }
  
  .time-picker-section:last-child {
    border-bottom: none;
  }
}
</style>
