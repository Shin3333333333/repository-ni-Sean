<template>
  <div v-if="show" class="modal-overlay" @click="handleModalClick">
    <div
      class="modal-content"
      :class="{ 'modal-large': courseForm.id }"
      @click.stop
    >
      <div class="modal-header">
        <h3 class="modal-title">{{ courseForm.id ? 'Edit' : 'Add' }} Course/Section</h3>
      </div>

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
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
              <option value="4th Year">4th Year</option>
            </select>
          </div>

          <div class="form-group col-6">
            <label>Curriculum:</label>
            <div class="grid-row gap-2">
              <!-- Dropdown always available -->
              <select class="form-group col-10 " v-model="courseForm.curriculum_id" required>
                <option value="">Select Curriculum</option>
                <option
                  v-for="curr in curriculumList"
                  :key="curr.id"
                  :value="curr.id"
                >
                  {{ curr.name }}
                </option>
              </select>

              <!-- Upload only visible when adding a new course -->
              <template v-if="!courseForm.id">
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
              </template>
            </div>
          </div>
        </div>

        <!-- Subjects Table (Only when editing) -->
        <div
      class="form-group col-12"
      v-if="courseForm.id"
    >

          <div class="flex justify-between items-center mb-1 section-header">
            <label>Subjects:</label>

              <select v-model="selectedSemesterKey" class="semester-filter fancy-select">
      <option value="">All Semesters</option>
      <option
        v-for="option in semesterFilterOptions"
        :key="option.key"
        :value="option.key"
      >
        {{ option.label }}
      </option>
    </select>


          </div>

          <div class="subject-table-wrapper">
            <table class="subject-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Title</th>
                  <th>LEC Units</th>
                  <th>LAB Units</th>
                  <th>Total Units</th>
                  <th>Pre-requisite</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(subject, index) in filteredSubjects"
                  :key="subject.id || index"
                >
                  <td><input v-model="subject.subject_code" type="text" required /></td>
                  <td><input v-model="subject.subject_title" type="text" required /></td>
                  <td><input v-model.number="subject.lec_units" type="number" min="0" /></td>
                  <td><input v-model.number="subject.lab_units" type="number" min="0" /></td>
                  <td><input v-model.number="subject.total_units" type="number" min="0" /></td> 
                  <td><input v-model="subject.pre_requisite" type="text" /></td>
                  <td><button @click.prevent="removeSubject(index)" class="btn-sm btn-danger">Delete</button></td>
              
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Buttons -->
        <div class="modal-buttons flex justify-end gap-2 mt-4">
          <button type="button" @click="closeModal" class="btn btn-cancel">Cancel</button>
          <button type="submit" class="btn btn-primary">
            {{ courseForm.id ? 'Update' : 'Add' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { useLoading } from '../../../../composables/useLoading';
import { useToast } from '../../../../composables/useToast';

export default {
  name: "CourseModal",
  props: {
    show: Boolean,
    courseForm: Object,
    curriculumList: Array,
    semesterList: Array,
  },
  emits: ["update:show", "update:courseForm", "submit", "upload"],
  setup() {
    const { show, hide } = useLoading();
    const { success, error } = useToast();
    return { showLoading: show, hideLoading: hide, success, error };
  },
  data() {
    return {
      
      selectedSemesterKey: "", // current year-semester filter
      allCurriculumSubjects: [], // store all subjects for selected curriculum
    };
  },
  computed: {
    // Filter subjects by selected semester and by course ID (when editing)
    filteredSubjects() {
      let subjects = this.courseForm.subjects || [];

      // When editing, show only subjects linked to this course
      if (this.courseForm.id) {
        subjects = subjects.filter(
          (s) => s.course_id === this.courseForm.id
        );
      }

      // If semester filter is selected, narrow down further
      if (this.selectedSemesterKey) {
        subjects = subjects.filter(
          (s) => `${s.year_level}-${s.semester_id}` === this.selectedSemesterKey
        );
      }

      return subjects;
    },

    // Generate unique semester filter options based on subjects
    semesterFilterOptions() {
      if (!this.courseForm.subjects) return [];
      const options = [];
      this.courseForm.subjects.forEach((sub) => {
        const key = `${sub.year_level}-${sub.semester_id}`;
        const label = `${sub.year_level} Year – ${this.getSemesterName(sub.semester_id)}`;
        if (!options.some((o) => o.key === key)) {
          options.push({ key, label });
        }
      });
      return options;
    },
  },
  watch: {
    // Set semester filter when courseForm changes
    courseForm: {
      immediate: true,
      handler(val) {
        if (!val.subjects) val.subjects = [];
        if (val.subjects.length) {
          const first = val.subjects[0];
          this.selectedSemesterKey = `${first.year_level}-${first.semester_id}`;
        } else {
          this.selectedSemesterKey = "";
        }
      },
    },

    // Only load curriculum subjects when creating a new course
"courseForm.curriculum_id": {
  async handler(curriculumId) {
    // ✅ Only when adding new course; fetch regardless of year to cache
    if (!curriculumId || this.courseForm.id) return;

    try {
      const res = await fetch(`/api/curriculums/${curriculumId}/subjects`);
      const subjects = await res.json();

      // Cache all subjects of the selected curriculum
      this.allCurriculumSubjects = Array.isArray(subjects) ? subjects : [];

      // If a year is already selected, filter immediately; otherwise wait for year selection
      let filtered = this.courseForm.year
        ? this.allCurriculumSubjects.filter((s) => s.year_level === this.courseForm.year)
        : [];

      // ✅ Remove duplicates (same subject_code + subject_title)
      const seen = new Set();
      filtered = filtered.filter(s => {
        const key = `${s.subject_code.trim().toLowerCase()}|${s.subject_title.trim().toLowerCase()}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });

      // Clone into courseForm.subjects (temporary)
      this.courseForm.subjects = filtered.map((s) => ({
        ...s,
        id: s.id,
        course_id: null, // not yet assigned
      }));

      // Default semester selection
      if (this.courseForm.subjects.length) {
        const first = this.courseForm.subjects[0];
        this.selectedSemesterKey = `${first.year_level}-${first.semester_id}`;
      } else {
        this.selectedSemesterKey = "";
      }
    } catch (err) {
      console.error("Failed to load curriculum subjects:", err);
    }
  },
},

// Re-filter subjects when the Year Level changes (only during Add)
"courseForm.year": {
  async handler(newYear) {
    if (this.courseForm.id) return; // do not auto-alter when editing existing
    if (!newYear || !this.courseForm.curriculum_id) return;

    // Ensure cache is loaded; if empty, fetch now
    if (!this.allCurriculumSubjects.length) {
      try {
        const res = await fetch(`/api/curriculums/${this.courseForm.curriculum_id}/subjects`);
        const subjects = await res.json();
        this.allCurriculumSubjects = Array.isArray(subjects) ? subjects : [];
      } catch (e) {
        console.error('Failed to load curriculum subjects on year change', e);
      }
    }

    // Use cached subjects from selected curriculum
    let filtered = this.allCurriculumSubjects.filter(
      (s) => s.year_level === newYear
    );

    // De-duplicate by subject_code + subject_title
    const seen = new Set();
    filtered = filtered.filter(s => {
      const key = `${(s.subject_code||'').trim().toLowerCase()}|${(s.subject_title||'').trim().toLowerCase()}`;
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });

    this.courseForm.subjects = filtered.map((s) => ({
      ...s,
      id: s.id,
      course_id: null,
    }));

    if (this.courseForm.subjects.length) {
      const first = this.courseForm.subjects[0];
      this.selectedSemesterKey = `${first.year_level}-${first.semester_id}`;
    } else {
      this.selectedSemesterKey = "";
    }
  }
}

  },
  methods: {
    getSemesterName(id) {
      const sem = this.semesterList.find((s) => s.id === Number(id));
      return sem ? sem.name : "";
    },
    handleModalClick() {
      this.$emit("update:show", false);
    },
    closeModal() {
      this.$emit("update:show", false);
    },
    uploadCurriculum(event) {
      this.$emit("upload", event);
    },
    removeSubject(index) {
      this.courseForm.subjects.splice(index, 1);
    },
    
    async addCourse() {
      this.showLoading();
      try {
        // Normalize year format before submission
        if (this.courseForm.year && !this.courseForm.year.includes("Year")) {
          this.courseForm.year = `${this.courseForm.year} Year`;
        }
        this.$emit("submit", this.courseForm);
        this.success('Course saved successfully!');
        this.$emit("update:show", false);
      } catch (error) {
        this.error('Failed to save course.');
        console.error("Error adding course:", error);
      } finally {
        this.hideLoading();
      }
    },
  },
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
  box-shadow: 0 20px 45px rgba(2, 6, 23, 0.25), 0 8px 20px rgba(2, 6, 23, 0.15);
  border: 1px solid #e5e7eb;
}

.modal-large {
  width: 90vw;
  max-width: 1100px;
  height: 85vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
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
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  overflow: hidden;
}

.subject-table th,
.subject-table td {
  border-bottom: 1px solid #eef2f7;
  padding: 10px 12px;
  text-align: left;
  overflow: hidden;
  text-overflow: ellipsis;
}

.subject-table thead th {
  background: linear-gradient(180deg, #f8fafc, #f1f5f9);
  color: #0f172a;
  border-bottom: 1px solid #e5e7eb;
}

.subject-table th:nth-child(6) { width: 15%; }

.subject-table th:nth-child(7) { 
  width: 15%; 
  text-align: center;
}

.subject-table tbody tr:nth-child(odd) {
  background: #fcfdff;
}

.section-header {
  padding: 8px 10px;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.subject-table td:nth-child(3),
.subject-table td:nth-child(4),
.subject-table td:nth-child(5) {
  text-align: center;
}

/* --- Buttons --- */
.modal-buttons .btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all .15s ease;
}

.btn-cancel {
  background: #ccc;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  box-shadow: 0 10px 20px rgba(59,130,246,0.25), 0 4px 10px rgba(59,130,246,0.2);
}

.btn:hover { transform: translateY(-1px); }
.btn:active { transform: translateY(0); }

/* Inputs and selects */
.form-group input,
.form-group select {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 10px 12px;
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}

.btn-danger {
  background-color: #dc3545;
  color: white;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: .875rem;
  border-radius: 0.2rem;
}

.fancy-select {
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  min-width: 280px;
  height: 40px;
}
</style>