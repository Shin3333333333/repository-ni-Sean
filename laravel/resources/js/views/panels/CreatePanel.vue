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
      <div class="schedules-area" :class="{ 'sidebar-open': sidebarOpen }">
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
                  <th>Subject Code</th>
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
                  <td v-if="editMode"><input v-model="entry.courseCode" /></td>
                  <td v-else>{{ entry.courseCode || '—' }}</td>
                  <td v-if="editMode"><input v-model="entry.subject" /></td>
                  <td v-else>{{ entry.subject || '—' }}</td>

                  <td v-if="editMode"><input v-model="entry.time" /></td>
                  <td v-else>{{ entry.time || '—' }}</td>

                  <td v-if="editMode"><input v-model="entry.classroom" /></td>
                  <td v-else>{{ entry.classroom || '—' }}</td>

                  <td v-if="editMode"><input v-model="entry.courseSection" /></td>
                  <td v-else>{{ entry.courseSection || '—' }}</td>

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

       <button @click="undoLastAssignment" class="undo-btn">Undo Last</button>
      <button @click="undoAllAssignments" class="undo-all-btn">Undo All</button>


        <!-- 🎯 Focused Unassigned Section -->
<!-- 🎯 Unassigned Subjects Section -->
<div class="sidebar-section">
  <h5>Unassigned Subjects Overview</h5>

  <div class="summary-box">
     <p><strong>Total Subjects:</strong> {{ summary?.total_curriculum_subjects ?? (assignedCount + unassigned.length) }}</p>
    <p><strong>Assigned by AI:</strong> {{ summary?.total_assigned ?? assignedCount }}</p>
    <p><strong>Unassigned:</strong> {{ summary?.total_unassigned ?? unassigned.length }}</p>
  </div>

  <div v-if="unassigned.length">
    <div
      v-for="(u, i) in unassigned"
      :key="u.subject_id || i"
      class="unassigned-item"
    >
      <div class="ua-top">
       <div class="ua-title">{{ u.course_code || u.subject_title || u.subject || 'Untitled Subject' }}</div>

        <div class="ua-meta">
          Course: {{ u.course_code || u.course || '—' }} — Units: {{ u.units ?? 0 }}
        </div>
      </div>

      <!-- 🧠 Show unassignment reason -->
       <!-- <div class="ua-reason" v-if="findUnassignedReason(u.subject_id)">
    ⚠️ {{ findUnassignedReason(u.subject_id).description }}
  </div> -->


<div class="ua-suggestions">
  <p><strong>Possible Assignments (AI Suggestions):</strong></p>
  <ul>
    <li v-for="pa in availableAssignments(u)" :key="`${u.subject_id}-${pa.faculty_id}`" class="faculty-item">
  <template v-if="facultyStateResult = facultyState(pa.faculty_id, u.units, u.department)">
    👩‍🏫 {{ pa.faculty_name || 'Unknown Faculty' }}
    ({{ getFacultyDepartment(pa.faculty_id) || 'No Dept' }},
    Load: {{ facultyStateResult.current }}/{{ facultyStateResult.max }})

    <button
      v-if="!facultyStateResult.willExceed"
      @click.stop="assignToFaculty(u, pa)"
      class="assign-btn"
    >Assign</button>

    <button
      v-else
      @click.stop="confirmOverloadAssign(u, pa)"
      class="assign-btn overload-btn"
    >⚠️ Assign</button>

    <div class="state-hint">
      <small v-if="facultyStateResult.mismatch">🚫 Dept Mismatch</small>
      <small v-else-if="facultyStateResult.willExceed">⚠️ Overload</small>
      <small v-else-if="facultyStateResult.underload">🟡 Underload</small>
      <small v-else>✅ Suitable</small>
    </div>
  </template>
</li>


    <li v-if="!(u.possible_assignments || []).length" class="no-suggestion">
      No suggested assignments from AI.
    </li>
  </ul>
