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

 <!-- Faculty Table -->
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
    <tr v-for="faculty in facultyList" :key="faculty.id">
      <td>{{ faculty.name }}</td>
      <td>{{ faculty.type }}</td>
      <td>{{ faculty.department }}</td>
      <td>{{ faculty.max_load }}</td>
      <td>{{ faculty.time_unavailable }}</td>
      <td>
        <span :class="faculty.status === 'Active' ? 'status-yes' : 'status-no'">
          {{ faculty.status }}
        </span>
      </td>
      <td>
        <button @click="openEditFacultyModal(faculty)">Edit</button>
        <button class="delete-btn" @click="removeEntry(faculty)">Delete</button>
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
  <!-- Room Table -->
    <tbody>
      <tr v-for="(room, index) in roomList" :key="room.id">
  <td>{{ room.name }}</td>
  <td>{{ room.capacity }}</td>
  <td>{{ room.type }}</td>
  <td>
    <span class="status" :class="room.status === 'Available' ? 'status-yes' : 'status-no'">
      {{ room.status }}
    </span>
  </td>
 <td>
  <button @click="openEditRoomModal(room)">Edit</button>
  <button class="delete-btn" @click="removeEntry(room)">Delete</button>
</td>

</tr>

    </tbody>
</table>
<!-- Course Table -->
<table v-if="activeTable === 'course'" class="styled-table">
  <thead>
    <tr>
      <th>Course/Section</th>
      <th>Year Level</th>
      <th>Students</th>
      <th>Curriculum</th>
      <th>Actions</th>
    </tr>
  </thead>

  <tbody>
    <tr v-for="course in courseList" :key="course.id">
      <td>{{ course.name }}</td>
      <td>{{ course.year }}</td>
      <td>{{ course.students }}</td>
      <td>{{ course.curriculum ? course.curriculum.name : '-' }}</td>

      <td>
        <button @click="openEditCourseModal(course)">Edit</button>
        <button class="delete-btn" @click="removeCourse(course.id)">Delete</button>
      </td>
    </tr>
  </tbody>
</table>


<!-- Faculty Add Modal -->
<div v-if="showFacultyModal" class="modal-overlay" @click="handleModalClick">
  <div class="modal-content" @click.stop>
    <h3>Add New Faculty</h3>
    <form @submit.prevent="addFaculty" class="grid-row gap-4">
      <div class="form-group col-6">
        <label>Faculty Name:</label>
        <input v-model="facultyForm.name" type="text" required />
      </div>
      <div class="form-group col-6">
        <label>Type:</label>
        <select v-model="facultyForm.type" required>
          <option value="">Select Type</option>
          <option value="Full-time">Full-time</option>
          <option value="Part-time">Part-time</option>
        </select>
      </div>
      <div class="form-group col-6">
        <label>Department:</label>
        <input v-model="facultyForm.department" type="text" required />
      </div>
      <div class="form-group col-6">
        <label>Max Load:</label>
        <input v-model.number="facultyForm.maxLoad" type="number" min="1" required />
      </div>
      <div class="form-group col-6">
        <label>Status:</label>
        <select v-model="facultyForm.status" required>
          <option value="">Select Status</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>
      <div class="form-group col-6">
        <label>Time Unavailable:</label>
        <div class="grid-row gap-2">
          <select class="col-6" v-model="facultyForm.day">
            <option value="">Select Day</option>
            <option value="M">Monday</option>
            <option value="T">Tuesday</option>
            <option value="W">Wednesday</option>
            <option value="Th">Thursday</option>
            <option value="F">Friday</option>
            <option value="Sat">Saturday</option>
          </select>
          <input class="col-6" v-model="facultyForm.time" type="text" placeholder="e.g., 1-3PM" />
        </div>
      </div>

      <div class="modal-buttons col-12 flex justify-end gap-2">
        <button type="button" @click="closeFacultyModal">Cancel</button>
        <button type="submit">Add</button>
      </div>
    </form>
  </div>
