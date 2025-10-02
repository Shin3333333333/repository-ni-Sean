<template>
  <div class="export-page">

    <label for="schedule-select">Select Schedule:</label>
    <select id="schedule-select" v-model="selectedScheduleId" @change="loadSchedule">
      <option disabled value="">-- Select a schedule --</option>
      <option v-for="schedule in schedules" :key="schedule.id" :value="schedule.id">
        {{ schedule.name }}
      </option>
    </select>

    <div v-if="selectedSchedule" class="schedule-preview" ref="printSection">
      <h3>{{ selectedSchedule.name }}</h3>
      <table border="1" cellpadding="5" cellspacing="0" class="schedule-table">
        <thead>
          <tr>
            <th>Subject</th>
            <th>Time</th>
            <th>Classroom #</th>
            <th>Professor</th>
            <th>Course Code</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in selectedSchedule.items" :key="item.id">
            <td>{{ item.subject }}</td>
            <td>{{ item.time }}</td>
            <td>{{ item.classroom }}</td>
            <td>{{ item.professor }}</td>
            <td>{{ item.courseCode }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <button
      v-if="selectedSchedule"
      @click="printSchedule"
      class="print-btn"
    >
      Print Schedule
    </button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      schedules: [
        {
          id: 1,
          name: 'BSIT 2nd Year - Fall 2024',
          items: [
            {
              id: 1,
              subject: 'Math',
              time: '9:00-10:00',
              classroom: '101',
              professor: 'Mr. Smith',
              courseCode: 'BSIT-2',
            },
            {
              id: 2,
              subject: 'Physics',
              time: '10:00-11:00',
              classroom: '102',
              professor: 'Dr. Brown',
              courseCode: 'BSCS-1',
            },
            // Add more schedule items here
          ],
        },
        {
          id: 2,
          name: 'BSCS 1st Year - Spring 2024',
          items: [
            // Schedule items here
          ],
        },
      ],
      selectedScheduleId: '',
      selectedSchedule: null,
    };
  },
  methods: {
    loadSchedule() {
      this.selectedSchedule = this.schedules.find(
        (s) => s.id === this.selectedScheduleId
      );
    },
    printSchedule() {
      // Print only the schedule preview section
      const printContents = this.$refs.printSection.innerHTML;
      const originalContents = document.body.innerHTML;

      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      window.location.reload(); // Reload to restore Vue app state
    },
  },
};
</script>

<style scoped>
.export-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

label {
  font-weight: 600;
  margin-right: 10px;
}

select {
  padding: 6px 10px;
  margin-bottom: 20px;
}

.schedule-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.schedule-table th,
.schedule-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}

.print-btn {
  margin-top: 20px;
  padding: 10px 16px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}

.print-btn:hover {
  background-color: #2980b9;
}
</style>