</div>


    </div>
  </div>

  <div v-else>
    <p>✅ All subjects have been assigned successfully.</p>
  </div>
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
      conflicts: [],
      unassignedReasons: [],
      loading: false,
      usedSlots: {},
      usedRooms: {},
      undoStack: [],
      currentLoadByFaculty: {},
      occupiedSlotsByFaculty: {},
      occupiedSlotsByRoom: {},
    };
  },
  setup() {
    const { show, hide } = useLoading();
    return { show, hide };
  },
  watch: {
    semester(newVal, oldVal) {
      this.groupedSchedules = {};
      this.unassigned = [];
      this.summary = null;
      this.conflicts = [];
      this.unassignedReasons = [];
      this.selectedFaculty = "All";
      this.selectedCourse = "All";
    },
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
    facultyLoads() {
      const loads = {};
      Object.values(this.groupedSchedules).forEach(entries => {
        entries.forEach(e => {
          const fid = e.faculty || e.faculty_name;
          if (!loads[fid]) loads[fid] = 0;
          loads[fid] += Number(e.units || 0);
        });
      });
      return loads;
    },
  },
  methods: {
  assignToFaculty(subject, option) {
  const slotKey = `${option.faculty_id}|${option.time_slot_label}`;
  const roomKey = `${option.room_id}|${option.time_slot_label}`;

  if (this.usedSlots[slotKey] || this.usedRooms[roomKey]) {
    this.showError("This assignment conflicts with an existing schedule.");
    return;
  }

  const facultyName = option.faculty_name || "Unknown";
  if (!this.groupedSchedules[facultyName]) this.groupedSchedules[facultyName] = [];
  
  // Fallback values for courseCode and courseSection
  const courseCode = subject.subject_code || subject.subjectCode || "-";
  console.log(subject);  // Log the subject to verify if course_section is available
 
  const courseSection = subject.course_section ||  "-";

  console.log(Object.keys(subject));  // This will list all available keys in the subject object

  console.log('Course Code:', courseCode);
  console.log('Course Section:', courseSection);

  this.groupedSchedules[facultyName].push({
    subject: subject.subject_display || subject.subject_title || subject.subject,
    time: option.time_slot_label,
    classroom: option.room_name,
    courseCode: courseCode, // Use the course_code property of the subject
    courseSection: courseSection, // Use the course_section property of the subject
    units: subject.units || 0,
    faculty: facultyName,
    subject_id: subject.subject_id,
    _localId: subject._localId,
    _assignedManually: true
  });

  this.usedSlots[slotKey] = true;
  this.usedRooms[roomKey] = true;

  const f = this.facultyList.find(fac => fac.id === option.faculty_id);
  if (f) f.current_load = (f.current_load || 0) + (subject.units || 0);

  // Remove from unassigned
  this.unassigned = this.unassigned.filter(u => u._localId !== subject._localId);

  this.undoStack.push({ type: "manual_assign", facultyName, subject, option });
  this.showSuccess(`Assigned "${subject.subject_display}" to ${facultyName} (manual/forced)`);

  this.refreshAISuggestions();
}

,
     getFacultyDepartment(facultyId) {
    const faculty = this.facultyList.find(f => f.id === facultyId);
    return faculty?.department || null;
  },
     showError(msg) {
    this.message = msg;
    this.messageType = "error";
    setTimeout(() => (this.message = ""), 5000);
  },
  showSuccess(msg) {
    this.message = msg;
    this.messageType = "success";
    setTimeout(() => (this.message = ""), 5000);
  },
      facultyState(facultyId, units, department) {
    const f = this.facultyList.find(f => f.id === facultyId);
    if (!f) return { current: 0, max: 0, willExceed: false, mismatch: false, underload: false };
    
    const current = f.current_load || 0;
    const max = f.max_load || 12;
    const willExceed = current + (units || 0) > max;
    const mismatch = department && f.department && f.department !== department;
    const underload = current < max / 2;

    return { current, max, willExceed, mismatch, underload };
  },

    // ------------------------
    // AI suggestion helpers
    // ------------------------
    availableAssignments(subject) {
      if (!subject.possible_assignments_original) return [];
      return subject.possible_assignments_original.filter(opt => {
        const slotKey = `${opt.faculty_id}|${opt.time_slot_label}`;
        const roomKey = `${opt.room_id}|${opt.time_slot_label}`;
        return !this.usedSlots[slotKey] && !this.usedRooms[roomKey];
      });
    },

   refreshAISuggestions() {
  // Replace each unassigned subject with a new object to trigger reactivity
  this.unassigned = this.unassigned.map(u => {
    const available = (u.possible_assignments_original || []).filter(opt => {
      const slotKey = `${opt.faculty_id}|${opt.time_slot_label}`;
      const roomKey = `${opt.room_id}|${opt.time_slot_label}`;
      return !this.usedSlots[slotKey] && !this.usedRooms[roomKey];
    });

    return {
      ...u,
      possible_assignments: available
    };
  });
}
,
 
  async refreshFacultiesAndRooms() {
    try {
      // You might want to fetch or update the faculty and room data
      // For example, fetching fresh faculty/room data from the backend:
      const res = await fetch('/api/faculties-rooms'); // Adjust this API endpoint as needed
      const result = await res.json();
      
      if (result && result.faculties && result.rooms) {
        this.facultyList = result.faculties;
        this.roomList = result.rooms;
        this.showSuccess('Faculties and rooms refreshed!');
      } else {
        this.showError('Failed to refresh faculties and rooms.');
      }
    } catch (err) {
      console.error(err);
      this.showError('Error refreshing faculties and rooms.');
    }
  },

assignFromSuggestion(subject, option) {
  console.log('Assigning subject:', subject);

  const slotKey = `${option.faculty_id}|${option.time_slot_label}`;
  const roomKey = `${option.room_id}|${option.time_slot_label}`;

  if (this.usedSlots[slotKey] || this.usedRooms[roomKey]) {
    this.showError("This suggestion conflicts with an existing schedule.");
    return;
  }

  const facultyName = option.faculty_name || "Unknown";
  if (!this.groupedSchedules[facultyName]) this.groupedSchedules[facultyName] = [];

  // Verify the values in the subject object
  const courseCode = subject.course_code || subject.courseCode || "-"; // fallback to "-" if not available
  const courseSection = subject.course_section || "-"; // fallback to "-" if not available

  console.log('Course Code:', courseCode);
  console.log('Course Section:', courseSection);

  this.groupedSchedules[facultyName].push({
    subject: subject.subject_display || subject.subject_title || subject.subject,
    time: option.time_slot_label,
    classroom: option.room_name,
    courseCode: courseCode, // properly assigned courseCode
    courseSection: courseSection, // properly assigned courseSection
    units: subject.units || 0,
    faculty: facultyName,
    subject_id: subject.subject_id,
    _localId: subject._localId,
    _assignedManually: true
  });

  this.usedSlots[slotKey] = true;
  this.usedRooms[roomKey] = true;

  const f = this.findFacultyById(option.faculty_id);
  if (f) f.current_load = (f.current_load || 0) + (subject.units || 0);

  // Remove from unassigned
  this.unassigned = this.unassigned.filter(u => u._localId !== subject._localId);

  this.undoStack.push({ type: "manual_assign", facultyName, subject, option });
  this.showSuccess(`Assigned "${subject.subject_display}" to ${facultyName} (manual/forced)`);

  this.refreshAISuggestions();
}
,

    undoLastAssignment() {
      if (this.undoStack.length === 0) {
        this.showError("No assignments to undo.");
        return;
      }

      const last = this.undoStack.pop();
      if (last.type !== "manual_assign") return;

      const { facultyName, subject, option } = last;
      const slotKey = `${option.faculty_id}|${option.time_slot_label}`;
      const roomKey = `${option.room_id}|${option.time_slot_label}`;

      const entries = this.groupedSchedules[facultyName] || [];
      const idx = entries.findIndex((e) => e._localId === subject._localId);
      if (idx !== -1) entries.splice(idx, 1);

      delete this.usedSlots[slotKey];
      delete this.usedRooms[roomKey];

      const fobj = this.findFacultyById(option.faculty_id);
      if (fobj) fobj.current_load = Math.max(0, (fobj.current_load || 0) - (subject.units || 0));

      this.unassigned.push(subject);

      this.refreshAISuggestions();
      this.showSuccess(`Undid assignment of "${subject.subject_display}"`);
    },

    undoAllAssignments() {
      if (this.undoStack.length === 0) {
        this.showError("No assignments to undo.");
        return;
      }

      const lastStack = [...this.undoStack];
      this.undoStack = [];

      lastStack.forEach((entry) => {
        if (entry.type === "manual_assign") {
          const { facultyName, subject, option } = entry;
          const slotKey = `${option.faculty_id}|${option.time_slot_label}`;
          const roomKey = `${option.room_id}|${option.time_slot_label}`;

          const entries = this.groupedSchedules[facultyName] || [];
          const idx = entries.findIndex((e) => e._localId === subject._localId);
          if (idx !== -1) entries.splice(idx, 1);

          delete this.usedSlots[slotKey];
          delete this.usedRooms[roomKey];

          this.unassigned.push(subject);

          const fobj = this.findFacultyById(option.faculty_id);
          if (fobj) fobj.current_load = Math.max(0, (fobj.current_load || 0) - (subject.units || 0));
        }
      });

      this.refreshAISuggestions();
      this.showSuccess("All manual assignments undone.");
    },
   async generateSchedule() {
  if (!this.academicYear) {
    this.showError("Please provide Academic Year before generating.");
    return;
  }

  this.groupedSchedules = {};
  this.unassigned = [];
  this.summary = null;
  this.conflicts = [];

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
        semester: this.semester,
      }),
    });

    const result = await res.json();
    if (!result || !result.success) {
      this.showError(result?.message || "Failed to generate schedule.");
      return;
    }

    const grouped = {};
    const sched = Array.isArray(result.schedule) ? result.schedule : [];
    const curriculumSubjects = result.curriculum_subjects || [];  // Include curriculum_subjects in the API response

    sched.forEach((s, idx) => {
      const faculty = (s.faculty || s.faculty_name || "Unassigned").toString().trim();
      if (!grouped[faculty]) grouped[faculty] = [];

      const courseSection = this.getCourseSection(s.course_id, curriculumSubjects);  // Get course section from the curriculum subjects

      grouped[faculty].push({
        subject: s.subject_title || s.subject || 'Untitled',
        time: s.time_slot || "",
        classroom: s.room_name || "",
        courseCode: s.course_code || "",
        courseSection: courseSection,  // Use the fetched course section
        units: s.units || 0,
        faculty: s.faculty_name || faculty,
        subject_id: s.subject_id ?? s.id ?? null,
        _localId: `${Date.now()}-${idx}-${Math.random()}`,
      });
    });

    this.unassigned = result.unassigned.map((u, idx) => ({
      ...u,
      possible_assignments_original: u.possible_assignments || [],
      possible_assignments: u.possible_assignments || [],
      subject_display: `${u.course_code ? u.course_code + " - " : ""}${u.subject_title || u.subject || "Untitled"}`,
      _localId: `${Date.now()}-${idx}-${Math.random()}`,
    }));

    this.groupedSchedules = grouped;
    this.sidebarOpen = true;

    this.showSuccess(result.message || "Schedule generated successfully!");
  } catch (err) {
    console.error(err);
    this.showError("Could not generate schedule.");
  } finally {
    this.hide();
    this.loading = false;
  }
}

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
  overflow-x: auto; /* allow horizontal scroll if needed */
}
.schedules-area.drawer-open {
  margin-right: 400px; /* wider to match sidebar */
}

