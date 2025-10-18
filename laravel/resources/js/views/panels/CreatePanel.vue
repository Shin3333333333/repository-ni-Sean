<template>
  <div class="create-panel">
    <!-- Header -->
    <div class="header grid">
      <div class="col-4 left-controls">
        <button @click="generateSchedule" class="generate-btn" :disabled="loading">Generate</button>

        <input
          type="text"
          v-model="academicYear"
          placeholder="Academic Year (e.g., 2025-2026)"
          class="filter-select"
          :disabled="scheduleGenerated || loading"
          :class="{ 'disabled-field': scheduleGenerated }"
        />

        <select
          v-model="semester"
          class="filter-select"
          :disabled="scheduleGenerated || loading"
          :class="{ 'disabled-field': scheduleGenerated }"
        >
          <option value="1st Semester">1st Semester</option>
          <option value="2nd Semester">2nd Semester</option>
        </select>

        <!-- Faculty Filter -->
        <select
          v-if="uniqueFaculties.length > 0"
          v-model="selectedFaculty"
          class="filter-select"
        >
          <option value="All">All Faculties</option>
          <option v-for="faculty in uniqueFaculties" :key="faculty" :value="faculty">
            {{ faculty }}
          </option>
        </select>

        <!-- Course Filter -->
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

        <!-- Sidebar toggle -->
        <button @click="sidebarOpen = !sidebarOpen" class="info-btn">
          {{ sidebarOpen ? 'Hide Details' : 'Show Details' }}
        </button>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="message" :class="['toast', messageType]">{{ message }}</div>

    <div class="content-with-sidebar">
      <!-- Schedule Table -->
      <div class="schedules-area">
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
                  <th v-if="showFacultyColumn">Faculty</th>
                  <th v-if="editMode">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(entry, index) in entries"
                  :key="entry._localId || entry.subject + '-' + index"
                  :id="`row-${entry.subject_id || entry._localId}`"
                >
                  <td v-if="editMode"><input v-model="entry.subject" /></td>
                  <td v-else>{{ entry.subject || '—' }}</td>

                  <td v-if="editMode"><input v-model="entry.time" /></td>
                  <td v-else>{{ entry.time || '—' }}</td>

                  <td v-if="editMode"><input v-model="entry.classroom" /></td>
                  <td v-else>{{ entry.classroom || '—' }}</td>

                  <td v-if="editMode"><input v-model="entry.courseCode" /></td>
                  <td v-else>{{ entry.courseCode || '—' }}</td>

                  <td v-if="editMode"><input v-model.number="entry.units" type="number" min="0" /></td>
                  <td v-else>{{ entry.units ?? 0 }}</td>

                  <td v-if="showFacultyColumn">{{ entry.faculty || groupKey }}</td>

                  <td v-if="editMode">
                    <button @click="removeEntry(groupKey, index)">Remove</button>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="total-units">
              Total Load Units: {{ totalUnitsFiltered[groupKey] || 0 }}
            </div>

            <button v-if="editMode" @click="addEntry(groupKey)" class="add-btn small">
              + Add Row
            </button>
          </div>
        </div>

        <div v-else class="no-schedule">
          <p>No schedule yet. Click "Generate" to create one.</p>
        </div>
      </div>

      <!-- Sidebar -->
      <aside :class="['sidebar', { open: sidebarOpen }]">
        <div class="sidebar-header">
          <h4>Schedule Details</h4>
          <button class="collapse" @click="sidebarOpen = false">×</button>
        </div>

        <!-- ✅ Simplified Summary -->
        <div class="sidebar-section">
          <h5>Summary</h5>
          <div v-if="summary || assignedCount || unassigned.length">
            <p><strong>Total Subjects:</strong> {{ summary?.total_curriculum_subjects ?? (assignedCount + unassigned.length) }}</p>
            <p><strong>Assigned Subjects:</strong> {{ summary?.total_assigned ?? assignedCount }}</p>
            <p><strong>Unassigned Subjects:</strong> {{ summary?.total_unassigned ?? unassigned.length }}</p>
          </div>
          <div v-else><p>No summary available.</p></div>
        </div>

        <!-- Keep Unassigned Section -->
        <div class="sidebar-section">
          <h5>Unassigned Subjects</h5>
          <div v-if="unassigned && unassigned.length">
            <div v-for="(u, i) in unassigned" :key="u.subject_id || i" class="unassigned-item">
              <div class="ua-top">
                <div class="ua-title">{{ u.subject_title || u.subject || 'Untitled' }}</div>
                <div class="ua-meta">Course: {{ (u.course_id ?? u.course) || '—' }} — Units: {{ u.units ?? u.unit ?? 0 }}</div>
              </div>

              <div class="ua-actions">
                <label>Assign to:</label>
                <select v-model="u._selectedFaculty">
                  <option value="">Choose faculty</option>
                  <option v-for="f in facultyList" :key="f.id" :value="f.id">{{ f.name }}</option>
                </select>

                <label>Choose room (optional):</label>
                <select v-model="u._selectedRoom">
                  <option value="">Auto</option>
                  <option v-for="r in roomList" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>

                <div class="ua-buttons">
                  <button @click="forceAssign(u)" class="force-assign">Force Assign</button>
                  <button @click="suggestAddResource(u)" class="add-resource">Add Faculty/Room</button>
                </div>
              </div>
            </div>
          </div>
          <div v-else><p>All subjects assigned.</p></div>
        </div>

        <!-- 🆕 Conflicts Overview -->
        <div class="sidebar-section">
          <h5>Conflicts Overview</h5>
          <div v-if="conflicts && conflicts.length">
            <ul class="conflict-list">
              <li v-for="(c, i) in conflicts" :key="i" @click="scrollToConflict(c)" class="conflict-item">
                ⚠️ {{ c.description || c.message || `Conflict between ${c.subject_a || 'Unknown'} and ${c.subject_b || ''}` }}
              </li>
            </ul>
          </div>
          <div v-else><p>No conflicts detected.</p></div>
        </div>

        <div class="sidebar-footer">
          <button @click="refreshFacultiesAndRooms">Refresh Faculty / Rooms</button>
        </div>
      </aside>
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
      sidebarOpen: false,
      unassigned: [],
      summary: null,
      facultyList: [],
      roomList: [],
      conflicts: [], // 🆕 added
      loading: false,
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
    uniqueFaculties() {
      const set = new Set();
      Object.values(this.groupedSchedules).forEach((entries) =>
        entries.forEach(
          (e) => (e.faculty || e.faculty_name) && set.add(e.faculty || e.faculty_name)
        )
      );
      if (set.size === 0)
        Object.keys(this.groupedSchedules).forEach((k) => set.add(k));
      return Array.from(set).sort();
    },
    filteredSchedules() {
      let schedules = this.groupedSchedules;

      if (this.selectedFaculty !== "All") {
        schedules = {
          [this.selectedFaculty]: schedules[this.selectedFaculty] || [],
        };
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

    // 🧩 Updated logic — only show Faculty column when filtering by course
    showFacultyColumn() {
      return this.selectedCourse !== "All";
    },

    assignedList() {
      const list = [];
      Object.values(this.groupedSchedules).forEach((entries) => {
        entries.forEach((e) => {
          list.push({
            subject_title: e.subject || e.subject_title,
            faculty_name: e.faculty || e.faculty_name || "Unknown",
            subject_id: e.subject_id || null,
          });
        });
      });
      return list;
    },
    assignedCount() {
      return this.assignedList.length;
    },
  },
  methods: {
    showMessage(text, type = "info") {
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
      if (!this.groupedSchedules[groupKey])
        this.$set(this.groupedSchedules, groupKey, []);
      this.groupedSchedules[groupKey].push({
        subject: "",
        time: "",
        classroom: "",
        courseCode: "",
        units: 0,
        faculty: groupKey,
        _localId: Date.now() + Math.random(),
      });
    },
    removeEntry(groupKey, index) {
      this.groupedSchedules[groupKey].splice(index, 1);
    },

    // 🧩 UPDATED: send semester_id instead of semester text
    async generateSchedule() {
      if (!this.academicYear) {
        this.showError("Please provide Academic Year before generating.");
        return;
      }

      const semesterMap = {
        "1st Semester": 1,
        "2nd Semester": 2,
      };
      const semester_id = semesterMap[this.semester] || 1;

      this.loading = true;
      this.show();
      try {
        const res = await fetch("/api/generate-schedule", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            academicYear: this.academicYear,
            semester_id: semester_id,
          }),
        });

        const result = await res.json();

        if (!result || !result.success) {
          this.showError(result?.message || "Failed to generate schedule.");
          return;
        }

        const grouped = {};
        const sched = Array.isArray(result.schedule) ? result.schedule : [];

        sched.forEach((s, idx) => {
          const faculty =
            (s.faculty || s.faculty_name || "Unassigned")?.toString().trim() ||
            "Unassigned";
          const subject =
            s.subject || s.subject_title || s.subject_name || "";
          const time =
            s.time ||
            s.time_slot ||
            (s.day && s.start && s.end ? `${s.day} ${s.start}-${s.end}` : "");
          const classroom = s.room || s.classroom || s.room_name || "";
          const courseCode =
            s.course_section ||
            s.course_section_name ||
            s.course_section_code ||
            s.course_code ||
            s.course_name ||
            s.section_code ||
            s.section ||
            s.course ||
            "";
          const units = Number(s.units ?? s.unit ?? 0);

          if (!grouped[faculty]) grouped[faculty] = [];

          grouped[faculty].push({
            subject,
            time,
            classroom,
            courseCode,
            units,
            faculty,
            subject_id: s.subject_id ?? s.id ?? null,
            _localId: `${Date.now()}-${idx}-${Math.random()}`,
          });
        });

        this.unassigned = Array.isArray(result.unassigned)
          ? result.unassigned.map((u) => ({
              ...u,
              _selectedFaculty: "",
              _selectedRoom: "",
            }))
          : [];

        this.summary = result.summary || null;
        this.conflicts = result.conflicts || result.conflict_list || []; // 🆕

        this.groupedSchedules = Object.keys(grouped).length ? grouped : {};
        this.sidebarOpen = true;

        await this.refreshFacultiesAndRooms();

        this.showSuccess(result.message || "Schedule generated successfully!");
      } catch (err) {
        console.error(err);
        this.showError("Could not generate schedule.");
      } finally {
        this.hide();
        this.loading = false;
      }
    },

    scrollToConflict(conflict) {
      const targetId =
        conflict.subject_a_id ||
        conflict.subject_a ||
        conflict.subject_b_id ||
        conflict.subject_b;
      if (!targetId) return;
      const el = document.querySelector(`#row-${targetId}`);
      if (el) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
        el.style.backgroundColor = "#ffcccc";
        setTimeout(() => (el.style.backgroundColor = ""), 2000);
      }
    },

    async refreshFacultiesAndRooms() {
      try {
        const [fRes, rRes] = await Promise.all([
          fetch("/api/faculties").then((r) => r.json()).catch(() => ({ items: [] })),
          fetch("/api/rooms").then((r) => r.json()).catch(() => ({ items: [] })),
        ]);

        this.facultyList = Array.isArray(fRes)
          ? fRes
          : fRes.items || fRes.faculties || [];
        this.roomList = Array.isArray(rRes)
          ? rRes
          : rRes.items || rRes.rooms || [];
      } catch (err) {
        console.warn("Could not refresh faculties/rooms:", err);
      }
    },
  },
};
</script>


