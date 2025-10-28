<template>
  <div class="manage-page">
    <div class="top-controls">
      <div class="switcher">
        <button :class="{ active: activeTable === 'faculty' }" @click="setActiveTable('faculty')">Faculty</button>
        <button :class="{ active: activeTable === 'room' }" @click="setActiveTable('room')">Room</button>
        <button :class="{ active: activeTable === 'course' }" @click="setActiveTable('course')">Course/Section</button>
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
      @delete="removeEntry"
    />

    <!-- Modals -->
    <FacultyModal 
  v-model:show="showFacultyModal"
  :form="selectedFaculty"
  @submit="faculty => updateLists('faculty', faculty)"
/>



    <RoomModal
      :show="showRoomModal"
      :form="roomForm"
      @submit="updateLists('room', $event)"
      @update:show="showRoomModal = $event"
    />

    <CourseModal
      v-model:show="showCourseModal"
      v-model:courseForm="courseForm"
      :curriculum-list="curriculumList"
      :semester-list="semesterList"
      @submit="updateLists('course', $event)"
      @upload="handleCurriculumUpload"
    />

    <!-- Global Loading Modal -->
    <LoadingModal />
  </div>
</template>

<script>
import FacultyTable from './manage/FacultyTable.vue';
import RoomTable from './manage/RoomTable.vue';
import CourseTable from './manage/CourseTable.vue';
import FacultyModal from './manage/FacultyModal.vue';
import RoomModal from './manage/RoomModal.vue';
import CourseModal from './manage/CourseModal.vue';
import LoadingModal from '../components/LoadingModal.vue';
import axios from "axios";
import { useLoading } from '../composables/useLoading';

