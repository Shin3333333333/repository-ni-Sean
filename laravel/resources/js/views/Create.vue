<template>
  <div class="create-page">
    <!-- Header -->
    <div class="header grid">

      <!-- Title on top (centered full width) -->
      <div class="col-12 title-top">
        <h2 class="title">Create Schedule</h2>
      </div>

      <!-- Left controls -->
      <div class="col-4 left-controls">
        <!-- Academic Year Input -->
    
        <button
          @click="generateSchedule"
          class="generate-btn"
        >
          Generate
        </button>
        <!-- Academic Year Input -->
          <input
            type="text"
            v-model="academicYear"
            placeholder="Academic Year (e.g., 2025-2026)"
            class="filter-select"
            :disabled="scheduleGenerated"
            :class="{ 'disabled-field': scheduleGenerated }"
          />

          <!-- Semester Dropdown -->
          <select
            v-model="semester"
            class="filter-select"
            :disabled="scheduleGenerated"
            :class="{ 'disabled-field': scheduleGenerated }"
          >
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
          </select>


        <!-- Faculty filter -->
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

        <!-- Course filter -->
        <select
          v-if="uniqueCourses.length > 0"
          v-model="selectedCourse"
          class="filter-select"
        >
          <option value="All">All Courses</option>
          <option
            v-for="course in uniqueCourses"
            :key="course"
            :value="course"
          >
            {{ course }}
          </option>
        </select>
      </div>

      <!-- Right controls -->
      <!-- Right controls -->
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
          @click="saveSchedule"
          class="save-btn"
        >
          Save
        </button>

        <button
          @click="exitSchedule"
          class="exit-btn"
        >
          Exit
        </button>
      </div>

    </div>

    <!-- Messages -->
    <div v-if="message" :class="['message', messageType]">
      {{ message }}
    </div>

    <!-- Grouped schedule display -->
    <div v-if="groupedSchedules && Object.keys(groupedSchedules).length > 0">
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
              <th>Classroom #</th>
              <th>Course Section</th>
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

              <td v-if="editMode && selectedCourse === 'All'"><input v-model="entry.courseCode" /></td>
              <td v-else>{{ entry.courseCode }}</td>

              <td v-if="selectedCourse !== 'All'">{{ entry.faculty }}</td>
              
              <td v-if="editMode">
                <button type="button" @click="removeEntry(groupKey, index)">Remove</button>
              </td>
            </tr>
          </tbody>
        </table>

        <button
          v-if="editMode"
          type="button"
          @click="addEntry(groupKey)"
          class="add-btn small"
        >
          + Add Row
        </button>
      </div>
    </div>

    <!-- Message if no schedule -->
    <div v-else class="no-schedule">
      <p>No schedule yet. Click "Generate" to create one.</p>
    </div>

    <!-- Reusable Loading Modal -->
    <LoadingModal />
  </div>
</template>

<script>
import '/resources/css/create.css';
import LoadingModal from '../components/LoadingModal.vue';
import { useLoading } from '../composables/useLoading';

export default {
  components: { LoadingModal },
  data() {
    return {
      groupedSchedules: {},
      message: '',
      messageType: '',
      editMode: false,
      selectedFaculty: 'All',
      selectedCourse: 'All',
      academicYear: '',
      semester: '1st Semester',
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
      const courses = new Set();
      Object.values(this.groupedSchedules).forEach(entries => {
        entries.forEach(entry => {
          if (entry.courseCode) courses.add(entry.courseCode);
        });
      });
      return Array.from(courses).sort();
    },
    filteredSchedules() {
      let schedules = this.groupedSchedules;

      if (this.selectedFaculty !== 'All') {
        schedules = { [this.selectedFaculty]: schedules[this.selectedFaculty] };
      }

      if (this.selectedCourse !== 'All') {
        const filtered = {};
        Object.entries(schedules).forEach(([faculty, entries]) => {
          entries.forEach(entry => {
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
  },
  methods: {
    
    showMessage(text, type) {
      this.message = text;
      this.messageType = type;
      setTimeout(() => {
        this.message = '';
        this.messageType = '';
      }, 5000);
    },
    showSuccess(text) { this.showMessage(text, 'success'); },
    showError(text) { this.showMessage(text, 'error'); },
    toggleEditMode() { this.editMode = !this.editMode; },
    addEntry(groupKey) {
      this.groupedSchedules[groupKey].push({ subject: '', time: '', classroom: '', courseCode: '' });
    },
    
    removeEntry(groupKey, index) {
      this.groupedSchedules[groupKey].splice(index, 1);
    },async saveSchedule() {
    if (!this.academicYear || !this.semester) {
      this.showError('Academic Year and Semester are required!');
      return;
    }

    try {
      this.show(); // show loading

      // Convert groupedSchedules into an array for easier storage
      const scheduleArray = [];
      Object.entries(this.groupedSchedules).forEach(([faculty, entries]) => {
        entries.forEach(entry => {
          scheduleArray.push({
            faculty,
            subject: entry.subject,
            time: entry.time,
            classroom: entry.classroom,
            courseCode: entry.courseCode,
            academicYear: this.academicYear,
            semester: this.semester
          });
        });
      });

      const res = await fetch("/api/save-schedule", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ schedule: scheduleArray })
      });

      const result = await res.json();
      if (result.success) {
        this.showSuccess("Schedule saved successfully!");
      } else {
        this.showError(result.message || "Failed to save schedule.");
      }
    } catch (err) {
      console.error(err);
      this.showError("Could not save schedule. Check console.");
    } finally {
      this.hide();
    }
  },

  exitSchedule() {
    // Reset everything or redirect to start page
    this.groupedSchedules = {};
    this.selectedFaculty = 'All';
    this.selectedCourse = 'All';
    this.academicYear = '';
    this.semester = '1st Semester';
    this.editMode = false;

    // If you have routing:
    // this.$router.push('/start');
  },
    async generateSchedule() {
      this.show();
      this.message = '';
      this.groupedSchedules = {};
      this.editMode = false;

      try {
        const res = await fetch("/api/generate-schedule", {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            academicYear: this.academicYear,
            semester: this.semester
          })
        });
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const result = await res.json();

        if (result.success && result.schedule) {
          const grouped = {};
          result.schedule.forEach((s) => {
            const faculty = s.faculty || 'Unknown';
            if (!grouped[faculty]) grouped[faculty] = [];
            grouped[faculty].push({
              subject: s.subject,
              time: s.time,
              classroom: s.room,
              courseCode: s.course,
            });
          });
          this.groupedSchedules = grouped;
          this.selectedFaculty = 'All';
          this.selectedCourse = 'All';
          this.showSuccess(result.message || 'Schedule generated successfully!');
        } else {
          this.showError(result.message || 'Failed to generate schedule');
        }
      } catch (err) {
        console.error(err);
        this.showError('Could not generate schedule. Check console.');
      } finally {
        this.hide();
      }
    },
  },
};
</script>