</div>

<!-- Room Add Modal -->
<div v-if="showRoomModal" class="modal-overlay" @click="closeRoomModal">
  <div class="modal-content" @click.stop>
    <h3>Add New Room</h3>
    <form @submit.prevent="addRoom" class="grid-row gap-4">
      <div class="form-group col-6">
        <label>Room Name:</label>
        <input v-model="roomForm.name" type="text" required />
      </div>
      <div class="form-group col-6">
        <label>Capacity:</label>
        <input v-model.number="roomForm.capacity" type="number" min="1" required />
      </div>
      <div class="form-group col-6">
        <label>Type:</label>
        <input v-model="roomForm.type" type="text" required />
      </div>
      <div class="form-group col-6">
        <label>Status:</label>
        <select v-model="roomForm.status" required>
          <option value="Available">Available</option>
          <option value="Unavailable">Unavailable</option>
        </select>
      </div>
      <div class="modal-buttons col-12 flex justify-end gap-2">
        <button type="button" @click="closeRoomModal">Cancel</button>
        <button type="submit">Add</button>
      </div>
    </form>
  </div>
</div>
<!-- Course/Section Add Modal -->
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
              {{ curr.name }}
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
        <button type="button" @click="closeCourseModal">Cancel</button>
        <button type="submit">Add</button>
      </div>
    </form>
  </div>
</div>


