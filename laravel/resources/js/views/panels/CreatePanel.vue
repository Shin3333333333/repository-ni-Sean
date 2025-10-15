<template>
  <div class="create-panel">
    <!-- Header -->
    <div class="header grid">
      <div class="col-4 left-controls">
        <button @click="generateSchedule" class="generate-btn">Generate</button>
        <input
          type="text"
          v-model="academicYear"
          placeholder="Academic Year (e.g., 2025-2026)"
          class="filter-select"
          :disabled="scheduleGenerated"
          :class="{ 'disabled-field': scheduleGenerated }"
        />
        <select
          v-model="semester"
          class="filter-select"
          :disabled="scheduleGenerated"
          :class="{ 'disabled-field': scheduleGenerated }"
        >
          <option value="1st Semester">1st Semester</option>
          <option value="2nd Semester">2nd Semester</option>
        </select>

        <!-- Filters -->
        <select
          v-if="Object.keys(groupedSchedules).length > 0"
          v-model="selectedFaculty"
          class="filter-select"
        >
          <option value="All">All Faculties</option>
          <option
            v-for="faculty in Object.keys(groupedSchedules)"
            :key="faculty"
            :value="faculty"
          >
            {{ faculty }}
          </option>
        </select>

        <select
          v-if="uniqueCourses.length > 0"
          v-model="selectedCourse"
          class="filter-select"
        >
          <option value="All">All Courses</option>
          <option v-for="course in uniqueCourses" :key="course" :value="course">
            {{ course }}
          </option>
        </select>
      </div>

      <!-- Right Controls -->
      <div class="col-4 right-controls">
        <button
          v-if="Object.keys(groupedSchedules).length > 0"
          @click="toggleEditMode"
          class="edit-btn"
        >
          {{ editMode ? 'Finish Editing' : 'Edit' }}
        </button>
        <button
          v-if="Object.keys(groupedSchedules).length > 0"
          @click="saveSchedule('pending')"
          class="save-btn"
        >
          Save as Pending
        </button>
        <button
          v-if="Object.keys(groupedSchedules).length > 0"
          @click="saveSchedule('finalized')"
          class="save-btn"
        >
          Finalize
        </button>
        <button @click="exitSchedule" class="exit-btn">Exit</button>
      </div>
    </div>

    <!-- Toast Message -->
    <div v-if="message" :class="['toast', messageType]">{{ message }}</div>

    <!-- Schedules -->
    <div v-if="Object.keys(groupedSchedules).length > 0">
      <div
        v-for="(entries, groupKey) in filteredSchedules"
        :key="groupKey"
        class="faculty-section"
      >
        <h3>{{ groupKey }}</h3>
        <table border="1" cellpadding="5" cellspacing="0" class="create-table">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Time</th>
              <th>Classroom</th>
              <th>Course Section</th>
              <th>Units</th>
              <th v-if="selectedCourse !== 'All'">Faculty</th>
              <th v-if="editMode">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(entry, index) in entries" :key="index">
              <td v-if="editMode"><input v-model="entry.subject" /></td>
              <td v-else>{{ entry.subject }}</td>

              <td v-if="editMode"><input v-model="entry.time" /></td>
              <td v-else>{{ entry.time }}</td>

              <td v-if="editMode"><input v-model="entry.classroom" /></td>
              <td v-else>{{ entry.classroom }}</td>

              <td v-if="editMode"><input v-model="entry.courseCode" /></td>
              <td v-else>{{ entry.courseCode }}</td>

              <td v-if="editMode"><input v-model="entry.units" type="number" min="0" /></td>
              <td v-else>{{ entry.units }}</td>

              <td v-if="selectedCourse !== 'All'">{{ entry.faculty }}</td>

              <td v-if="editMode">
                <button @click="removeEntry(groupKey, index)">Remove</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="total-units">Total Load Units: {{ totalUnitsFiltered[groupKey] || 0 }}</div>

        <button
          v-if="editMode"
          @click="addEntry(groupKey)"
          class="add-btn small"
        >
          + Add Row
        </button>
      </div>
    </div>

    <div v-else class="no-schedule">
      <p>No schedule yet. Click "Generate" to create one.</p>
    </div>

    <LoadingModal />
  </div>
</template>

<script>
import LoadingModal from "../../components/LoadingModal.vue";
import { useLoading } from "../../composables/useLoading";
import "/resources/css/create.css";

