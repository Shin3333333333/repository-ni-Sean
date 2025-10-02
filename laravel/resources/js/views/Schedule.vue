<template>
  <div>
    <div class="filters">
      <div class="filter-group">
        <label>
          Academic Year:
          <select v-model="selectedAcademicYear">
            <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </label>

        <label>
          Semester:
          <select v-model="selectedSemester">
            <option v-for="sem in semesters" :key="sem" :value="sem">{{ sem }}</option>
          </select>
        </label>

        <label>
          Course:
          <select v-model="selectedCourse" @change="filterSchedules">
            <option value="">All</option>
            <option v-for="course in courses" :key="course" :value="course">{{ course }}</option>
          </select>
        </label>

        <label>
          Year:
          <select v-model="selectedYear" @change="filterSchedules">
            <option value="">All</option>
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </label>
      </div>

      <button class="export-btn" @click="goToExport">Export</button>
    </div>

    <div class="top-controls">
      <router-link to="/schedule/modify" class="modify-btn">Modify Schedules</router-link>
      <router-link to="/error-log" class="errorlog-btn">Error Log</router-link>
    </div>

    <div class="view-toggle">
      <button 
        @click="activeView = 'faculty'" 
        :class="{ active: activeView === 'faculty' }"
      >
        Faculty Loads
      </button>
      <button 
        @click="activeView = 'schedules'" 
        :class="{ active: activeView === 'schedules' }"
      >
        Class Schedules
      </button>
    </div>

    <div v-if="activeView === 'faculty'">
      <div v-for="faculty in facultyLoads" :key="faculty.name" class="faculty-section">
        <h4>{{ faculty.name }} ({{ faculty.type }} - {{ faculty.field }})</h4>
        <table class="styled-table">
          <thead>
            <tr>
              <th>Course Code</th>
              <th>Subject</th>
              <th>Room</th>
              <th>Time</th>
              <th>Day</th>
              <th>Course</th>
              <th>Units</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(load, idx) in faculty.loads" :key="idx">
              <td>{{ load.courseCode }}</td>
              <td>{{ load.subject }}</td>
              <td>{{ load.room }}</td>
              <td>{{ load.time }}</td>
              <td>{{ load.day }}</td>
              <td>{{ load.course }}</td>
              <td>{{ load.units }}</td>
            </tr>
          </tbody>
        </table>
        <div class="total-units">Total Units = {{ faculty.totalUnits }}</div>
      </div>
    </div>

    <div v-if="activeView === 'schedules'">
      <table class="styled-table">
        <thead>
          <tr>
            <th>Course Section</th>
            <th>Course Code</th>
            <th>Subject</th>
            <th>Room</th>
            <th>Time</th>
            <th>Day</th>
            <th>Faculty</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="schedule in filteredSchedules" :key="schedule.id">
            <td>{{ schedule.courseSection }}</td>
            <td>{{ schedule.courseCode }}</td>
            <td>{{ schedule.subject }}</td>
            <td>{{ schedule.room }}</td>
            <td>{{ schedule.time }}</td>
            <td>{{ schedule.day }}</td>
            <td>{{ schedule.faculty }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      activeView: 'faculty',
      academicYears: ["2024-2025", "2025-2026", "2026-2027"],
      semesters: ["1st Semester", "2nd Semester", "Summer"],
      selectedAcademicYear: "2024-2025",
      selectedSemester: "1st Semester",

      courses: ["BSIT", "BSCS", "BSA"],
      years: ["1st Year", "2nd Year", "3rd Year", "4th Year"],
      selectedCourse: "",
      selectedYear: "",

      facultyLoads: [
        {
          name: "Dr. Brown",
          type: "Full-time",
          field: "BSCS",
          loads: [
            { courseCode: "BSCS-1", subject: "Physics", room: "102", time: "10:00-11:00", day: "Mon-Wed", course: "BSCS 1", units: 3 },
            { courseCode: "BSCS-2", subject: "Algorithms", room: "201", time: "1:00-2:30", day: "Tue-Thu", course: "BSCS 2", units: 4 },
          ],
          get totalUnits() {
            return this.loads.reduce((sum, l) => sum + l.units, 0);
          },
        },
        {
          name: "Mr. Smith",
          type: "Part-time",
          field: "BSIT",
          loads: [
            { courseCode: "BSIT-2", subject: "Math", room: "101", time: "9:00-10:00", day: "Mon-Fri", course: "BSIT 2", units: 3 },
          ],
          get totalUnits() {
            return this.loads.reduce((sum, l) => sum + l.units, 0);
          },
        },
      ],

      schedules: [
        {
          id: 1,
          courseSection: "BSIT 2-1",
          courseCode: "BSIT-2",
          subject: "Math",
          room: "101",
          time: "9:00-10:00",
          day: "Mon-Fri",
          faculty: "Mr. Smith",
          course: "BSIT",
          year: "2nd Year",
        },
        {
          id: 2,
          courseSection: "BSCS 1-1",
          courseCode: "BSCS-1",
          subject: "Physics",
          room: "102",
          time: "10:00-11:00",
          day: "Mon-Wed",
          faculty: "Dr. Brown",
          course: "BSCS",
          year: "1st Year",
        },
      ],
      filteredSchedules: [],
    };
  },
  created() {
    this.filterSchedules();
  },
  methods: {
    filterSchedules() {
      this.filteredSchedules = this.schedules.filter((s) => {
        return (
          (this.selectedCourse === "" || s.course === this.selectedCourse) &&
          (this.selectedYear === "" || s.year === this.selectedYear)
        );
      });
    },
    goToExport() {
      this.$router.push('/export');
    },
  },
};
</script>

<style scoped>
.filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 20px;
}

.filters label {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.filters select {
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  min-width: 120px;
}

.export-btn {
  padding: 8px 14px;
  background-color: #27ae60;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.export-btn:hover {
  background-color: #1e8449;
}

.top-controls {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 20px;
}

.modify-btn {
  display: inline-block;
  padding: 8px 14px;
  background-color: #3498db;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.errorlog-btn {
  display: inline-block;
  padding: 8px 14px;
  background-color: #e74c3c;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.errorlog-btn:hover {
  background-color: #c0392b;
}

.view-toggle {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.view-toggle button {
  padding: 10px 20px;
  border: 1px solid #ddd;
  background: white;
  color: #333;
  cursor: pointer;
  border-radius: 5px;
  font-weight: 500;
  transition: background-color 0.2s, color 0.2s;
}

.view-toggle button:hover {
  background-color: #f0f0f0;
}

.view-toggle button.active {
  background-color: #121212;
  color: white;
}

.styled-table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 12px;
  overflow: hidden;
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  margin-bottom: 30px;
}

.styled-table th,
.styled-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #ddd;
  text-align: left;
}

.styled-table th {
  background: #f5f5f5;
  font-weight: 600;
}

.styled-table tr:last-child td {
  border-bottom: none;
}

.total-units {
  font-weight: 700;
  margin-top: 8px;
  margin-bottom: 30px;
}

h3 {
  margin-top: 40px;
  margin-bottom: 10px;
}

h4 {
  margin: 20px 0 10px;
}

.faculty-section {
  margin-bottom: 40px;
}
</style>