/* Drawer Sidebar (acts like modal) */
.sidebar {
  width: 400px; /* wider sidebar */
  background: #ffffff;
  border-left: 1px solid #e5e7eb;
  box-shadow: -2px 0 6px rgba(0, 0, 0, 0.08);
  position: fixed;
  top: 0;
  right: -400px; /* start hidden */
  height: 100vh;
  overflow-y: auto;
  transition: right 0.3s ease-in-out;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  padding: 20px;
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
  padding: 6px 8px; /* smaller padding for space */
  font-size: 0.9rem; /* slightly smaller font */
  text-align: left;
  word-wrap: break-word;
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
/* ===== Adjust schedules area when sidebar/modal is open ===== */
.schedules-area {
  flex: 1;
  transition: margin-right 0.3s ease, width 0.3s ease;
  overflow-x: auto; /* prevent horizontal overflow */
}

.schedules-area.sidebar-open {
  margin-right: 420px; /* match new wider sidebar */
  width: calc(100% - 420px);
}

/* ===== Table tweaks when sidebar is open ===== */
.schedules-area.sidebar-open .create-table th,
.schedules-area.sidebar-open .create-table td {
  padding: 6px 8px; /* slightly smaller cells */
  font-size: 13px;  /* smaller font to fit more */
}

/* ===== Sidebar / Schedule Details Modal ===== */
.sidebar {
  width: 420px; /* increased from 360px */
  max-width: 90%; /* responsive fallback */
  right: -420px; /* hidden initially */
  transition: right 0.3s ease-in-out, width 0.3s ease-in-out;
}

.sidebar.open {
  right: 0;
}

/* Optional: increase padding & font for readability in bigger sidebar */
.sidebar-section {
  padding: 14px;
}

.sidebar-header h4 {
  font-size: 1.3rem;
}

/* Mobile responsiveness */
@media (max-width: 980px) {
  .schedules-area.sidebar-open {
    margin-right: 0; /* take full width on mobile */
    width: 100%;
  }
  .sidebar {
    width: 100%;
    right: -100%;
  }
  .sidebar.open {
    right: 0;
  }
}
.faculty-item {
  position: relative;
  margin-bottom: 6px;
  padding: 6px 8px;
  border-radius: 4px;
  background-color: rgba(0, 0, 0, 0.05);
  cursor: pointer;
  transition: background-color 0.2s;
  overflow: hidden; /* for overlay */
}
.faculty-item:hover {
  background-color: rgba(0, 0, 0, 0.25); /* semi-transparent overlay */
  color: white; /* text becomes visible */
}

.assign-btn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 4px;
  background-color: #3498db;
  color: white;
  border: none;
  opacity: 0;
  transition: opacity 0.2s;
}

.faculty-item:hover .assign-btn {
  opacity: 1; /* fade in on hover */
}

.overload-btn {
  background-color: #e74c3c;
}.faculty-item.mismatch {
  background-color: rgba(255, 0, 0, 0.1);
}

.faculty-item.underload {
  background-color: rgba(255, 255, 0, 0.1);
}

.faculty-item .state-hint {
  font-size: 11px;
  color: #666;
  margin-top: 2px;
}

</style>
