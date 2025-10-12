<template>
  <div v-if="show" class="modal-overlay" @click="handleModalClick">
    <div
      class="modal-content"
      :class="{ 'modal-large': courseForm.id }"
      @click.stop
    >
      <h3>{{ courseForm.id ? 'Edit' : 'Add' }} Course/Section</h3>

      <form @submit.prevent="addCourse">
        <!-- Basic Course Details -->
        <div class="grid-row gap-4">
          <div class="form-group col-6">
            <label>Course/Section:</label>
            <input
              v-model="courseForm.name"
              type="text"
              placeholder="e.g., BSIT 3-A"
              required
            />
          </div>

          <div class="form-group col-6">
            <label>Year Level:</label>
            <select v-model="courseForm.year" required>
              <option value="">Select Year</option>
              <option value="1st">1st Year</option>
              <option value="2nd">2nd Year</option>
              <option value="3rd">3rd Year</option>
              <option value="4th">4th Year</option>
            </select>
          </div>

          <div class="form-group col-6">
            <label>Number of Students:</label>
            <input v-model.number="courseForm.students" type="number" min="1" required />
          </div>

          <div class="form-group col-6">
            <label>Curriculum:</label>
            <div class="grid-row gap-2">
              <select class="col-10" v-model="courseForm.curriculum_id" required>
                <option value="">Select Curriculum</option>
                <option
                  v-for="curr in curriculumList"
                  :key="curr.id"
                  :value="curr.id"
                >
                  {{ curr.name }}
                </option>
              </select>
              <label
                for="curriculum-upload"
                class="upload-icon col-2"
                title="Upload Curriculum"
                style="cursor: pointer; text-align: center;"
              >📁</label>
              <input
                id="curriculum-upload"
                type="file"
                @change="uploadCurriculum"
                accept=".xlsx,.xls"
                style="display: none;"
              />
            </div>
          </div>
        </div>

        <!-- Subjects Table (Only when editing) -->
        <div
          class="form-group col-12"
          v-if="courseForm.id && courseForm.subjects && courseForm.subjects.length"
        >
          <div class="flex justify-between items-center mb-1">
            <label>Subjects:</label>

            <!-- Semester Filter -->
            <select v-model.number="selectedSemester" class="semester-filter">
              <option value="">All Semesters</option>
              <option
                v-for="semester in semesterList"
                :key="semester.id"
                :value="Number(semester.id)"
              >
                {{ semester.name }}
              </option>
            </select>

          </div>

          <div class="subject-table-wrapper">
            <table class="subject-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Title</th>
                  <th>Units</th>
                  <th>Hours</th>
                  <th>Pre-requisite</th>
                  <th>Type</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(subject, index) in filteredSubjects"
                  :key="subject.id || index"
                >
                  <td><input v-model="subject.subject_code" type="text" required /></td>
                  <td><input v-model="subject.subject_title" type="text" required /></td>
                  <td><input v-model.number="subject.units" type="number" min="0" /></td>
                  <td><input v-model.number="subject.hours" type="number" min="0" /></td>
                  <td><input v-model="subject.pre_requisite" type="text" /></td>
                  <td><input v-model="subject.type" type="text" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Buttons -->
        <div class="modal-buttons flex justify-end gap-2 mt-4">
          <button type="button" @click="closeModal" class="btn-cancel">Cancel</button>
          <button type="submit" class="btn-primary">
            {{ courseForm.id ? 'Update' : 'Add' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: "CourseModal",
  props: {
    show: Boolean,
    courseForm: Object,
    curriculumList: Array,
    semesterList: Array
  },
  emits: ["update:show", "update:courseForm", "submit", "upload"],
  data() {
    return {
      selectedSemester: ""  // currently selected semester
    };
  },
  computed: {
    // Filter subjects by selected semester
    filteredSubjects() {
      if (!this.selectedSemester) return this.courseForm.subjects || [];
      return (this.courseForm.subjects || []).filter(
        s => Number(s.semester_id) === this.selectedSemester
      );
    }
  },
  watch: {
    // Initialize selectedSemester when modal opens or courseForm changes
    courseForm: {
      immediate: true,
      handler(val) {
        if (val.subjects && val.subjects.length) {
          // Default to the semester of the first subject
          this.selectedSemester = Number(val.subjects[0].semester_id);
        } else {
          this.selectedSemester = "";
        }
      }
    }
  },
  methods: {
    handleModalClick() {
      this.$emit("update:show", false);
    },
    closeModal() {
      this.$emit("update:show", false);
    },
    uploadCurriculum(event) {
      this.$emit("upload", event);
    },
    addCourse() {
      this.$emit("submit", this.courseForm);
      this.$emit("update:show", false);
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
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  width: 600px;
  max-height: 85vh;
  overflow-y: auto;
  z-index: 10000 !important;
}

.modal-large {
  width: 90vw;
  max-width: 1100px;
  height: 85vh;
  overflow-y: auto;
}

/* --- Table Styling --- */
.subject-table-wrapper {
  overflow-x: auto;
  max-width: 100%;
}

.subject-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 0.5rem;
  table-layout: fixed;
}

.subject-table th,
.subject-table td {
  border: 1px solid #ccc;
  padding: 6px 8px;
  text-align: left;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Column Width Balancing */
.subject-table th:nth-child(1),
.subject-table td:nth-child(1) {
  width: 10%; /* Code */
}

.subject-table th:nth-child(2),
.subject-table td:nth-child(2) {
  width: 40%; /* Title gets more space */
  white-space: normal; /* allow wrapping if long */
  word-wrap: break-word;
}

.subject-table th:nth-child(3),
.subject-table td:nth-child(3),
.subject-table th:nth-child(4),
.subject-table td:nth-child(4) {
  width: 10%; /* Units & Hours narrower */
  text-align: center;
}

.subject-table th:nth-child(5),
.subject-table td:nth-child(5) {
  width: 20%; /* Pre-requisite */
}

.subject-table th:nth-child(6),
.subject-table td:nth-child(6) {
  width: 10%; /* Type */
}

/* --- Buttons --- */
.modal-buttons button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
}

.btn-cancel {
  background: #ccc;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}
</style>
