<template>
  <div class="create-page">
    <h2>Create</h2>

    <button @click="generateSchedule" class="generate-btn">Generate Schedule</button>

    <form @submit.prevent="saveSchedule">
      <table border="1" cellpadding="5" cellspacing="0" class="create-table">
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
          <tr v-for="(entry, index) in scheduleEntries" :key="index">
            <td><input v-model="entry.subject" required /></td>
            <td><input v-model="entry.time" required /></td>
            <td><input v-model="entry.classroom" required /></td>
            <td><input v-model="entry.professor" required /></td>
            <td><input v-model="entry.courseCode" required /></td>
            <td>
              <button type="button" @click="removeEntry(index)">Remove</button>
            </td>
          </tr>
        </tbody>
      </table>

      <button type="button" @click="addEntry" class="add-btn">Add Entry</button>
      <button type="submit" class="save-btn">Save Schedule</button>
    </form>
  </div>
</template>

<script>
export default {
  data() {
    return {
      scheduleEntries: [], // initially empty; will be filled by API
    };
  },
  methods: {
    addEntry() {
      this.scheduleEntries.push({
        subject: '',
        time: '',
        classroom: '',
        professor: '',
        courseCode: '',
      });
    },
    removeEntry(index) {
      this.scheduleEntries.splice(index, 1);
    },
    saveSchedule() {
      const incomplete = this.scheduleEntries.some(
        (e) =>
          !e.subject ||
          !e.time ||
          !e.classroom ||
          !e.professor ||
          !e.courseCode
      );
      if (incomplete) {
        alert('Please fill in all fields for all entries.');
        return;
      }

      // Here you can send scheduleEntries to your backend if needed
      console.log('Saving schedule:', this.scheduleEntries);
      alert('Schedule saved successfully!');

      this.scheduleEntries = [];
    },
   async generateSchedule() {
      try {
        const res = await fetch("/api/generate-schedule"); // calls Laravel API
        const generated = await res.json();

        this.scheduleEntries = generated.map((s) => ({
          subject: s.subject,
          time: s.time,
          classroom: s.room,
          professor: s.faculty,
          courseCode: s.course,
        }));
      } catch (err) {
        console.error("Failed to generate schedule:", err);
        alert("Could not generate schedule. Check console for errors.");
      }
    }

,
  },
};
</script>

<style scoped>
.create-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

.create-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 15px;
}

.create-table th,
.create-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}

input {
  width: 100%;
  box-sizing: border-box;
  padding: 6px 8px;
}

.add-btn,
.save-btn,
.generate-btn {
  padding: 10px 16px;
  margin-right: 10px;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
}

.add-btn {
  background-color: #27ae60;
  color: white;
}

.add-btn:hover {
  background-color: #219150;
}

.save-btn {
  background-color: #2980b9;
  color: white;
}

.save-btn:hover {
  background-color: #1f5f8b;
}

.generate-btn {
  background-color: #f39c12;
  color: white;
  margin-bottom: 15px;
}

.generate-btn:hover {
  background-color: #d68910;
}

button[type='button'] {
  background-color: #e74c3c;
  color: white;
  padding: 6px 10px;
  border-radius: 4px;
}

button[type='button']:hover {
  background-color: #c0392b;
}
</style>
