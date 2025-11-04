# Time Picker Component

A Vue 3 time picker component with Element Plus design and day selection functionality.

## Features

- ✅ Element Plus design and styling
- ✅ Single time picker
- ✅ Range time picker
- ✅ Arrow control mode
- ✅ Day selection with availability toggle
- ✅ Responsive design
- ✅ TypeScript support

## Usage

### Basic Time Picker

```vue
<template>
  <TimePicker
    v-model="time"
    placeholder="Select time"
    @change="handleTimeChange"
  />
</template>

<script setup>
import { ref } from 'vue'
import { TimePicker } from '@/components/ui/time-picker'

const time = ref(null)

const handleTimeChange = (value) => {
  console.log('Time selected:', value)
}
</script>
```

### Range Time Picker

```vue
<template>
  <TimePicker
    v-model="timeRange"
    :is-range="true"
    start-placeholder="Start time"
    end-placeholder="End time"
    range-separator="To"
    @change="handleRangeChange"
  />
</template>

<script setup>
import { ref } from 'vue'
import { TimePicker } from '@/components/ui/time-picker'

const timeRange = ref(null)

const handleRangeChange = (value) => {
  console.log('Time range selected:', value)
}
</script>
```

### Time Picker with Day Selection

```vue
<template>
  <TimePicker
    v-model="scheduleData"
    :is-range="true"
    :show-day-picker="true"
    start-placeholder="Start time"
    end-placeholder="End time"
    range-separator="To"
    @change="handleScheduleChange"
  />
</template>

<script setup>
import { ref } from 'vue'
import { TimePicker } from '@/components/ui/time-picker'

const scheduleData = ref({
  selectedDays: [],
  unavailableDays: [],
  timeRange: null
})

const handleScheduleChange = (value) => {
  console.log('Schedule data:', value)
  // value.selectedDays: Array of selected day numbers (0-6, where 0 = Sunday)
  // value.unavailableDays: Array of unavailable day numbers
  // value.timeRange: Array of [startDate, endDate] or null
}
</script>
```

### Arrow Control Mode

```vue
<template>
  <TimePicker
    v-model="timeRange"
    :is-range="true"
    :arrow-control="true"
    start-placeholder="Start time"
    end-placeholder="End time"
    range-separator="To"
  />
</template>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `modelValue` | `Date \| Array \| Object` | `null` | The selected time value(s) |
| `isRange` | `Boolean` | `false` | Whether to show range picker |
| `rangeSeparator` | `String` | `'To'` | Separator text between start and end time |
| `startPlaceholder` | `String` | `'Start time'` | Placeholder for start time input |
| `endPlaceholder` | `String` | `'End time'` | Placeholder for end time input |
| `placeholder` | `String` | `'Select time'` | Placeholder for single time input |
| `arrowControl` | `Boolean` | `false` | Whether to show arrow controls |
| `showDayPicker` | `Boolean` | `true` | Whether to show day selection |

## Events

| Event | Parameters | Description |
|-------|------------|-------------|
| `update:modelValue` | `value` | Emitted when the selected value changes |
| `change` | `value` | Emitted when the selection is confirmed |

## Data Structure

### Single Time
```javascript
// Returns a Date object
new Date(2024, 0, 1, 14, 30) // 2:30 PM
```

### Range Time
```javascript
// Returns an array of Date objects
[new Date(2024, 0, 1, 9, 0), new Date(2024, 0, 1, 17, 0)] // 9:00 AM to 5:00 PM
```

### Schedule Data (with day selection)
```javascript
{
  selectedDays: [1, 2, 3, 4, 5], // Monday to Friday
  unavailableDays: [0, 6], // Sunday and Saturday
  timeRange: [startDate, endDate] // Time range or null
}
```

## Day Values

- `0` = Sunday
- `1` = Monday
- `2` = Tuesday
- `3` = Wednesday
- `4` = Thursday
- `5` = Friday
- `6` = Saturday

## Styling

The component uses Element Plus color scheme and styling:

- Primary color: `#409eff`
- Success color: `#67c23a`
- Danger color: `#f56c6c`
- Text colors: `#303133`, `#606266`, `#909399`
- Border colors: `#dcdfe6`, `#e4e7ed`

## Browser Support

- Chrome 60+
- Firefox 60+
- Safari 12+
- Edge 79+