<!-- Edit Course Modal -->
<div v-if="showEditCourseModal" class="modal-overlay" @click="closeCourseModal">
  <div class="modal-content" @click.stop>
    <h3>Edit Course/Section</h3>
    <form @submit.prevent="updateCourse">
      <div class="grid-row">
        <div class="form-group col-6">
          <label>Course Name:</label>
          <input v-model="courseForm.courseName" type="text" required />
        </div>
  
        <div class="form-group col-6">
          <label>Year Level:</label>
          <select v-model="courseForm.yearLevel" required>
            <option value="">Select Year</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>
      </div>

      <div class="grid-row">
        <div class="form-group col-6">
          <label>Students:</label>
          <input v-model="courseForm.students" type="text" />
        </div>
        <div class="form-group col-6">
          <label>Curriculum:</label>
          <div class="curriculum-group">
            <select v-model="courseForm.curriculum_id" required @change="loadSubjectsForCurriculum">
              <option value="">Select Curriculum</option>
              <option v-for="curriculum in curriculumList" :key="curriculum.id" :value="curriculum.id">
                {{ curriculum.filename }}
              </option>
            </select>
            <label for="curriculum-upload" class="upload-icon">📁</label>
            <input id="curriculum-upload" type="file" @change="handleCurriculumUpload" accept=".xlsx" style="display: none;" />
          </div>
        </div>
      </div>

      <div class="modal-buttons">
        <button type="button" @click="closeCourseModal">Cancel</button>
        <button type="submit">Update</button>
      </div>
    </form>

    <!-- Subjects loaded from the course -->
    <div v-if="subjectList.length">
      <h4>Subjects</h4>
      <table class="styled-table">
        <thead>
          <tr>
            <th>Subject Code</th>
            <th>Title</th>
            <th>Units</th>
            <th>Semester</th>
            <th>Year Level</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(subject, idx) in subjectList" :key="idx">
            <td>{{ subject.code }}</td>
            <td>{{ subject.name }}</td>
            <td>{{ subject.units }}</td>
            <td>{{ subject.semester }}</td>
            <td>{{ subject.year_level }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>



<!-- Room Edit Modal -->
<div v-if="showEditRoomModal" class="modal-overlay" @click="closeRoomModal">
  <div class="modal-content" @click.stop>
    <h3>Edit Room</h3>
    <form @submit.prevent="updateRoom" class="grid-row gap-4">
      <div class="form-group col-6">
        <label>Room Name:</label>
        <input v-model="editRoomForm.name" type="text" required />
      </div>
      <div class="form-group col-6">
        <label>Capacity:</label>
        <input v-model.number="editRoomForm.capacity" type="number" min="1" required />
      </div>
      <div class="form-group col-6">
        <label>Type:</label>
        <input v-model="editRoomForm.type" type="text" required />
      </div>
      <div class="form-group col-6">
        <label>Status:</label>
        <select v-model="editRoomForm.status" required>
          <option value="Available">Available</option>
          <option value="Unavailable">Unavailable</option>
        </select>
      </div>
      <div class="modal-buttons col-12 flex justify-end gap-2">
        <button type="button" @click="closeRoomModal">Cancel</button>
        <button type="submit">Save Changes</button>
      </div>
    </form>
  </div>
</div>
</div>

</template>
<script>
import axios from "axios";

export default {
  name: "Manage",
  data() {
    return {
      activeTable: "faculty",

      // --- Modal Flags ---
      showFacultyModal: false,
      showCourseModal: false,
      showSubjectModal: false,
      showEditFacultyModal: false,
      showEditCourseModal: false,
      showEditRoomModal: false,
      showRoomModal: false,

      // --- Tracking ---
      editFacultyId: null,
      editCourseId: null,
      editRoomId: null,

      // --- Forms ---
      facultyForm: {
        name: "",
        type: "",
        department: "",
        maxLoad: 1,
        status: "",
        day: "",
        time: "",
      },
      courseForm: {
        name: "",
        year: "",
        students: 0,
        curriculum_id: null,
      },
      roomForm: {
        name: "",
        capacity: 1,
        type: "",
        status: "Available",
      },
      editRoomForm: {},

      // --- Data Lists ---
      facultyList: [],
      roomList: [],
      courseList: [],
      subjectList: [],
      curriculumList: [],
      selectedCourse: null,
    };
  },

  mounted() {
    this.loadAllData();
    this.loadCurriculums();
  },

  watch: {
    showFacultyModal(val) { document.body.classList.toggle("modal-open", val); },
    showCourseModal(val) { document.body.classList.toggle("modal-open", val); },
    showSubjectModal(val) { document.body.classList.toggle("modal-open", val); },
  },

  methods: {
    // --- LOAD DATA ---
    async loadAllData() {
      try {
        const [facRes, roomRes, courseRes] = await Promise.all([
          axios.get("http://localhost:8000/api/professors"),
          axios.get("http://localhost:8000/api/rooms"),
          axios.get("http://localhost:8000/api/courses"),
        ]);

        this.facultyList = Array.isArray(facRes.data) ? facRes.data : facRes.data.data;
        this.roomList = Array.isArray(roomRes.data) ? roomRes.data : roomRes.data.data;
        this.courseList = Array.isArray(courseRes.data) ? courseRes.data : courseRes.data.data;
      } catch (err) {
        console.error("Failed to load data:", err);
      }
    },

    async loadCurriculums() {
      try {
        const res = await axios.get("/api/curriculums");
        this.curriculumList = res.data;
      } catch (error) {
        console.error("Failed to load curricula:", error);
      }
    },

    async uploadCurriculum(event) {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("file", file);

      try {
        const response = await axios.post("/api/curriculums", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });

        alert(response.data.message);
        this.loadCurriculums(); // refresh dropdown list
      } catch (error) {
        console.error("Upload failed:", error.response?.data || error);
        alert("Failed to upload curriculum");
      }
    },

    // --- MODAL OPENERS ---
    addEntry() {
      if (this.activeTable === "faculty") this.showFacultyModal = true;
      else if (this.activeTable === "course") this.showCourseModal = true;
      else if (this.activeTable === "room") this.showRoomModal = true;
    },

    openEditFacultyModal(faculty) {
      this.facultyForm = {
        name: faculty.name,
        type: faculty.type,
        department: faculty.department,
        maxLoad: faculty.max_load || 1,
        status: faculty.status || "Active",
        day: faculty.time_unavailable ? faculty.time_unavailable.split(' ')[0] : "",
        time: faculty.time_unavailable ? faculty.time_unavailable.split(' ')[1] : "",
      };
      this.editFacultyId = faculty.id;
      this.showEditFacultyModal = true;
    },

async openEditCourseModal(course) {
  // Make sure curriculum list is loaded
  if (!this.curriculumList.length) await this.loadCurriculums();

  this.courseForm = {
    id: course.id,
    courseName: course.name,
   yearLevel: `${course.year} Year`, // keep as in database; match <option> values
    students: course.students,
    curriculum_id: course.curriculum ? course.curriculum.id : null,
  };

  this.editCourseId = course.id;
  this.showEditCourseModal = true;

  // Load subjects from curriculum file if curriculum_id exists
  if (this.courseForm.curriculum_id) {
    try {
      const res = await axios.get(`/api/curriculums/${this.courseForm.curriculum_id}/subjects`);
      this.subjectList = res.data; // array of subjects from XLSX
    } catch (err) {
      console.error("Failed to load subjects from curriculum:", err);
      this.subjectList = [];
    }
  } else {
    this.subjectList = [];
  }
}
,

    openEditRoomModal(room) {
      this.editRoomForm = { ...room };
      this.editRoomId = room.id;
      this.showEditRoomModal = true;
    },

    // --- ADD METHODS ---
    addFaculty() {
      const payload = {
        name: this.facultyForm.name,
        type: this.facultyForm.type,
        department: this.facultyForm.department,
        max_load: this.facultyForm.maxLoad,
        status: this.facultyForm.status,
        time_unavailable: `${this.facultyForm.day} ${this.facultyForm.time}`.trim(),
      };

      axios.post("/api/professors", payload)
        .then(res => {
          this.facultyList.push(res.data);
          this.closeFacultyModal();
        })
        .catch(err => {
          console.error("Failed to add faculty:", err.response?.data || err);
          alert("Failed to add faculty");
        });
    },

    async addCourse() {
      try {
        const payload = {
          name: this.courseForm.name,
          year: this.courseForm.year,
          students: this.courseForm.students,
          curriculum_id: this.courseForm.curriculum_id || null,
        };

        const res = await axios.post("/api/courses", payload);
        alert(res.data.message);
        this.loadAllData();
        this.resetCourseForm();
        this.closeCourseModal();
      } catch (error) {
        console.error("Failed to add course:", error.response?.data || error);
      }
    },

    addRoom() {
      axios.post('/api/rooms', this.roomForm)
        .then(res => {
          if (Array.isArray(this.roomList)) this.roomList.push(res.data);
          else this.roomList = [res.data];
          this.closeRoomModal();
        })
        .catch(err => {
          console.error('Failed to add room:', err);
          alert('Failed to add room');
        });
    },

    // --- UPDATE METHODS ---
    updateFaculty() {
      const payload = {
        name: this.facultyForm.name,
        type: this.facultyForm.type,
        department: this.facultyForm.department,
        max_load: this.facultyForm.maxLoad,
        status: this.facultyForm.status,
        time_unavailable: `${this.facultyForm.day} ${this.facultyForm.time}`.trim(),
      };

      axios.put(`/api/professors/${this.editFacultyId}`, payload)
        .then(res => {
          const index = this.facultyList.findIndex(f => f.id === this.editFacultyId);
          if (index > -1) this.facultyList.splice(index, 1, res.data);
          this.closeFacultyModal();
        })
        .catch(err => {
          console.error("Failed to update faculty:", err.response?.data || err);
          alert("Failed to update faculty");
        });
    },

    async updateCourse() {
      try {
        const payload = {
          name: this.courseForm.name,
          year: this.courseForm.year,
          students: this.courseForm.students,
          curriculum_id: this.courseForm.curriculum_id || null,
        };
        const res = await axios.put(`/api/courses/${this.editCourseId}`, payload);
        const index = this.courseList.findIndex(c => c.id === this.editCourseId);
        if (index > -1) this.courseList.splice(index, 1, res.data);
        this.closeCourseModal();
      } catch (err) {
        console.error("Failed to update course:", err.response?.data || err);
      }
    },

    async updateRoom() {
      try {
        const res = await axios.put(`/api/rooms/${this.editRoomId}`, this.editRoomForm);
        const index = this.roomList.findIndex(r => r.id === this.editRoomId);
        if (index > -1) this.roomList.splice(index, 1, res.data);
        this.closeRoomModal();
      } catch (err) {
        console.error("Failed to update room:", err.response?.data || err);
        alert("Failed to update room");
      }
    },

    // --- DELETE METHOD ---
    async removeEntry(item) {
      if (!confirm("Are you sure you want to delete this entry?")) return;

      let apiUrl = "";
      if (this.activeTable === "faculty") apiUrl = `/api/professors/${item.id}`;
      else if (this.activeTable === "course") apiUrl = `/api/courses/${item.id}`;
      else if (this.activeTable === "room") apiUrl = `/api/rooms/${item.id}`;

      try {
        await axios.delete(apiUrl);
        const list =
          this.activeTable === "faculty" ? this.facultyList :
          this.activeTable === "course" ? this.courseList : this.roomList;
        const index = list.findIndex(e => e.id === item.id);
        if (index > -1) list.splice(index, 1);
      } catch (err) {
        console.error("Failed to delete entry:", err);
      }
    },

    // --- MODAL CLOSE & RESET ---
    closeFacultyModal() { this.showFacultyModal = false; this.showEditFacultyModal = false; this.resetFacultyForm(); document.body.classList.remove("modal-open"); },
    closeCourseModal() { this.showCourseModal = false; this.showEditCourseModal = false; this.resetCourseForm(); document.body.classList.remove("modal-open"); },
    closeRoomModal() { this.showRoomModal = false; this.showEditRoomModal = false; this.resetRoomForm(); document.body.classList.remove("modal-open"); },

    resetFacultyForm() { this.facultyForm = { name: "", type: "", department: "", maxLoad: 1, status: "", day: "", time: "" }; },
    resetCourseForm() { this.courseForm = { name: "", year: "", students: 0, curriculum_id: null, section: "", department: "", adviser: "", uploadedFile: null }; },
    resetRoomForm() { this.roomForm = { name: "", capacity: 1, type: "", status: "Available" }; this.editRoomForm = { name: "", capacity: 1, type: "", status: "Available" }; },
  }
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
/* 12-column grid container */
.grid-row {
  display: grid;
  grid-template-columns: repeat(12, 1fr); /* 12 equal columns */
  gap: 16px; /* space between columns */
  margin-bottom: 16px;
}

/* Assign column span dynamically */
[class*="col-"] {
  /* fallback span 12 if not specified */
  grid-column: span 12;
}

/* Optional: small helper classes for quick use */
.col-1 { grid-column: span 1; }
.col-2 { grid-column: span 2; }
.col-3 { grid-column: span 3; }
.col-4 { grid-column: span 4; }
.col-5 { grid-column: span 5; }
.col-6 { grid-column: span 6; }
.col-7 { grid-column: span 7; }
.col-8 { grid-column: span 8; }
.col-9 { grid-column: span 9; }
.col-10 { grid-column: span 10; }
.col-11 { grid-column: span 11; }
.col-12 { grid-column: span 12; }

/* Optional: responsive adjustments */
@media (max-width: 768px) {
  .grid-row {
    grid-template-columns: repeat(6, 1fr); /* 6 columns for smaller screens */
  }
  [class*="col-"] { grid-column: span 6; } /* all fields full width by default */
}

@media (max-width: 480px) {
  .grid-row {
    grid-template-columns: repeat(1, 1fr); /* stack on mobile */
  }
  [class*="col-"] { grid-column: span 1; }
}


</style>