<template>
  <div>
    <h2>Modify Schedules</h2>

    <table border="1" cellpadding="5" cellspacing="0" class="modify-table">
      <thead>
        <tr>
          <th>Subject</th>
          <th>Time</th>
          <th>Classroom #</th>
          <th>Professor</th>
          <th>Course Code</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(schedule, index) in editableSchedules" :key="schedule.id">
          <td><input v-model="schedule.subject" /></td>
          <td><input v-model="schedule.time" /></td>
          <td><input v-model="schedule.classroom" /></td>
          <td><input v-model="schedule.professor" /></td>
          <td><input v-model="schedule.courseCode" /></td>
          <td>
            <button @click="saveSchedule(index)">Save</button>
            <button @click="cancelEdit(index)">Cancel</button>
          </td>
        </tr>
      </tbody>
    </table>

    <router-link to="/schedule" class="back-btn">Back to Schedule</router-link>
  </div>
</template>

<script>
export default {
  name: 'ModifySchedule',
  data() {
    return {

      // fetch schedules from API or Vuex store

      originalSchedules: [
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
        
      ],
      editableSchedules: [],
    };
  },
  created() {
    // Deep clone to avoid mutating original data until saved
    this.editableSchedules = this.originalSchedules.map(s => ({ ...s }));
  },
  methods: {
    saveSchedule(index) {
      const schedule = this.editableSchedules[index];
      // TODO: Add API call to save schedule changes here
      // For now, update originalSchedules to reflect changes
      this.originalSchedules[index] = { ...schedule };
      alert(`Schedule for "${schedule.subject}" saved.`);
    },
    cancelEdit(index) {
      // Revert changes by resetting editableSchedules row to original
      this.editableSchedules.splice(index, 1, { ...this.originalSchedules[index] });
    },
  },
};
</script>

<style scoped>
.modify-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
.modify-table th,
.modify-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}
.modify-table input {
  width: 100%;
  box-sizing: border-box;
  padding: 4px 6px;
}
button {
  margin-right: 6px;
  padding: 6px 10px;
  cursor: pointer;
}
.back-btn {
  display: inline-block;
  padding: 8px 12px;
  background-color: #777;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  font-weight: 600;
}
</style>