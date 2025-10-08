<template>
  <div v-if="showCourseModal" class="modal-overlay" @click="handleModalClick">
    <div class="modal-content" @click.stop>
      <h3>Add New Course/Section</h3>

      <form @submit.prevent="addCourse" class="grid-row gap-4">
        <!-- Course/Section -->
        <div class="form-group col-6">
          <label>Course/Section:</label>
          <input
            v-model="courseForm.name"
            type="text"
            placeholder="e.g., BSIT 3-A"
            required
          />
        </div>

        <!-- Year Level -->
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

        <!-- Students -->
        <div class="form-group col-6">
          <label>Number of Students:</label>
          <input
            v-model.number="courseForm.students"
            type="number"
            placeholder="e.g., 45"
            min="1"
            required
          />
        </div>

        <!-- Curriculum -->
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
                {{ curr.filename }}
              </option>
            </select>

            <!-- Upload button -->
            <label
              for="curriculum-upload"
              class="upload-icon col-2"
              title="Upload Curriculum"
              style="cursor: pointer; text-align: center;"
            >
              📁
            </label>
            <input
              id="curriculum-upload"
              type="file"
              @change="uploadCurriculum"
              accept=".xlsx"
              style="display: none;"
            />
          </div>
        </div>

        <!-- Buttons -->
        <div class="modal-buttons col-12 flex justify-end gap-2">
          <button type="button" @click="$emit('update:showCourseModal', false)">Cancel</button>
          <button type="submit">Add</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: "CourseModal",
  props: {
    showCourseModal: Boolean,
    courseForm: Object,
    curriculumList: Array
  },
  methods: {
    handleModalClick() {
      this.$emit("update:showCourseModal", false);
    },
    uploadCurriculum(event) {
      this.$emit("upload", event);
    },
    addCourse() {
      this.$emit("submit", this.courseForm);
    }
  }
};
</script>