export default {
  components: { LoadingModal },
  data() {
    return {
      groupedSchedules: {},
      editMode: false,
      message: "",
      messageType: "",
      academicYear: "",
      semester: "1st Semester",
      selectedFaculty: "All",
      selectedCourse: "All",
    };
  },
  setup() {
    const { show, hide } = useLoading();
    return { show, hide };
  },
  computed: {
    scheduleGenerated() {
      return Object.keys(this.groupedSchedules).length > 0;
    },
    uniqueCourses() {
      const set = new Set();
      Object.values(this.groupedSchedules).forEach((entries) =>
        entries.forEach((e) => e.courseCode && set.add(e.courseCode))
      );
      return Array.from(set).sort();
    },
    filteredSchedules() {
      let schedules = this.groupedSchedules;
      if (this.selectedFaculty !== "All") {
        schedules = { [this.selectedFaculty]: schedules[this.selectedFaculty] };
      }
      if (this.selectedCourse !== "All") {
        const filtered = {};
        Object.entries(schedules).forEach(([faculty, entries]) => {
          entries.forEach((entry) => {
            if (entry.courseCode === this.selectedCourse) {
              if (!filtered[entry.courseCode]) filtered[entry.courseCode] = [];
              filtered[entry.courseCode].push({ ...entry, faculty });
            }
          });
        });
        schedules = filtered;
      }
      return schedules;
    },
    totalUnitsFiltered() {
      const totals = {};
      Object.entries(this.filteredSchedules).forEach(([k, v]) => {
        totals[k] = v.reduce((sum, e) => sum + (Number(e.units) || 0), 0);
      });
      return totals;
    },
  },
  methods: {
    showMessage(text, type) {
      this.message = text;
      this.messageType = type;
      setTimeout(() => (this.message = ""), 4000);
    },
    showSuccess(text) {
      this.showMessage(text, "success");
    },
    showError(text) {
      this.showMessage(text, "error");
    },
    toggleEditMode() {
      this.editMode = !this.editMode;
    },
    addEntry(groupKey) {
      this.groupedSchedules[groupKey].push({
        subject: "",
        time: "",
        classroom: "",
        courseCode: "",
      });
    },
    removeEntry(groupKey, index) {
      this.groupedSchedules[groupKey].splice(index, 1);
    },
    async generateSchedule() {
      this.show();
      try {
        const res = await fetch("/api/generate-schedule", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            academicYear: this.academicYear,
            semester: this.semester,
          }),
        });
        const result = await res.json();
        if (result.success && result.schedule) {
          const grouped = {};
          result.schedule.forEach((s) => {
            const faculty = s.faculty || "Unknown";
            if (!grouped[faculty]) grouped[faculty] = [];
            grouped[faculty].push({
              subject: s.subject,
              time: s.time,
              classroom: s.room,
              courseCode: s.course,
              units: s.units || 0,
            });
          });
          this.groupedSchedules = grouped;
          this.showSuccess(result.message || "Schedule generated successfully!");
        } else {
          this.showError(result.message || "Failed to generate schedule.");
        }
      } catch (err) {
        console.error(err);
        this.showError("Could not generate schedule.");
      } finally {
        this.hide();
      }
    },
    async saveSchedule(status = "pending") {
      if (!this.academicYear || !this.semester)
        return this.showError("Academic Year and Semester are required!");

      this.show();
      try {
        const scheduleArray = [];
        Object.entries(this.groupedSchedules).forEach(([faculty, entries]) => {
          entries.forEach((entry) =>
            scheduleArray.push({
              faculty,
              subject: entry.subject,
              time: entry.time,
              classroom: entry.classroom,
              course_code: entry.courseCode,
              units: entry.units || 0,
              academic_year: this.academicYear,
              semester: this.semester,
              status,
            })
          );
        });
        const res = await fetch("/api/save-schedule", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ schedule: scheduleArray }),
        });
        const result = await res.json();
        result.success
          ? this.showSuccess(`Schedule ${status} saved successfully!`)
          : this.showError(result.message || "Failed to save schedule.");
      } catch (err) {
        console.error(err);
        this.showError("Could not save schedule.");
      } finally {
        this.hide();
      }
    },
    exitSchedule() {
      this.groupedSchedules = {};
      this.selectedFaculty = "All";
      this.selectedCourse = "All";
      this.academicYear = "";
      this.semester = "1st Semester";
      this.editMode = false;
    },
  },
};
</script>
