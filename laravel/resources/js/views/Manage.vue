<template>
  <div class="manage-page">

    <div class="top-controls">
      <div class="switcher">
        <button
          :class="{ active: activeTable === 'faculty' }"
          @click="activeTable = 'faculty'"
        >
          Faculty
        </button>
        <button
          :class="{ active: activeTable === 'room' }"
          @click="activeTable = 'room'"
        >
          Room
        </button>
        <button
          :class="{ active: activeTable === 'course' }"
          @click="activeTable = 'course'"
        >
          Course/Section
        </button>
      </div>

      <button class="add-btn" @click="addEntry">+ Add</button>
    </div>

    <!-- tables -->

    <table v-if="activeTable === 'faculty'" class="styled-table">
      <thead>
        <tr>
          <th>Faculty</th>
          <th>Type</th>
          <th>Department</th>
          <th>Max Load</th>
          <th>Time Unavailable</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(faculty, index) in facultyList" :key="faculty.id">
          <td>{{ faculty.name }}</td>
          <td>{{ faculty.type }}</td>
          <td>{{ faculty.department }}</td>
          <td>{{ faculty.maxLoad }}</td>
          <td>{{ faculty.timeUnavailable }}</td>
          <td>
            <span
              class="status"
              :class="faculty.available ? 'status-yes' : 'status-no'"
            >
              {{ faculty.available ? "Available" : "Unavailable" }}
            </span>
          </td>
          <td>
            <button @click="editEntry(index)">Edit</button>
            <button class="delete-btn" @click="removeEntry(index)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <table v-if="activeTable === 'room'" class="styled-table">
      <thead>
        <tr>
          <th>Room</th>
          <th>Capacity</th>
          <th>Type</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(room, index) in roomList" :key="room.id">
          <td>{{ room.name }}</td>
          <td>{{ room.capacity }}</td>
          <td>{{ room.type }}</td>
          <td>
            <span
              class="status"
              :class="room.available ? 'status-yes' : 'status-no'"
            >
              {{ room.available ? "Available" : "Unavailable" }}
            </span>
          </td>
          <td>
            <button @click="editEntry(index)">Edit</button>
            <button class="delete-btn" @click="removeEntry(index)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <table v-if="activeTable === 'course'" class="styled-table">
      <thead>
        <tr>
          <th>Course/Section</th>
          <th>Year Level</th>
          <th>Department</th>
          <th>Students</th>
          <th>Adviser</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(course, index) in courseList" :key="course.id">
          <td>{{ course.name }}</td>
          <td>{{ course.year }}</td>
          <td>{{ course.department }}</td>
          <td>{{ course.students }}</td>
          <td>{{ course.adviser }}</td>
          <td>
            <span
              class="status"
              :class="course.active ? 'status-yes' : 'status-no'"
            >
              {{ course.active ? "Active" : "Inactive" }}
            </span>
          </td>
          <td>
            <button @click="editEntry(index)">Edit</button>
            <button class="delete-btn" @click="removeEntry(index)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Faculty Add Modal -->
    <div v-if="showFacultyModal" class="modal-overlay" @click="handleModalClick">
      <div class="modal-content" @click.stop>
        <h3>Add New Faculty</h3>
        <form @submit.prevent="addFaculty">
          <div class="form-group">
            <label>Faculty Name:</label>
            <input v-model="facultyForm.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Type:</label>
            <select v-model="facultyForm.type" required>
              <option value="">Select Type</option>
              <option value="Full-time">Full-time</option>
              <option value="Part-time">Part-time</option>
            </select>
          </div>
          <div class="form-group">
            <label>Field/Department:</label>
            <input v-model="facultyForm.department" type="text" required />
          </div>
          <div class="form-group">
            <label>Max Load:</label>
            <input v-model.number="facultyForm.maxLoad" type="number" min="1" required />
          </div>
          <div class="form-group">
            <label>Status:</label>
            <select v-model="facultyForm.status" required>
              <option value="">Select Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label>Time Unavailable:</label>
            <div class="time-unavailable">
              <select v-model="facultyForm.day">
                <option value="">Select Day</option>
                <option value="M">Monday</option>
                <option value="T">Tuesday</option>
                <option value="W">Wednesday</option>
                <option value="Th">Thursday</option>
                <option value="F">Friday</option>
                <option value="Sat">Saturday</option>
              </select>
              <input v-model="facultyForm.time" type="text" placeholder="e.g., 1-3PM" />
            </div>
          </div>
          <div class="modal-buttons">
            <button type="button" @click="closeFacultyModal">Cancel</button>
            <button type="submit">Add</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Course/Section Add Modal -->
    <div v-if="showCourseModal" class="modal-overlay" @click="handleModalClick">
      <div class="modal-content" @click.stop>
        <h3>Add New Course/Section</h3>
        <form @submit.prevent="addCourse">
          <div class="form-group">
            <label>Course Section:</label>
            <input v-model="courseForm.name" type="text" placeholder="e.g., BSIT 3-A" required />
          </div>
          <div class="form-group">
            <label>Year/Level:</label>
            <select v-model="courseForm.year" required>
              <option value="">Select Year</option>
              <option value="1st">1st Year</option>
              <option value="2nd">2nd Year</option>
              <option value="3rd">3rd Year</option>
              <option value="4th">4th Year</option>
            </select>
          </div>
          <div class="form-group">
            <label>Curriculum:</label>
            <div class="curriculum-group">
              <select v-model="courseForm.curriculum" required>
                <option value="">Select Curriculum</option>
                <option value="CMO 1">CMO 1</option>
                <option value="CMO 2">CMO 2</option>
                <option value="Custom">Custom</option>
              </select>
              <label for="curriculum-upload" class="upload-icon">📁</label>
              <input id="curriculum-upload" type="file" @change="handleCurriculumUpload" accept=".pdf,.docx" style="display: none;" />
            </div>
          </div>
          <div class="modal-buttons">
            <button type="button" @click="closeCourseModal">Cancel</button>
            <button type="submit">Add</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Manage",
  data() {
    return {
      activeTable: "faculty",
      showFacultyModal: false,
      showCourseModal: false,
      facultyForm: {
        name: '',
        type: '',
        department: '',
        maxLoad: 1,
        status: '',
        day: '',
        time: ''
      },
      courseForm: {
        name: '',
        year: '',
        curriculum: '',
        uploadedFile: null
      },
      nextId: 3, // For generating new IDs (starting after existing ones)
      facultyList: [
        {
          id: 1,
          name: "Mr. Smith",
          type: "Full-time",
          department: "BSIT",
          maxLoad: 18,
          timeUnavailable: "MWF 1-3PM",
          available: true,
        },
      ],
      roomList: [
        { id: 1, name: "Room 101", capacity: 40, type: "Lecture", available: true },
        { id: 2, name: "Lab 201", capacity: 25, type: "Laboratory", available: false },
      ],
      courseList: [
        {
          id: 1,
          name: "BSIT 3-A",
          year: "3rd",
          department: "BSIT",
          students: 45,
          adviser: "Dr. Brown",
          active: true,
        },
      ],
    };
  },
  watch: {
    showFacultyModal(newVal) {
      if (newVal) {
        document.body.classList.add('modal-open');
      } else {
        document.body.classList.remove('modal-open');
      }
    },
    showCourseModal(newVal) {
      if (newVal) {
        document.body.classList.add('modal-open');
      } else {
        document.body.classList.remove('modal-open');
      }
    }
  },
  methods: {
    addEntry() {
      if (this.activeTable === 'faculty') {
        this.showFacultyModal = true;
        this.resetFacultyForm();
      } else if (this.activeTable === 'course') {
        this.showCourseModal = true;
        this.resetCourseForm();
      } else if (this.activeTable === 'room') {
        alert(`Open Add Form for ${this.activeTable}`);
      }
    },
    handleModalClick(event) {
      // Prevent closing on overlay click as per requirements
      event.stopPropagation();
      return false;
    },
    closeFacultyModal() {
      this.showFacultyModal = false;
      this.resetFacultyForm();
    },
    closeCourseModal() {
      this.showCourseModal = false;
      this.resetCourseForm();
    },
    resetFacultyForm() {
      this.facultyForm = {
        name: '',
        type: '',
        department: '',
        maxLoad: 1,
        status: '',
        day: '',
        time: ''
      };
    },
    resetCourseForm() {
      this.courseForm = {
        name: '',
        year: '',
        curriculum: '',
        uploadedFile: null
      };
    },
    addFaculty() {
      if (!this.facultyForm.name || !this.facultyForm.type || !this.facultyForm.department || 
          !this.facultyForm.maxLoad || !this.facultyForm.status) {
        alert('Please fill in all required fields.');
        return;
      }
      const timeUnavailable = this.facultyForm.day && this.facultyForm.time 
        ? `${this.facultyForm.day}${this.facultyForm.time}` 
        : 'None';
      this.facultyList.push({
        id: this.nextId++,
        name: this.facultyForm.name,
        type: this.facultyForm.type,
        department: this.facultyForm.department,
        maxLoad: this.facultyForm.maxLoad,
        timeUnavailable: timeUnavailable,
        available: this.facultyForm.status === 'Active'
      });
      this.closeFacultyModal();
      alert('Faculty added successfully!');
    },
    addCourse() {
      if (!this.courseForm.name || !this.courseForm.year || !this.courseForm.curriculum) {
        alert('Please fill in all required fields.');
        return;
      }
      // Note: Department and other fields like students/adviser are not in the form; assuming defaults or to be added later
      this.courseList.push({
        id: this.nextId++,
        name: this.courseForm.name,
        year: this.courseForm.year,
        department: 'BSIT', // Default; can be made dynamic
        students: 0, // Default
        adviser: 'TBD', // Default
        active: true,
        curriculum: this.courseForm.curriculum,
        uploadedFile: this.courseForm.uploadedFile ? this.courseForm.uploadedFile.name : null
      });
      this.closeCourseModal();
      alert('Course/Section added successfully!');
    },
    handleCurriculumUpload(event) {
      const file = event.target.files[0];
      if (file) {
        this.courseForm.uploadedFile = file;
        alert(`File uploaded: ${file.name}`);
      }
    },
    editEntry(index) {
      alert(`Edit ${this.activeTable} at index ${index}`);
    },
    removeEntry(index) {
      if (confirm("Are you sure you want to remove this entry?")) {
        if (this.activeTable === "faculty") this.facultyList.splice(index, 1);
        if (this.activeTable === "room") this.roomList.splice(index, 1);
        if (this.activeTable === "course") this.courseList.splice(index, 1);
      }
    },
  },
};
</script>

