<template>
  <div class="manage-page">
    <div class="top-controls">
      <div class="switcher">
        <button :class="{ active: activeTable === 'faculty' }" @click="activeTable = 'faculty'">Faculty</button>
        <button :class="{ active: activeTable === 'room' }" @click="activeTable = 'room'">Room</button>
        <button :class="{ active: activeTable === 'course' }" @click="activeTable = 'course'">Course/Section</button>
      </div>
      <button class="add-btn" @click="addEntry">+ Add</button>
    </div>

    <!-- Tables -->
    <FacultyTable
      v-if="activeTable === 'faculty'"
      :faculty-list="facultyList"
      @edit="openEditFacultyModal"
      @delete="removeEntry"
    />
    <RoomTable
      v-if="activeTable === 'room'"
      :room-list="roomList"
      @edit="openEditRoomModal"
      @delete="removeEntry"
    />
    <CourseTable
      v-if="activeTable === 'course'"
      :course-list="courseList"
      :curriculum-list="curriculumList"
      @edit="openEditCourseModal"
      @delete="removeCourse"
    />

    <!-- Modals -->
    <FacultyModal
      v-model:show="showFacultyModal"
      v-model:form="facultyForm"
      @submit="faculty => updateLists('faculty', faculty)"
    />
    <RoomModal
      v-model:show="showRoomModal"
      v-model:form="roomForm"
      @submit="room => updateLists('room', room)"
    />
    <CourseModal
      v-model:showCourseModal="showCourseModal"
      v-model:courseForm="courseForm"
      :curriculum-list="curriculumList"
      @submit="updateLists('course', $event)"
      @upload="handleCurriculumUpload"
    />

  </div>
</template>

<script>
import FacultyTable from './manage/FacultyTable.vue';
import RoomTable from './manage/RoomTable.vue';
import CourseTable from './manage/CourseTable.vue';
import FacultyModal from './manage/FacultyModal.vue';
import RoomModal from './manage/RoomModal.vue';
import CourseModal from './manage/CourseModal.vue';
import axios from "axios";

export default {
  name: "Manage",
  components: { FacultyTable, RoomTable, CourseTable, FacultyModal, RoomModal, CourseModal },
  data() {
    return {
      activeTable: "faculty",
      facultyList: [],
      roomList: [],
      courseList: [],
      curriculumList: [],
      subjectList: [],
      showFacultyModal: false,
      showRoomModal: false,
      showCourseModal: false,
      facultyForm: {},
      roomForm: {},
      courseForm: {},
    };
  },
  mounted() {
    this.loadAllData();
    this.loadCurriculums();
  },
  methods: {
    addEntry() {
      if (this.activeTable === "faculty") this.showFacultyModal = true;
      else if (this.activeTable === "room") this.showRoomModal = true;
      else if (this.activeTable === "course") this.showCourseModal = true;
    },
    async loadAllData() {
      try {
        const [facRes, roomRes, courseRes] = await Promise.all([
          axios.get("/api/professors"),
          axios.get("/api/rooms"),
          axios.get("/api/courses"),
        ]);
        this.facultyList = facRes.data.data || facRes.data;
        this.roomList = roomRes.data.data || roomRes.data;
        this.courseList = courseRes.data.data || courseRes.data;
      } catch (err) { console.error("Failed to load data:", err); }
    },
    async loadCurriculums() {
      try {
        const res = await axios.get("/api/curriculums");
        this.curriculumList = res.data;
      } catch (err) { console.error("Failed to load curricula:", err); }
    },
    async handleCurriculumUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("file", file);

      try {
        const res = await axios.post("/api/curriculums", formData, { headers: { "Content-Type": "multipart/form-data" } });
        alert(res.data.message);
        await this.loadCurriculums();
        this.courseForm.curriculum_id = res.data.curriculum.id;
      } catch (err) { console.error("Upload failed:", err.response?.data || err); }
    },
    async removeEntry(item) {
      if (!confirm("Are you sure?")) return;

      let url = "";
      if (this.activeTable === "faculty") url = `/api/professors/${item.id}`;
      else if (this.activeTable === "room") url = `/api/rooms/${item.id}`;
      else if (this.activeTable === "course") url = `/api/courses/${item.id}`;

      try {
        await axios.delete(url);
        const list = this.activeTable === "faculty" ? this.facultyList : this.activeTable === "room" ? this.roomList : this.courseList;
        const idx = list.findIndex(e => e.id === item.id);
        if (idx > -1) list.splice(idx, 1);
      } catch (err) { console.error("Delete failed:", err); }
    },
    updateLists(type, item) {
      if (type === "faculty") {
        const idx = this.facultyList.findIndex(f => f.id === item.id);
        if (idx > -1) this.facultyList.splice(idx, 1, item);
        else this.facultyList.push(item);
      } else if (type === "room") {
        const idx = this.roomList.findIndex(r => r.id === item.id);
        if (idx > -1) this.roomList.splice(idx, 1, item);
        else this.roomList.push(item);
      } else if (type === "course") {
        const idx = this.courseList.findIndex(c => c.id === item.id);
        if (idx > -1) this.courseList.splice(idx, 1, item);
        else this.courseList.push(item);
      }
    },
    openEditFacultyModal(faculty) { this.facultyForm = { ...faculty }; this.showFacultyModal = true; },
    openEditRoomModal(room) { this.roomForm = { ...room }; this.showRoomModal = true; },
    openEditCourseModal(course) { this.courseForm = { ...course }; this.showCourseModal = true; },
  }
};
</script>