export default {
  name: "Manage",
  components: { FacultyTable, RoomTable, CourseTable, FacultyModal, RoomModal, CourseModal, LoadingModal },

  data() {
    return {
      activeTable: "faculty",
      facultyList: [],
      roomList: [],
      courseList: [],
      curriculumList: [],
      subjectList: [],
      semesterList: [],
      showFacultyModal: false,
      showRoomModal: false,
      showCourseModal: false,
      roomForm: {},
      courseForm: {},
      selectedFaculty: { unavailableTimes: [], maxLoad: 1 }, // ✅ initiali
    };
  },

  setup() {
    const { show, hide } = useLoading();
    return { show, hide };
  },

  mounted() {
    this.show();
    Promise.all([
      this.loadAllData(),
      this.loadCurriculums(),
      this.loadSemesters(),
    ]).finally(() => this.hide());
  },

  methods: {
    async setActiveTable(tab) {
      if (this.activeTable === tab) return;
      this.show();
      this.activeTable = tab;
      try {
        if (tab === 'faculty') {
          await this.loadAllData(); // includes professors
        } else if (tab === 'room') {
          const res = await axios.get('/api/rooms');
          this.roomList = res.data.data || res.data;
        } else if (tab === 'course') {
          const res = await axios.get('/api/courses');
          this.courseList = res.data.data || res.data;
        }
      } catch (e) {
        console.error('Failed to load data for tab', tab, e);
      } finally {
        this.hide();
      }
    },
    addEntry() {
    if (this.activeTable === "faculty") {
      // ✅ Full default object for Add Faculty
      this.selectedFaculty = {
        id: null,
        name: "",
        type: "Full-time",
        department: "",
        maxLoad: 1,
        status: "Active",
        unavailableTimes: [],
      };
      this.showFacultyModal = true;
    } else if (this.activeTable === "room") {
      this.roomForm = { name: "", capacity: 1, type: "", status: "Available" };
      this.showRoomModal = true;
    } else if (this.activeTable === "course") {
      this.courseForm = { name: "", year: "", students: 1, curriculum_id: null, subjects: [] };
      this.showCourseModal = true;
    }
  },

    async loadSemesters() {
      try {
        const res = await axios.get("/api/semesters");
        this.semesterList = res.data;
      } catch (err) {
        console.error("Failed to load semesters:", err);
      }
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
      } catch (err) {
        console.error("Failed to load data:", err);
      }
    },

    async loadCurriculums() {
      try {
        const res = await axios.get("/api/curriculums");
        this.curriculumList = res.data;
      } catch (err) {
        console.error("Failed to load curricula:", err);
      }
    },

    async handleCurriculumUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("file", file);

      try {
        this.show(); // show loading
        const res = await axios.post("/api/curriculums", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });

        alert(res.data.message);
        await this.loadCurriculums();

        if (this.showCourseModal) {
          this.courseForm.curriculum_id = res.data.curriculum.id;
        }
      } catch (err) {
        console.error("Upload failed:", err.response?.data || err);
      } finally {
        this.hide(); // hide loading
      }
    },

    async updateLists(type, item) {
      this.show(); // show loading
      try {
        let updated;

        if (type === "course") {
          if (!item.id) {
            const res = await axios.post("/api/courses", item);
            updated = res.data.course;
            this.courseList.push(updated);
          } else {
            const payload = {
              ...item,
              subjects: item.subjects?.map(s => {
                const { created_at, updated_at, ...rest } = s;
                return rest;
              }),
            };
            const res = await axios.put(`/api/courses/${item.id}`, payload);
            updated = res.data.course;
            const idx = this.courseList.findIndex(c => c.id === updated.id);
            if (idx > -1) this.courseList.splice(idx, 1, updated);
          }
          this.courseForm = {};
          this.showCourseModal = false;

        } else if (type === "faculty") {
        let res;
        // Normalize time_unavailable to a proper string, not "[object Object]"
        const timeUnavailableString = Array.isArray(item.unavailableTimes)
          ? item.unavailableTimes
              .map(u => {
                if (typeof u === 'string') return u;
                if (u && typeof u === 'object') {
                  const day = u.dayName || u.day || '';
                  const start = u.start || '';
                  const end = u.end || '';
                  if (day && start && end) return `${day} ${start}–${end}`;
                }
                return '';
              })
              .filter(Boolean)
              .join(', ')
          : (typeof item.time_unavailable === 'string' ? item.time_unavailable : '');

        const payload = {
          name: item.name,
          type: item.type,
          department: item.department,
          max_load: item.maxLoad,
          status: item.status,
          time_unavailable: timeUnavailableString,
        };

     if (!item.id) {
      // Prevent accidental double submission
      if (this.facultyList.some(f => f.name === payload.name && f.department === payload.department)) {
        console.warn("Duplicate faculty prevented");
        return;
      }

      res = await axios.post("/api/professors", payload);
    } else {
      res = await axios.put(`/api/professors/${item.id}`, payload);
    }

        const data = res.data.data || res.data;

       const prof = {
        id: data.id,
        name: data.name,
        type: data.type,
        department: data.department,
        max_load: data.max_load,          // ⚡ key must match table
        status: data.status,
        unavailableTimes: data.time_unavailable
          ? data.time_unavailable.split(",").map(t => t.trim())
          : [],
        time_unavailable: data.time_unavailable || "",
      };


      const idx = this.facultyList.findIndex(f => f.id === prof.id);

      if (idx === -1) {
        // ✅ New faculty → add to list
        this.facultyList = [...this.facultyList, prof];
      } else {
        // ✅ Existing faculty → update
        this.facultyList.splice(idx, 1, prof);
      }


        this.showFacultyModal = false


      
    }
else if (type === "room") {
  // Make sure we received a valid object
  if (!item || !item.id || !item.name) {
    console.warn("⚠️ Ignored invalid room data:", item);
    return;
  }

  // Find room in the current list
  const idx = this.roomList.findIndex(r => r.id === item.id);

  if (idx !== -1) {
    // ✅ Update existing room
    this.roomList.splice(idx, 1, item);
  } else {
    // ✅ Add new room reactively
    this.roomList = [...this.roomList, item];
  }

  // Reset form + close modal
  this.roomForm = {};
  this.showRoomModal = false;
}

      } catch (err) {
        console.error(`Failed to update ${type}:`, err.response?.data || err);
      } finally {
        this.hide(); // hide loading
      }
    },
    async openEditFacultyModal(faculty) {
      this.show(); // show global loading
      try {
        // If you need to fetch fresh data from API, do it here
        // const res = await axios.get(`/api/professors/${faculty.id}`);
        // this.selectedFaculty = res.data.data || res.data;

        this.selectedFaculty = faculty; // populate the modal
        this.showFacultyModal = true;   // open the modal
      } finally {
        this.hide(); // hide loading
      }
    },


    openEditRoomModal(room) {
  this.roomForm = JSON.parse(JSON.stringify(room)); // ✅ deep clone
  this.showRoomModal = true;
}
,

    async openEditCourseModal(course) {
      try {
        this.show(); // show loading
        const res = await axios.get(`/api/courses/${course.id}`);
        const courseData = res.data.course;

        this.courseForm = {
          ...courseData,
          subjects: courseData.subjects || []
        };

        this.showCourseModal = true;
      } catch (err) {
        console.error("Failed to load course details:", err.response?.data || err);
      } finally {
        this.hide(); // hide loading
      }
    },

    async removeEntry(item) {
      if (!item?.id) return;
      if (!confirm("Are you sure?")) return;

      this.show(); // show loading

      let url = "";
      if (this.activeTable === "faculty") url = `/api/professors/${item.id}`;
      else if (this.activeTable === "room") url = `/api/rooms/${item.id}`;
      else if (this.activeTable === "course") url = `/api/courses/${item.id}`;

      try {
        await axios.delete(url);

        const list =
          this.activeTable === "faculty"
            ? this.facultyList
            : this.activeTable === "room"
            ? this.roomList
            : this.courseList;

        const idx = list.findIndex(e => e.id === item.id);
        if (idx > -1) list.splice(idx, 1);
      } catch (err) {
        console.error("Delete failed:", err);
      } finally {
        this.hide(); // hide loading
      }
    },
  },
};
</script>