<style scoped>
.manage-page {
  padding: 20px;
}

.top-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.switcher {
  display: flex;
  gap: 12px;
}

.switcher button {
  padding: 8px 14px;
  border: none;
  border-radius: 8px;
  background: #e0e0e0;
  cursor: pointer;
  font-weight: 600;
}

.switcher button.active {
  background: #000;
  color: #fff;
}

.add-btn {
  background: #000;
  color: #fff;
  padding: 8px 14px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.styled-table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 12px;
  overflow: hidden;
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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

.status {
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
}

.status-yes {
  background: #d4edda;
  color: #155724;
}

.status-no {
  background: #f8d7da;
  color: #721c24;
}

button {
  margin-right: 6px;
  padding: 6px 10px;
  cursor: pointer;
  border: none;
  border-radius: 6px;
  font-weight: 600;
}

.delete-btn {
  background: #dc3545;
  color: #fff;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999; /* High z-index to cover side nav and other elements */
}

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  position: relative;
  z-index: 10000; /* Even higher for the content */
}

.modal-content h3 {
  margin-top: 0;
  margin-bottom: 20px;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #555;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.time-unavailable {
  display: flex;
  gap: 10px;
}

.time-unavailable select,
.time-unavailable input {
  flex: 1;
}

.curriculum-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.curriculum-group select {
  flex: 1;
}

.upload-icon {
  cursor: pointer;
  font-size: 20px;
  padding: 10px;
  background: #f0f0f0;
  border-radius: 6px;
  border: 1px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
}

.upload-icon:hover {
  background: #e0e0e0;
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.modal-buttons button {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.modal-buttons button:first-child {
  background: #6c757d;
  color: white;
}

.modal-buttons button:last-child {
  background: #28a745;
  color: white;
}

.modal-buttons button:hover {
  opacity: 0.9;
}

/* Prevent background scroll when modal is open */
body.modal-open {
  overflow: hidden;
}
</style>