<style scoped>
.create-panel {
  padding: 18px;
  box-sizing: border-box;
  background: #f9fafb;
  min-height: 100vh;
}

/* Header Area */
.header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
  align-items: center;
  background: #ffffff;
  padding: 12px 18px;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.06);
}
.left-controls, .right-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}

/* Restored button colors */
.generate-btn {
  background-color: #f39c12;
}
.edit-btn {
  background-color: #f39c12;
}
.save-btn {
  background-color: #2ecc71;
}
.exit-btn {
  background-color: #e74c3c;
}
.info-btn {
  background-color: #6c5ce7;
}

.generate-btn,
.edit-btn,
.save-btn,
.exit-btn,
.info-btn {
  padding: 8px 14px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  color: #fff;
  font-weight: 600;
  transition: all 0.2s ease;
}
.generate-btn:hover,
.edit-btn:hover,
.save-btn:hover,
.exit-btn:hover,
.info-btn:hover {
  filter: brightness(1.1);
}
.generate-btn[disabled] {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Filters */
.filter-select {
  padding: 8px 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  background: #fff;
}
.disabled-field {
  background: #f3f4f6;
}

/* Toast messages */
.toast {
  margin: 10px 0;
  padding: 10px 12px;
  border-radius: 8px;
  font-weight: 500;
}
.toast.success {
  background: #eafaf1;
  color: #1e824c;
}
.toast.error {
  background: #fdecea;
  color: #b71c1c;
}

/* Layout */
.content-with-sidebar {
  display: flex;
  gap: 0;
  position: relative;
  overflow-x: hidden;
}
.schedules-area {
  flex: 1;
  transition: margin-right 0.3s ease;
}
.schedules-area.drawer-open {
  margin-right: 360px; /* keep layout intact for small screens */
}

/* Drawer Sidebar (acts like modal) */
.sidebar {
  width: 360px;
  background: #ffffff;
  border-left: 1px solid #e5e7eb;
  box-shadow: -2px 0 6px rgba(0, 0, 0, 0.08);
  position: fixed;
  top: 0;
  right: -360px;
  height: 100vh;
  overflow-y: auto;
  transition: right 0.3s ease-in-out;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  padding: 18px;
}
.sidebar.open {
  right: 0;
}
.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.sidebar-header h4 {
  font-size: 1.2rem;
  font-weight: 700;
  color: #333;
}
.sidebar-header .collapse {
  border: none;
  background: transparent;
  font-size: 1.5rem;
  cursor: pointer;
  color: #888;
}
.sidebar-header .collapse:hover {
  color: #111;
}

/* Drawer Sections */
.sidebar-section {
  background: #fafafa;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 14px;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
}
.sidebar-section h5 {
  font-size: 1rem;
  margin-bottom: 6px;
  color: #444;
}

/* Unassigned Cards */
.unassigned-item {
  border: 1px solid #e5e7eb;
  background: #fff;
  padding: 10px 12px;
  border-radius: 10px;
  margin-bottom: 10px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.ua-top {
  display: flex;
  flex-direction: column;
  margin-bottom: 6px;
}
.ua-title {
  font-weight: 600;
  color: #222;
}
.ua-meta {
  font-size: 0.9rem;
  color: #555;
}
.ua-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.ua-actions label {
  font-size: 0.85rem;
  color: #666;
}
.ua-buttons {
  display: flex;
  gap: 8px;
  margin-top: 6px;
}
.force-assign {
  background: #16a085;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}
.add-resource {
  background: #e67e22;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}
.force-assign:hover,
.add-resource:hover {
  filter: brightness(1.1);
}

/* Table */
.create-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}
.create-table th {
  background: #f1f5f9;
  color: #333;
  font-weight: 600;
}
.create-table th,
.create-table td {
  border: 1px solid #e2e8f0;
  padding: 8px 10px;
  text-align: left;
}
.faculty-section {
  margin-bottom: 20px;
  background: #ffffff;
  border-radius: 10px;
  padding: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.total-units {
  margin-top: 8px;
  font-weight: 600;
  color: #2c3e50;
}
.add-btn {
  background: #dfac07;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  margin-top: 8px;
}
.add-btn:hover {
  filter: brightness(1.1);
}

/* Responsive Drawer */
@media (max-width: 980px) {
  .sidebar {
    width: 100%;
    right: -100%;
  }
  .sidebar.open {
    right: 0;
  }
}
</style>

