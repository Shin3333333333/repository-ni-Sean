<template>
  <div>
    <!-- Filters -->
    <div class="filters">
      <div class="filter-group">
        <label>
          Academic Year:
          <select v-model="selectedAcademicYear" @change="loadFilteredSchedule">
            <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </label>

        <label>
          Semester:
          <select v-model="selectedSemester" @change="loadFilteredSchedule">
            <option v-for="sem in semesters" :key="sem" :value="sem">{{ sem }}</option>
          </select>
        </label>

        <label>
          Course Section:
          <select v-model="selectedCourseSection" @change="filterSchedules">
            <option value="">All</option>
            <option v-for="section in courseSections" :key="section" :value="section">{{ section }}</option>
          </select>
        </label>
      </div>

      <div class="actions">
        <button class="export-btn" @click="goToExport">Export</button>
        <button
          class="stage-btn"
          v-if="latestSchedule.length"
          @click="showStageModal = true"
        >
          Stage as Active Schedule
        </button>
         <button class="detect-btn" @click="detectConflicts">
      Detect Conflicts
    </button>
          </div>
    </div>
    
    <!-- Current Active Info -->
    <div v-if="activeScheduleInfo" class="active-banner">
      <strong>Active Schedule:</strong>
      {{ activeScheduleInfo.academicYear }} – {{ activeScheduleInfo.semester }}
    </div>

    <!-- View Toggle -->
    <div class="view-toggle">
      <button @click="activeView='faculty'" :class="{ active: activeView==='faculty' }">Faculty Loads</button>
      <button @click="activeView='schedules'" :class="{ active: activeView==='schedules' }">Class Schedules</button>
    </div>

    <!-- Faculty Loads View -->
    <div v-if="activeView==='faculty'">
      <div v-if="facultyLoads.length===0">No faculty loads available.</div>
      <div v-for="faculty in facultyLoads" :key="faculty.name" class="faculty-section">
        <h4>{{ faculty.name }} (Total Units: {{ faculty.totalUnits }})</h4>
        <table class="styled-table">
          <thead>
            <tr>
              <th>Course Code</th>
              <th>Subject</th>
              <th>Time</th>
              <th>Room</th>
              <th>Course Section</th>
              <th>Units</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(load, idx) in faculty.loads" :key="idx">
              <td>{{ load.course_code || 'N/A' }}</td>
              <td>{{ load.subject || 'N/A' }}</td>
              <td>{{ load.time || 'N/A' }}</td>
              <td>{{ load.classroom || 'N/A' }}</td>
              <td>{{ load.course_section || 'N/A' }}</td>
              <td>{{ load.units || 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Class Schedules View -->
    <div v-if="activeView==='schedules'">
      <div v-if="Object.keys(courseSchedules).length===0">No class schedules available.</div>
      <div v-for="(schedules, section) in courseSchedules" :key="section" class="course-section">
        <h4>{{ section }}</h4>
        <table class="styled-table">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Room</th>
              <th>Time</th>
              <th>Faculty</th>
              <th>Units</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="schedule in schedules" :key="schedule.id">
              <td>{{ schedule.subject || 'N/A' }}</td>
              <td>{{ schedule.classroom || 'N/A' }}</td>
              <td>{{ schedule.time || 'N/A' }}</td>
              <td>{{ schedule.faculty || 'N/A' }}</td>
              <td>{{ schedule.units || 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Stage Confirmation Modal -->
    <div v-if="showStageModal" class="modal-backdrop">
      <div class="modal">
        <h3>Stage Schedule</h3>
        <p>
          Are you sure you want to set this schedule as the <strong>active</strong> schedule for
          <em>{{ selectedAcademicYear }}</em> – <em>{{ selectedSemester }}</em>?
        </p>
        <div class="modal-actions">
          <button class="confirm" @click="setAsActiveSchedule">Yes, Set Active</button>
          <button class="cancel" @click="showStageModal = false">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      activeView: 'faculty',
      academicYears: [],
      semesters: [],
      selectedAcademicYear: "",
      selectedSemester: "",
      courseSections: [],
      selectedCourseSection: "",
      latestSchedule: [],
      allSchedules: [], // ← store all fetched schedules
      facultyLoads: [],
      courseSchedules: {},
      showStageModal: false,
      activeScheduleInfo: null,
    };
  },
  created() {
    this.loadLatestSchedule();
    this.fetchActiveScheduleInfo();
  },
  methods: {
    async detectConflicts() {
  try {
    const payload = {
      academic_year: this.selectedAcademicYear,
      semester: this.selectedSemester,
    };
    const res = await fetch('/api/detect-conflicts', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    alert(data.message);
    if (data.redirect) this.$router.push(data.redirect);
  } catch (err) {
    console.error(err);
    alert('Conflict detection failed.');
  }
}
,
    async loadLatestSchedule() {
      try {
        const res = await fetch(`/api/finalized-schedules`);
        if (!res.ok) throw new Error(`Server returned ${res.status}`);
        const data = await res.json();
        const schedules = Array.isArray(data) ? data : data.schedules || [];

        if (!schedules.length) {
          this.latestSchedule = [];
          this.allSchedules = [];
          this.facultyLoads = [];
          this.courseSchedules = {};
          return;
        }

        this.allSchedules = schedules; // ← store all for later filtering

        // Populate filters dynamically
        this.academicYears = [...new Set(schedules.map(s => s.academicYear).filter(Boolean))];
        this.semesters = [...new Set(schedules.map(s => s.semester).filter(Boolean))];
        this.courseSections = [...new Set(schedules.map(s => s.course_section).filter(Boolean))];

        this.selectedAcademicYear ||= this.academicYears[0];
        this.selectedSemester ||= this.semesters[0];

        // Apply initial filter
        this.loadFilteredSchedule();
      } catch (err) {
        console.error(err);
        alert("Failed to load latest schedule.");
      }
    },

    loadFilteredSchedule() {
      if (!this.allSchedules.length) return;

      // Filter by academic year and semester
      const filtered = this.allSchedules.filter(
        s =>
          s.academicYear === this.selectedAcademicYear &&
          s.semester === this.selectedSemester
      );

      if (!filtered.length) {
        this.latestSchedule = [];
        this.facultyLoads = [];
        this.courseSchedules = {};
        return;
      }

      // Use latest batch from filtered set
      const latestBatchId = filtered.sort(
        (a, b) => new Date(b.created_at) - new Date(a.created_at)
      )[0].batch_id;

      const latestBatchSchedules = filtered.filter(s => s.batch_id === latestBatchId);
      this.latestSchedule = latestBatchSchedules;

      this.processFacultyLoads(this.latestSchedule);
      this.processCourseSchedules(this.latestSchedule);
    },

    async fetchActiveScheduleInfo() {
      try {
        const res = await fetch(`/api/active-schedule`);
        if (!res.ok) return;
        const data = await res.json();
        this.activeScheduleInfo = data || null;
      } catch (e) {
        console.error("Failed to fetch active schedule info", e);
      }
    },

    async setAsActiveSchedule() {
      try {
        const payload = {
          academicYear: this.selectedAcademicYear,
          semester: this.selectedSemester,
        };
        const res = await fetch(`/api/set-active-schedule`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error("Failed to set active schedule");
        this.showStageModal = false;
        alert("Schedule successfully staged as active.");
        this.fetchActiveScheduleInfo();
      } catch (err) {
        console.error(err);
        alert("Failed to set active schedule.");
      }
    },

    filterSchedules() {
      if (!this.latestSchedule.length) return;
      let rows = this.latestSchedule;

      if (this.selectedCourseSection)
        rows = rows.filter(r => r.course_section === this.selectedCourseSection);

      this.processFacultyLoads(rows);
      this.processCourseSchedules(rows);
    },

    processFacultyLoads(rows = null) {
      const data = rows || this.latestSchedule || [];
      const grouped = {};
      data.forEach(s => {
        const facultyName = s.faculty || "Unassigned";
        if (!grouped[facultyName]) grouped[facultyName] = [];
        grouped[facultyName].push(s);
      });

      this.facultyLoads = Object.entries(grouped).map(([name, loads]) => ({
        name,
        loads,
        totalUnits: loads.reduce((sum, l) => sum + (parseFloat(l.units) || 0), 0),
      }));
    },

    processCourseSchedules(rows = null) {
      const data = rows || this.latestSchedule || [];
      const grouped = {};
      data.forEach(s => {
        const section = s.course_section || "Unassigned Section";
        if (!grouped[section]) grouped[section] = [];
        grouped[section].push(s);
      });

      this.courseSchedules = grouped;
    },

    goToExport() {
      this.$router.push('/export');
    },
  },
};
</script>


<style scoped>
.filters { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.filter-group { display:flex; align-items:center; gap:20px; }
.filters label { display:flex; flex-direction:row; align-items:center; gap:8px; font-size:14px; }
.filters select { padding:6px 10px; border:1px solid #ddd; border-radius:4px; background:white; min-width:120px; }
.actions { display:flex; gap:10px; }
.export-btn { padding:8px 14px; background-color:#27ae60; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
.stage-btn { padding:8px 14px; background-color:#2980b9; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
.export-btn:hover { background-color:#1e8449; }
.stage-btn:hover { background-color:#1f6391; }
.view-toggle { display:flex; gap:10px; margin-bottom:20px; }
.view-toggle button { padding:10px 20px; border:1px solid #ddd; background:white; color:#333; cursor:pointer; border-radius:5px; font-weight:500; transition:background-color .2s, color .2s; }
.view-toggle button.active { background-color:#121212; color:white; }
.styled-table { width:100%; border-collapse:collapse; border-radius:12px; overflow:hidden; background:white; box-shadow:0 4px 12px rgba(0,0,0,.05); margin-bottom:30px; }
.styled-table th, .styled-table td { padding:12px 16px; border-bottom:1px solid #ddd; text-align:left; }
.styled-table th { background:#f5f5f5; font-weight:600; }
.faculty-section { margin-bottom:40px; }

.active-banner {
  background-color: #e8f8f5;
  color: #1e8449;
  padding: 10px 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
  font-weight: 500;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5000;
}
.modal {
  background: white;
  padding: 25px 30px;
  border-radius: 10px;
  width: 400px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,.2);
}
.modal-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 20px;
}
.modal-actions .confirm {
  background-color: #27ae60;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
}
.modal-actions .cancel {
  background-color: #ccc;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
}
</style>
