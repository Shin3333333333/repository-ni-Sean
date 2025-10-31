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

        <select v-if="uniqueFaculties.length > 0" v-model="selectedFaculty" class="filter-select">
          <option value="All">All Faculties</option>
          <option v-for="faculty in uniqueFaculties" :key="faculty" :value="faculty">{{ faculty }}</option>
        </select>

        <select v-if="uniqueCourses.length > 0" v-model="selectedCourse" class="filter-select">
          <option value="All">All Courses</option>
          <option v-for="course in uniqueCourses" :key="course" :value="course">{{ course }}</option>
        </select>
      </div>

      <!-- Right Controls -->
      <div class="col-4 right-controls">
        <button v-if="Object.keys(groupedSchedules).length > 0" @click="toggleEditMode" class="edit-btn">
          {{ editMode ? 'Finish Editing' : 'Edit' }}
        </button>

        <button v-if="Object.keys(groupedSchedules).length > 0" @click="saveSchedule('pending')" class="save-btn">
          Save as Pending
        </button>

        <button v-if="Object.keys(groupedSchedules).length > 0" @click="saveSchedule('finalized')" class="save-btn">
          Finalize
        </button>



        <button @click="sidebarOpen = !sidebarOpen" class="info-btn">{{ sidebarOpen ? 'Hide Details' : 'Show Details' }}</button>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="message" :class="['toast', messageType]">{{ message }}</div>

    <div class="content-with-sidebar">
      <!-- Schedule Table -->
      <div class="schedules-area" :class="{ 'sidebar-open': sidebarOpen }">
        <div v-if="Object.keys(groupedSchedules).length > 0">
          <div v-for="(entries, groupKey) in filteredSchedules" :key="groupKey" class="faculty-section">
            <h3>{{ formatFacultyHeader(groupKey) }}</h3>
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

                  <td v-if="showFacultyColumn">{{ formatFacultyCell(entry, groupKey) }}</td>

                  <td v-if="editMode"><button @click="removeEntry(groupKey, index)">Remove</button></td>
                </tr>
              </tbody>
            </table>

            <div class="total-units">Total Load Units: {{ totalUnitsFiltered[groupKey] || 0 }}</div>

            <button v-if="editMode" @click="addEntry(groupKey)" class="add-btn small">+ Add Row</button>
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

        <!-- Unassigned Subjects -->
        <div class="sidebar-section">
          <h5>Unassigned Subjects Overview</h5>

          <div class="summary-box">
            <p><strong>Total Subjects:</strong> {{ summary?.total_curriculum_subjects ?? (assignedCount + unassigned.length) }}</p>
            <p><strong>Assigned by AI:</strong> {{ summary?.total_assigned ?? assignedCount }}</p>
            <p><strong>Unassigned:</strong> {{ summary?.total_unassigned ?? unassigned.length }}</p>
          </div>

          <div v-if="unassigned.length">
            <div v-for="(u, i) in unassigned" :key="u.subject_id || i" class="unassigned-item">
              <div class="ua-top">
                <div class="ua-title">{{ u.course_code || u.subject_title || u.subject || 'Untitled Subject' }}</div>
                <div class="ua-meta">Course: {{ u.course_section || '—' }} — Units: {{ u.units ?? 0 }}</div>
              </div>

              <!-- AI Suggestions -->
              <div class="ua-suggestions">
                <p><strong>Possible Assignments (AI Suggestions):</strong></p>
                <ul>
                    <li
                      v-for="pa in availableAssignments(u)"
                      :key="`${u.subject_id}-${pa.faculty_id}-${pa.time_slot_label}`"
                      class="faculty-item"
                      :class="pa.dynamic_class || ''"
                    >
                    <div class="pa-content">
                      <div class="pa-top">
                        👩‍🏫 {{ pa.faculty_name || 'Unknown Faculty' }}
                        <small>({{ pa.faculty_department || 'No Dept' }}, Load: {{ pa.faculty_current_load }}/{{ pa.faculty_max_load }})</small>
                        <div class="pa-badges">
                          <span v-if="pa.deptMatch" class="badge badge-dept" title="Department matches">Dept</span>
                          <span v-if="pa.willExceed" class="badge badge-overload" title="Assigning this will exceed faculty's max load">Overload</span>
                          <span v-if="pa.conflictsExistingSlot" class="badge badge-conflict" title="This time overlaps an existing assignment">Time conflict</span>
                          <span v-if="pa.conflictsExistingRoom" class="badge badge-conflict" title="Room is already in use at this time">Room conflict</span>
                          <span v-if="pa.conflictsCourseSection" class="badge badge-course-section" title="This conflicts with another subject in the same course section">Course conflict</span>
                        </div>
                      </div>

                      <div class="pa-bottom">
                        <small>🕒 {{ pa.time_slot_label }} | 🏫 {{ pa.room_name }}</small>
                        <div class="pa-actions">
                          <button v-if="!pa.willExceed" @click.stop="assignToFaculty(u, pa)" class="assign-btn">Assign</button>
                          <button v-else @click.stop="confirmOverloadAssign(u, pa)" class="assign-btn overload-btn">⚠️ Assign</button>
                        </div>
                      </div>
                    </div>
                  </li>

                  <li v-if="!(u.possible_assignments || []).length" class="no-suggestion">No suggested assignments from AI.</li>
                </ul>
              </div>
            </div>
          </div>

          <div v-else>
            <p>✅ All subjects have been assigned successfully.</p>
          </div>
        </div>

        <!-- Conflicts -->
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

    <ConfirmModal
      :show="showConfirmModal"
      :title="confirmModalTitle"
      :message="confirmModalMessage"
      @confirm="handleConfirm"
      @cancel="cancelConfirm"
    />

    <!-- Leave Page Prompt -->
    <div v-if="showLeavePrompt" class="leave-overlay" role="dialog" aria-modal="true">
      <div class="leave-modal">
        <button class="leave-close" @click="cancelLeave" aria-label="Close">✖</button>
        <div class="leave-header">
          <span class="leave-icon">⚠️</span>
          <h3>Unsaved changes</h3>
        </div>
        <p class="leave-text">Changes will not be saved if you leave this page.</p>
        <div class="leave-actions">
          <button @click="saveAndLeave" :disabled="leavingBusy">Save as Pending</button>
          <button @click="proceedWithoutSaving" :disabled="leavingBusy">Proceed without saving</button>
          
        </div>
      </div>
    </div>
  </div>
</template>


<script>
import LoadingModal from "../../components/LoadingModal.vue";
import ConfirmModal from "../../components/ConfirmModal.vue";
import { useLoading } from "../../composables/useLoading";
import "/resources/css/create.css";

export default {
  components: { LoadingModal, ConfirmModal },
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
      showConfirmModal: false,
      confirmModalTitle: "",
      confirmModalMessage: "",
      confirmModalAction: null,
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
  suggestionLimit: 3,
  facultySuggestionLimit: 2,
      undoStack: [],
      currentLoadByFaculty: {},
      occupiedSlotsByFaculty: {},
      occupiedSlotsByRoom: {},
      apiBase: '/api',
      showLeavePrompt: false,
      leavingBusy: false,
      _pendingRouteNext: null,
      _pendingRouteTo: null,
    };
  },
  setup() {
    const { show, hide } = useLoading();
    return { show, hide };
  },
  mounted() {
    window.addEventListener('beforeunload', this.onBeforeUnload);
    // Add a global router guard to intercept in-app navigations
    if (this.$router && typeof this.$router.beforeEach === 'function') {
      this._removeNavGuard = this.$router.beforeEach((to, from, next) => {
        // Only guard when leaving this component's route
        const isLeavingThisView = from.fullPath === this.$route.fullPath;
        // Allow seamless navigation to Pending panel without prompting
        const goingToPending = (to && (
          (to.name && String(to.name).toLowerCase().includes('pending')) ||
          (to.path && String(to.path).toLowerCase().includes('pending'))
        ));
        if (isLeavingThisView && goingToPending) {
          next();
          return;
        }
        if (isLeavingThisView && this.hasUnsavedChanges()) {
          this._pendingRouteNext = next;
          this._pendingRouteTo = to;
          this.showLeavePrompt = true;
          // Do not call next yet; wait for user action
          return;
        }
        next();
      });
    }
  },
  beforeUnmount() {
    window.removeEventListener('beforeunload', this.onBeforeUnload);
    if (this._removeNavGuard && typeof this._removeNavGuard === 'function') {
      try { this._removeNavGuard(); } catch (e) { /* noop */ }
    }
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
        entries.forEach((e) => {
          if (e.courseSection && e.courseSection !== '-') {
            set.add(e.courseSection);
          }
        })
      );
      this.unassigned.forEach((u) => {
        if (u.course_section && u.course_section !== '-') {
          set.add(u.course_section);
        }
      });
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
            if (entry.courseSection === this.selectedCourse) {
              if (!filtered[entry.courseSection]) {
                filtered[entry.courseSection] = [];
              }
              filtered[entry.courseSection].push({ ...entry, faculty });
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
  beforeRouteLeave(to, from, next) {
    // Allow seamless navigation to Pending panel without prompting
    const goingToPending = (to && (
      (to.name && String(to.name).toLowerCase().includes('pending')) ||
      (to.path && String(to.path).toLowerCase().includes('pending'))
    ));
    if (goingToPending) {
      next();
      return;
    }
    if (!this.hasUnsavedChanges()) {
      next();
      return;
    }
    this._pendingRouteNext = next;
    this._pendingRouteTo = to;
    this.showLeavePrompt = true;
  },
  methods: {
    openLeavePrompt(nextCallback) {
      this._pendingRouteNext = typeof nextCallback === 'function' ? nextCallback : null;
      this.showLeavePrompt = true;
    },
    hasUnsavedChanges() {
      const hasAssigned = Object.keys(this.groupedSchedules || {}).some(k => (this.groupedSchedules[k] || []).length > 0);
      const hasUnassigned = (this.unassigned || []).length > 0;
      return hasAssigned || hasUnassigned;
    },
    onBeforeUnload(e) {
      if (this.hasUnsavedChanges()) {
        e.preventDefault();
        e.returnValue = '';
      }
    },
    async saveAndLeave() {
      try {
        this.leavingBusy = true;
        // Close prompt immediately and show loading while saving
        this.showLeavePrompt = false;
        if (typeof this.show === 'function') this.show();
        await this.saveSchedule('pending');
        // Clear local state to avoid re-triggering guard
        this.groupedSchedules = {};
        this.unassigned = [];
        const cont = this._pendingRouteNext;
        this.resetLeaveState();
        if (cont) cont();
      } catch (err) {
        console.error(err);
        this.showError('Failed to save as pending.');
        this.leavingBusy = false;
      }
    },
    proceedWithoutSaving() {
      const cont = this._pendingRouteNext;
      this.resetLeaveState();
      if (cont) cont();
    },
    cancelLeave() {
      // Simply close the prompt and do not continue navigation
      this.resetLeaveState();
    },
    resetLeaveState() {
      this.showLeavePrompt = false;
      this.leavingBusy = false;
      this._pendingRouteTo = null;
      this._pendingRouteNext = null;
    },
    // Resolve a faculty from name safely
    getFacultyByNameSafe(name) {
      if (!name) return null;
      return this.findFacultyByName(name) || null;
    },

    // Convert raw type values like "full_time, part_time" to "Full-time, Part-time"
    humanizeFacultyType(raw) {
      if (!raw) return null;
      const toTitleCaseHyphen = (str) => {
        return str
          .replace(/_/g, '-')
          .split('-')
          .map(s => s.trim())
          .filter(Boolean)
          .map(s => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase())
          .join('-');
      };
      // Support comma-separated list of types
      return String(raw)
        .split(',')
        .map(s => s.trim())
        .filter(Boolean)
        .map(toTitleCaseHyphen)
        .join(', ');
    },

    // Format display string: "Name (Type, Department)" where parts exist
    formatFacultyDisplay(name, facObj) {
      const faculty = facObj || this.getFacultyByNameSafe(name);
      const displayName = (faculty && faculty.name) || name || 'Unknown';
      const rawType = (faculty && (faculty.type || faculty.faculty_type)) ? (faculty.type || faculty.faculty_type) : null;
      const typePart = this.humanizeFacultyType(rawType);
      const deptPart = (faculty && faculty.department) ? faculty.department : null;
      if (typePart && deptPart) return `${displayName} (${typePart}, ${deptPart})`;
      if (typePart) return `${displayName} (${typePart})`;
      if (deptPart) return `${displayName} (${deptPart})`;
      return displayName;
    },

    // Header formatter for group key (faculty name)
    formatFacultyHeader(groupKey) {
      const fac = this.getFacultyByNameSafe(groupKey);
      return this.formatFacultyDisplay(groupKey, fac);
    },

    // Cell formatter for entry row
    formatFacultyCell(entry, groupKey) {
      const name = entry.faculty || entry.faculty_name || groupKey;
      // Prefer lookup by id if available
      let fac = null;
      if (entry.faculty_id) fac = this.findFacultyById(entry.faculty_id);
      if (!fac) fac = this.getFacultyByNameSafe(name);
      return this.formatFacultyDisplay(name, fac);
    },
    getFacultyColorClass(option, subject) {
  const f = this.facultyList.find(f => f.id === option.faculty_id);
  if (!f) return "bg-gray-200";

  const current = f.current_load || 0;
  const max = f.max_load || 12;
  const dept = f.department?.toUpperCase() || "";
  const subjDept = subject.course_code?.substring(0, 2)?.toUpperCase() || "";
  const sameDept = dept === subjDept;
  const overloaded = current >= max;
  const underloaded = current < max / 2;

  if (sameDept && !overloaded) return "bg-green-200"; // ✅ best
  if (sameDept && overloaded) return "bg-yellow-200"; // ⚠️ same dept but full
  if (!sameDept && underloaded) return "bg-orange-200"; // 🟡 mismatch but light
  if (!sameDept && overloaded) return "bg-red-300"; // 🔴 bad match
  return "bg-gray-100";
}
,
    facultyStateClass(facultyId, units, department, subjectCode = "") {
  const f = this.facultyList.find(f => f.id === facultyId);
  if (!f) return { current: 0, max: 0, willExceed: false, mismatch: false, underload: false };

  const current = f.current_load || 0;
  const max = f.max_load || 12;
  const willExceed = current + (units || 0) > max;

  // 🔠 Derive department code from subjectCode or courseCode
  const derivedDept = department || subjectCode.substring(0, 2).toUpperCase();

  const mismatch =
    f.department && derivedDept &&
    !f.department.toUpperCase().includes(derivedDept.toUpperCase());

  const underload = current < max / 2;

  return { current, max, willExceed, mismatch, underload };
},

  assignToFaculty(subject, option) {
  const normalized = this.normalizeSlotLabel(option.time_slot_label || "");
  const slotKey = `${option.faculty_id}|${normalized}`;
  const roomKey = `${option.room_id}|${normalized}`;

  if (this.usedSlots[slotKey] || this.usedRooms[roomKey] || this.checkSlotConflict(option.faculty_id, option.time_slot_label) || this.checkRoomConflict(option.room_id, option.time_slot_label)) {
    this.showError("This assignment conflicts with an existing schedule.");
    return;
  }

  // Check for course section conflicts (same course_section cannot have overlapping times)
  if (this.checkCourseSectionConflict(subject, option)) {
    this.showError("This assignment conflicts with another subject in the same course section.");
    return;
  }

  const facultyName = option.faculty_name || "Unknown";
  if (!this.groupedSchedules[facultyName]) this.groupedSchedules[facultyName] = [];
  
  // Fallback values for courseCode and courseSection
  const courseCode = subject.subject_code || subject.subjectCode || "-";
  console.log(subject);  // Log the subject to verify if course_section is available

  const courseSection = subject.course_section || "-";

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
    faculty_id: option.faculty_id || null,
    room_id: option.room_id || null,
    subject_id: subject.subject_id,
    _localId: subject._localId,
    _assignedManually: true
  });

  // mark normalized keys
  this.usedSlots[`${option.faculty_id}|${this.normalizeSlotLabel(option.time_slot_label)}`] = true;
  this.usedRooms[`${option.room_id}|${this.normalizeSlotLabel(option.time_slot_label)}`] = true;

  const f = this.facultyList.find(fac => fac.id === option.faculty_id);
  if (f) f.current_load = (f.current_load || 0) + (subject.units || 0);

  // Remove from unassigned
  this.unassigned = this.unassigned.filter(u => u._localId !== subject._localId);

  // Remove conflicting options from other unassigned subjects and capture removals for undo
  const removals = this.removeConflictingOptions(option, subject._localId);

  // Deep clone the subject so the original possible assignments are preserved
  const originalSubject = JSON.parse(JSON.stringify(subject));

  this.undoStack.push({
    type: "manual_assign",
    facultyName,
    subject: originalSubject, // store a clone, not the modified reference
    option,
    removedConflicts: removals,
  });

  this.showSuccess(`Assigned "${subject.subject_display}" to ${facultyName} (manual/forced)`);

  this.refreshAISuggestions();
}

,
     getFacultyDepartment(facultyId) {
    const faculty = this.facultyList.find(f => f.id === facultyId);
    return faculty?.department || null;
  },
  findFacultyById(id) {
    return this.facultyList.find(f => f.id === id) || null;
  },
  findFacultyByName(name) {
    if (!name) return null;
    return this.facultyList.find(f => (f.name || '').toString().trim() === name.toString().trim()) || null;
  },
  findRoomByName(name) {
    if (!name) return null;
    return (this.roomList || []).find(r => (r.name || '').toString().trim() === name.toString().trim()) || null;
  },
  // Parse slot label like 'Tue 06:00-09:00' -> { day: 'Tue', start: minutes, end: minutes }
  parseSlotLabel(label) {
    if (!label || typeof label !== 'string') return null;
    try {
      const s = label.replace(/–/g, '-').trim();
      const m = s.match(/^([A-Za-z]+)\s+(\d{1,2}):(\d{2})\s*[-–]\s*(\d{1,2}):(\d{2})$/);
      if (!m) return null;
      const day = m[1];
      const sh = parseInt(m[2], 10); const sm = parseInt(m[3], 10);
      const eh = parseInt(m[4], 10); const em = parseInt(m[5], 10);
      return { day, start: sh * 60 + sm, end: eh * 60 + em };
    } catch (e) {
      return null;
    }
  },

  // Return canonical label string used for keys: Day HH:MM-HH:MM
  normalizeSlotLabel(label) {
    const p = this.parseSlotLabel(label);
    if (!p) return label || "";
    const pad = (n) => String(Math.floor(n / 60)).padStart(2, '0') + ':' + String(n % 60).padStart(2, '0');
    return `${p.day} ${pad(p.start)}-${pad(p.end)}`;
  },

  intervalsOverlap(aStart, aEnd, bStart, bEnd) {
    if (aStart == null || aEnd == null || bStart == null || bEnd == null) return false;
    return !(aEnd <= bStart || bEnd <= aStart);
  },

  // Check if two slot labels overlap (same day and time ranges intersect)
  slotLabelsOverlap(aLabel, bLabel) {
    const a = this.parseSlotLabel(aLabel);
    const b = this.parseSlotLabel(bLabel);
    if (!a || !b) return false;
    if (a.day !== b.day) return false;
    return this.intervalsOverlap(a.start, a.end, b.start, b.end);
  },

  // Check if faculty has any used slot that overlaps with given slotLabel
  checkSlotConflict(facultyId, slotLabel) {
    if (!facultyId || !slotLabel) return false;
    // iterate keys in usedSlots which are of the form `${fid}|${label}`
    for (const key in this.usedSlots) {
      if (!this.usedSlots[key]) continue;
      const parts = key.split('|');
      if (parts.length < 2) continue;
      const fid = parts[0];
      const label = parts.slice(1).join('|');
      if (String(fid) === String(facultyId)) {
        if (this.slotLabelsOverlap(label, slotLabel)) return true;
      }
    }
    return false;
  },

  // Check if a room has any used slot that overlaps with given slotLabel
  checkRoomConflict(roomId, slotLabel) {
    if (!roomId || !slotLabel) return false;
    for (const key in this.usedRooms) {
      if (!this.usedRooms[key]) continue;
      const parts = key.split('|');
      if (parts.length < 2) continue;
      const rid = parts[0];
      const label = parts.slice(1).join('|');
      if (String(rid) === String(roomId)) {
        if (this.slotLabelsOverlap(label, slotLabel)) return true;
      }
    }
    return false;
  },

  // Check for course section conflicts (same course_section cannot have overlapping times)
  checkCourseSectionConflict(subject, option) {
    if (!subject || !option) return false;
    
    const subjectCourseSection = subject.course_section || subject.courseSection || "-";
    const optionTimeSlot = option.time_slot_label || option.time || "";
    
    if (!subjectCourseSection || subjectCourseSection === "-" || !optionTimeSlot) return false;
    
    // Check all existing assignments for the same course section
    for (const facultyName in this.groupedSchedules) {
      const entries = this.groupedSchedules[facultyName];
      for (const entry of entries) {
        const entryCourseSection = entry.courseSection || entry.course_section || "-";
        const entryTimeSlot = entry.time || entry.time_slot || "";
        
        // Skip if different course section or no time slot
        if (entryCourseSection !== subjectCourseSection || !entryTimeSlot) continue;
        
        // Check if times overlap on the same day
        if (this.slotLabelsOverlap(entryTimeSlot, optionTimeSlot)) {
          return true; // Conflict found
        }
      }
    }
    
    return false; // No conflict
  },

  // Check if a possible assignment would conflict with course section (for suggestion filtering)
  checkCourseSectionConflictForSuggestion(subject, option) {
    if (!subject || !option) return false;
    
    const subjectCourseSection = subject.course_section || subject.courseSection || "-";
    const optionTimeSlot = option.time_slot_label || option.time || "";
    
    if (!subjectCourseSection || subjectCourseSection === "-" || !optionTimeSlot) return false;
    
    // Only check against already assigned subjects, not other unassigned ones
    for (const facultyName in this.groupedSchedules) {
      const entries = this.groupedSchedules[facultyName];
      for (const entry of entries) {
        const entryCourseSection = entry.courseSection || entry.course_section || "-";
        const entryTimeSlot = entry.time || entry.time_slot || "";
        
        // Skip if different course section or no time slot
        if (entryCourseSection !== subjectCourseSection || !entryTimeSlot) continue;
        
        // Check if times overlap on the same day
        if (this.slotLabelsOverlap(entryTimeSlot, optionTimeSlot)) {
          return true; // Conflict found
        }
      }
    }
    
    return false; // No conflict
  },
  confirmOverloadAssign(subject, option) {
    const faculty = option.faculty_name || 'Selected Faculty';
    this.confirmModalTitle = "Confirm Overload";
    this.confirmModalMessage = `Assigning to ${faculty} will exceed their load. Do you want to proceed?`;
    this.confirmModalAction = () => this.assignFromSuggestion(subject, option);
    this.showConfirmModal = true;
  },

  handleConfirm() {
    if (this.confirmModalAction) {
      this.confirmModalAction();
    }
    this.cancelConfirm();
  },

  cancelConfirm() {
    this.showConfirmModal = false;
    this.confirmModalTitle = "";
    this.confirmModalMessage = "";
    this.confirmModalAction = null;
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
      // If refreshAISuggestions precomputed suggestions, return them
      if (subject.possible_assignments) return subject.possible_assignments;
      return [];
    },


    refreshAISuggestions() {
      // 1) For every unassigned subject compute candidate options with metadata and filter basic conflicts
      const candidates = []; // flat list: { subjectLocalId, subjectRef, opt }

      (this.unassigned || []).forEach(u => {
        const units = u.units || 0;
        const derivedDept = (u.department || u.subject_code || u.course_code || '').toString().substring(0,2).toUpperCase();
        (u.possible_assignments_original || []).forEach(opt0 => {
          // Instead of silently dropping candidates that conflict with already-assigned
          // slots/rooms, include them but mark flags so they can be shown (deprioritized)
          // in the UI. Actual assignment will still enforce conflicts.
          const conflictsExistingSlot = this.checkSlotConflict(opt0.faculty_id, opt0.time_slot_label);
          const conflictsExistingRoom = this.checkRoomConflict(opt0.room_id, opt0.time_slot_label);
          const conflictsCourseSection = this.checkCourseSectionConflictForSuggestion(u, opt0);

          const faculty = this.findFacultyById(opt0.faculty_id);
          const faculty_current = faculty ? (faculty.current_load || 0) : 0;
          const faculty_max = faculty ? (faculty.max_load || 12) : 12;
          const willExceed = (faculty_current + (units || 0)) > faculty_max;
          const facultyDept = faculty && faculty.department ? faculty.department.toString().substring(0,2).toUpperCase() : '';
          const deptMatch = derivedDept && facultyDept && derivedDept === facultyDept;

          const opt = {
            ...opt0,
            faculty_current_load: faculty_current,
            faculty_max_load: faculty_max,
            willExceed,
            deptMatch,
            conflictsExistingSlot,
            conflictsExistingRoom,
            conflictsCourseSection,
            dynamic_class: (deptMatch && !willExceed && !conflictsExistingSlot && !conflictsExistingRoom && !conflictsCourseSection) ? 'suitable' : (deptMatch && willExceed) ? 'overload' : (!deptMatch && !willExceed) ? 'underload' : 'mismatch',
          };

          candidates.push({ subjectLocalId: u._localId, subjectRef: u, opt });
        });
      });

      // 2) Group candidates by faculty and pick top N per faculty while avoiding time/room conflicts among picks
      const byFaculty = {};
      candidates.forEach(c => {
        const fid = c.opt.faculty_id || 'null';
        if (!byFaculty[fid]) byFaculty[fid] = [];
        byFaculty[fid].push(c);
      });

      const selectedPerSubject = {};

      Object.keys(byFaculty).forEach(fid => {
        const list = byFaculty[fid];
        // sort by deptMatch desc, conflictsExistingSlot/Room asc (prefer non-conflicting), willExceed asc, course section conflicts last, score desc
        list.sort((a,b) => {
          if ((a.opt.deptMatch ? 1 : 0) !== (b.opt.deptMatch ? 1 : 0)) return (a.opt.deptMatch ? -1 : 1);
          if ((a.opt.conflictsExistingSlot ? 1 : 0) !== (b.opt.conflictsExistingSlot ? 1 : 0)) return (a.opt.conflictsExistingSlot ? 1 : -1);
          if ((a.opt.conflictsExistingRoom ? 1 : 0) !== (b.opt.conflictsExistingRoom ? 1 : 0)) return (a.opt.conflictsExistingRoom ? 1 : -1);
          if ((a.opt.willExceed ? 1 : 0) !== (b.opt.willExceed ? 1 : 0)) return (a.opt.willExceed ? 1 : -1);
          if ((a.opt.conflictsCourseSection ? 1 : 0) !== (b.opt.conflictsCourseSection ? 1 : 0)) return (a.opt.conflictsCourseSection ? 1 : -1);
          return (b.opt.score || 0) - (a.opt.score || 0);
        });

        // local trackers to prevent time/room overlap for this faculty
        const facultyUsedSlots = new Set();
        const facultyUsedRooms = new Set();
        let count = 0;
        for (const entry of list) {
          if (count >= (this.facultySuggestionLimit || 2)) break;
          const opt = entry.opt;
          const subjId = entry.subjectLocalId;
          // do not drop candidates that conflict with existing schedule here; they were
          // deprioritized in the sort. Still avoid local faculty pick overlaps below.
          // check local faculty picks
          let conflictLocal = false;
          for (const sKey of facultyUsedSlots) {
            if (this.slotLabelsOverlap(sKey, opt.time_slot_label)) { conflictLocal = true; break; }
          }
          if (conflictLocal) continue;
          for (const rKey of facultyUsedRooms) {
            if (rKey === String(opt.room_id)) { conflictLocal = true; break; }
          }
          if (conflictLocal) continue;

          // select this candidate
          facultyUsedSlots.add(opt.time_slot_label);
          facultyUsedRooms.add(String(opt.room_id));
          count += 1;

          if (!selectedPerSubject[subjId]) selectedPerSubject[subjId] = [];
          selectedPerSubject[subjId].push(opt);
        }
      });

      // 3) assign selected candidates to each subject's possible_assignments (sorted best-first)
      this.unassigned = (this.unassigned || []).map(u => {
        const picks = selectedPerSubject[u._localId] || [];
        // sort picks for subject by deptMatch desc, willExceed asc, course section conflicts last, score desc
        picks.sort((a,b) => {
          if ((a.deptMatch ? 1 : 0) !== (b.deptMatch ? 1 : 0)) return (a.deptMatch ? -1 : 1);
          if ((a.willExceed ? 1 : 0) !== (b.willExceed ? 1 : 0)) return (a.willExceed ? 1 : -1);
          if ((a.conflictsCourseSection ? 1 : 0) !== (b.conflictsCourseSection ? 1 : 0)) return (a.conflictsCourseSection ? 1 : -1);
          return (b.score || 0) - (a.score || 0);
        });
        return { ...u, possible_assignments: picks };
      });

      this.$forceUpdate();
    },

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

  const normalized = this.normalizeSlotLabel(option.time_slot_label || "");
  const slotKey = `${option.faculty_id}|${normalized}`;
  const roomKey = `${option.room_id}|${normalized}`;

  if (this.usedSlots[slotKey] || this.usedRooms[roomKey] || this.checkSlotConflict(option.faculty_id, option.time_slot_label) || this.checkRoomConflict(option.room_id, option.time_slot_label)) {
    this.showError("This suggestion conflicts with an existing schedule.");
    return;
  }

  // Check for course section conflicts (same course_section cannot have overlapping times)
  if (this.checkCourseSectionConflict(subject, option)) {
    this.showError("This assignment conflicts with another subject in the same course section.");
    return;
  }

  const facultyName = option.faculty_name || "Unknown";
  if (!this.groupedSchedules[facultyName]) this.groupedSchedules[facultyName] = [];

  // Verify the values in the subject object
  const courseCode = subject.course_code || subject.courseCode || subject.subject_code || subject.subjectCode || "-"; // fallback to "-" if not available
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
    faculty_id: option.faculty_id || null,
    room_id: option.room_id || null,
    subject_id: subject.subject_id,
    _localId: subject._localId,
    _assignedManually: true
  });

  this.usedSlots[`${option.faculty_id}|${this.normalizeSlotLabel(option.time_slot_label)}`] = true;
  this.usedRooms[`${option.room_id}|${this.normalizeSlotLabel(option.time_slot_label)}`] = true;

  const f = this.findFacultyById(option.faculty_id);
  if (f) f.current_load = (f.current_load || 0) + (subject.units || 0);

  // Remove from unassigned
  this.unassigned = this.unassigned.filter(u => u._localId !== subject._localId);

  // Remove conflicting options from other unassigned subjects and capture removals for undo
  const removals = this.removeConflictingOptions(option, subject._localId);

  // Deep clone the subject so the original possible assignments are preserved
  const originalSubject = JSON.parse(JSON.stringify(subject));

  this.undoStack.push({
    type: "manual_assign",
    facultyName,
    subject: originalSubject, // store a clone, not the modified reference
    option,
    removedConflicts: removals,
  });

  this.showSuccess(`Assigned "${subject.subject_display}" to ${facultyName} (manual/forced)`);

  this.refreshAISuggestions();
}
,

    // Remove conflicting possible assignments on other unassigned subjects
    // Returns an array of removals for undo: [{ _localId, removed: [opts] }, ...]
    removeConflictingOptions(option, skipLocalId = null) {
      const removals = [];
      if (!option || !option.time_slot_label) return removals;
      const t = option.time_slot_label;
      const fid = option.faculty_id;
      const rid = option.room_id;

      this.unassigned.forEach(u => {
        if (u._localId === skipLocalId) return; // don't touch the subject just assigned
        const original = u.possible_assignments_original || [];
        const removed = [];
        const kept = [];
        original.forEach(o => {
          // remove if same faculty or same room and time overlaps
          const facMatch = o.faculty_id === fid;
          const roomMatch = o.room_id === rid;
          const timeOverlap = this.slotLabelsOverlap(o.time_slot_label, t);
          if (timeOverlap && (facMatch || roomMatch)) {
            removed.push(o);
          } else kept.push(o);
        });
        if (removed.length) {
          removals.push({ _localId: u._localId, removed: JSON.parse(JSON.stringify(removed)) });
          u.possible_assignments_original = kept;
          // update the live suggestions view as well
          u.possible_assignments = (u.possible_assignments || []).filter(o => !(o.time_slot_label === t && (o.faculty_id === fid || o.room_id === rid)));
        }
      });

      return removals;
    },

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

  // Remove from groupedSchedules
  if (this.groupedSchedules[facultyName]) {
    this.groupedSchedules[facultyName] = this.groupedSchedules[facultyName].filter(
      (e) => e._localId !== subject._localId
    );
  }

  // Free up slot and room
  delete this.usedSlots[slotKey];
  delete this.usedRooms[roomKey];

  // Reduce faculty load
  const f = this.facultyList.find(f => f.id === option.faculty_id);
  if (f) f.current_load = Math.max(0, (f.current_load || 0) - (subject.units || 0));

  // Restore unassigned subject fully
  this.unassigned.push({
    ...subject,
    possible_assignments: subject.possible_assignments_original
      ? JSON.parse(JSON.stringify(subject.possible_assignments_original))
      : [],
  });

  // Restore any conflicting options removed from other unassigned subjects when this assignment was made
  const removedConflicts = last.removedConflicts || [];
  removedConflicts.forEach(rc => {
    const target = this.unassigned.find(u => u._localId === rc._localId);
    if (target) {
      target.possible_assignments_original = (target.possible_assignments_original || []).concat(rc.removed || []);
      target.possible_assignments = (target.possible_assignments || []).concat(rc.removed || []);
    }
  });

  this.refreshAISuggestions();
  this.showSuccess(`Undid assignment of "${subject.subject_display}"`);
  },
  undoAllAssignments() {
    if (this.undoStack.length === 0) {
      this.showError("No assignments to undo.");
      return;
    }

    const toUndo = [...this.undoStack];
    this.undoStack = [];

  toUndo.forEach(({ facultyName, subject, option, removedConflicts }) => {
      const slotKey = `${option.faculty_id}|${option.time_slot_label}`;
      const roomKey = `${option.room_id}|${option.time_slot_label}`;

      if (this.groupedSchedules[facultyName]) {
        this.groupedSchedules[facultyName] = this.groupedSchedules[facultyName].filter(
          (e) => e._localId !== subject._localId
        );
      }

      delete this.usedSlots[slotKey];
      delete this.usedRooms[roomKey];

      const fobj = this.findFacultyById(option.faculty_id);
      if (fobj) fobj.current_load = Math.max(0, (fobj.current_load || 0) - (subject.units || 0));

      this.unassigned.push({
        ...subject,
        possible_assignments: subject.possible_assignments_original
          ? JSON.parse(JSON.stringify(subject.possible_assignments_original))
          : [],
      });

      // restore removed conflicts for each undo entry
      (removedConflicts || []).forEach(rc => {
        const target = this.unassigned.find(u => u._localId === rc._localId);
        if (target) {
          target.possible_assignments_original = (target.possible_assignments_original || []).concat(rc.removed || []);
          target.possible_assignments = (target.possible_assignments || []).concat(rc.removed || []);
        }
      });
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
            semester: this.semester, // send the string directly
          }),
        });

        const result = await res.json();
        if (!result || !result.success) {
          this.showError(result?.message || "Failed to generate schedule.");
          return;
        }

  const grouped = {};

  // reset occupied trackers so we can populate them from AI assigned schedule
  this.usedSlots = {};
  this.usedRooms = {};
        const sched = Array.isArray(result.schedule) ? result.schedule : [];

        sched.forEach((s, idx) => {
          const facultyName = (s.faculty || s.faculty_name || "Unassigned").toString().trim();
          if (!grouped[facultyName]) grouped[facultyName] = [];

          // normalize time and ids
          const timeSlot = s.time_slot || s.time_slot_label || "";
          const facultyId = s.faculty_id ?? s.facultyId ?? null;
          const roomId = (s.room_id ?? s.roomId ?? null) || s.room || null;

          grouped[facultyName].push({
            subject: s.subject_title || s.subject || 'Untitled',
            time: timeSlot,
            classroom: s.room_name || s.room || "",
            // try all likely keys for course/subject code
            courseCode: s.course_code || s.courseCode || s.subject_code || s.course || "",
            courseSection: s.course_section || s.courseSection || "",
            units: s.units || 0,
            faculty: s.faculty_name || facultyName,
            faculty_id: facultyId,
            room_id: roomId,
            subject_id: s.subject_id ?? s.id ?? null,
            _localId: `${Date.now()}-${idx}-${Math.random()}`,
          });

          // mark occupied slots/rooms so suggestions that conflict are filtered
          if (facultyId && timeSlot) this.usedSlots[`${facultyId}|${this.normalizeSlotLabel(timeSlot)}`] = true;
          if (roomId && timeSlot) this.usedRooms[`${roomId}|${this.normalizeSlotLabel(timeSlot)}`] = true;
        });

       this.unassigned = result.unassigned.map((u, idx) => {
  const courseSection =
    u.course_section ||
    u.curriculum_subject?.course_section ||
    u.curriculum?.course_section ||
    "-";

  return {
    ...u,
    course_section: courseSection,
    possible_assignments_original: u.possible_assignments || [],
    possible_assignments: u.possible_assignments || [],
    subject_display: `${u.course_code ? u.course_code + " - " : ""}${u.subject_title || u.subject || "Untitled"} (${courseSection})`,
    _localId: `${Date.now()}-${idx}-${Math.random()}`,
  };
});


        this.groupedSchedules = grouped;

        // Populate usedSlots/usedRooms from groupedSchedules entries (including manual edits)
        Object.values(this.groupedSchedules).forEach(entries => {
          entries.forEach(e => {
            const t = e.time || e.time_slot || '';
            const norm = this.normalizeSlotLabel(t);
            let fid = e.faculty_id || null;
            if (!fid && e.faculty) {
              const fobj = this.findFacultyByName(e.faculty);
              fid = fobj ? fobj.id : null;
            }
            let rid = e.room_id || e.room || null;
            if (!rid && e.classroom) {
              const robj = this.findRoomByName(e.classroom);
              rid = robj ? robj.id : null;
            }
            if (fid && t) this.usedSlots[`${fid}|${this.normalizeSlotLabel(norm)}`] = true;
            if (rid && t) this.usedRooms[`${rid}|${this.normalizeSlotLabel(norm)}`] = true;
          });
        });

        this.sidebarOpen = true;

        // Build initial faculty list from AI suggestions and grouped schedules so loads and departments are available
        const facultyMap = {};
        // Initialize faculty entries from AI suggestion metadata but DO NOT trust faculty_current_load
        // from suggestions alone (they may later be recomputed). We'll start with 0 and add
        // loads from already assigned schedule entries below to avoid double-counting.
        (this.unassigned || []).forEach(u => {
          (u.possible_assignments_original || []).forEach(opt => {
            if (!facultyMap[opt.faculty_id]) {
              facultyMap[opt.faculty_id] = {
                id: opt.faculty_id,
                name: opt.faculty_name || `F-${opt.faculty_id}`,
                current_load: 0, // start at zero; will aggregate from groupedSchedules
                max_load: opt.faculty_max_load || 12,
                department: opt.faculty_department || null,
                type: opt.faculty_type || opt.type || null,
              };
            } else {
              // ensure max_load and department exist if missing
              facultyMap[opt.faculty_id].max_load = facultyMap[opt.faculty_id].max_load || opt.faculty_max_load || 12;
              facultyMap[opt.faculty_id].department = facultyMap[opt.faculty_id].department || opt.faculty_department || null;
              facultyMap[opt.faculty_id].type = facultyMap[opt.faculty_id].type || opt.faculty_type || opt.type || null;
            }
          });
        });

        Object.values(this.groupedSchedules).forEach(entries => {
          entries.forEach(e => {
            // Match by faculty id first (if possible), then by name fallback
            let existing = null;
            if (e.faculty_id) existing = facultyMap[e.faculty_id];
            if (!existing) existing = Object.values(facultyMap).find(f => f.name === (e.faculty || e.faculty_name));
            if (existing) existing.current_load = (existing.current_load || 0) + (Number(e.units) || 0);
            else {
              const key = `name-${e.faculty || e.faculty_name}`;
              if (!facultyMap[key]) facultyMap[key] = { id: null, name: e.faculty || e.faculty_name, current_load: Number(e.units) || 0, max_load: 12, department: null };
              else facultyMap[key].current_load += Number(e.units) || 0;
            }
          });
        });

        this.facultyList = Object.values(facultyMap);

        // Deduplicate/merge facultyList by id or name to avoid duplicate faculty entries
        const merged = {};
        this.facultyList.forEach(f => {
          const key = f.id || `name-${f.name}`;
          if (!merged[key]) merged[key] = { ...f };
          else {
            merged[key].current_load = Math.max(merged[key].current_load || 0, f.current_load || 0);
            merged[key].max_load = merged[key].max_load || f.max_load || 12;
            merged[key].department = merged[key].department || f.department || null;
            merged[key].id = merged[key].id || f.id || null;
            merged[key].name = merged[key].name || f.name || '';
            merged[key].type = merged[key].type || f.type || f.faculty_type || null;
          }
        });
    this.facultyList = Object.values(merged);

    // Recompute and display AI suggestions limited per faculty
    this.refreshAISuggestions();

  // Add missing helper: confirmation for overload assignments

    // await this.refreshFacultiesAndRooms();

    this.showSuccess(result.message || "Schedule generated successfully!");
      } catch (err) {
        console.error(err);
        this.showError("Could not generate schedule.");
      } finally {
        this.hide();
        this.loading = false;
      }
    },

    // Save current schedule (groupedSchedules + unassigned) as pending or finalized
async saveSchedule(mode = 'pending') {
  if (!this.academicYear) return this.showError('Please set Academic Year before saving.');

  // Prevent finalizing if there are still unassigned subjects
  if (mode === 'finalized' && (this.unassigned || []).length > 0) {
    return this.showError('Cannot finalize schedule: there are still unassigned subjects.');
  }

  // Helper to build schedule array
  const buildScheduleArray = (payload, includeUnassigned = false) => {
    const resolveFacultyId = (row) => {
      if (row.faculty_id) return row.faculty_id;
      const name = (row.faculty || row.faculty_name || '').toString().trim();
      if (!name) return null;
      const match = (this.facultyList || []).find(f => (f.name || '').toString().trim() === name);
      return match ? match.id : null;
    };

    const arr = [];
    (Object.entries(this.groupedSchedules) || []).forEach(([faculty, entries]) => {
      entries.forEach(r => {
        const fid = resolveFacultyId(r);
        arr.push({
          faculty: r.faculty || r.faculty_name || null,
          faculty_id: fid,
          subject: r.subject || r.subject_title || null,
          time: r.time || r.time_slot || null,
          classroom: r.classroom || r.room_name || r.room || null,
          course_code: r.courseCode || r.course_code || null,
          course_section: r.courseSection || r.course_section || null,
          units: Number(r.units || 0),
          academicYear: this.academicYear,
          semester: this.semester,
          status: mode === 'finalized' ? 'finalized' : 'pending',
          batch_id: this.selectedBatch || null,
        });
      });
    });

    if (includeUnassigned) {
      (this.unassigned || []).forEach(u => {
        arr.push({
          faculty: u.faculty || u.faculty_name || null,
          subject: u.subject_display || u.subject_title || u.subject || null,
          time: u.time || u.time_slot_label || null,
          classroom: u.classroom || u.room_name || u.room || null,
          course_code: u.course_code || u.courseCode || u.subject_code || null,
          course_section: u.course_section || u.courseSection || null,
          units: Number(u.units || 0),
          academicYear: this.academicYear,
          semester: this.semester,
          status: 'pending',
          possible_assignments: u.possible_assignments || u.possible_assignments_original || [],
          payload: u,
        });
      });
    }

    return arr;
  };

  this.show();
  try {
    // If finalizing without a batch_id, first save as pending
    if (mode === 'finalized' && !this.selectedBatch) {
      const pendingPayload = buildScheduleArray({ grouped: this.groupedSchedules }, true);
      const pendingRes = await fetch(`${this.apiBase}/save-schedule`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ schedule: pendingPayload }),
      });

      if (!pendingRes.ok) {
        const text = await pendingRes.text();
        this.showError(`Failed to auto-save pending before finalizing (${pendingRes.status}): ${text.substring(0, 300)}`);
        return;
      }

      const pendingData = await pendingRes.json().catch(() => null);
      if (!pendingData || !pendingData.success || !pendingData.batch_id) {
        this.showError('Failed to auto-save pending before finalizing.');
        return;
      }

      this.selectedBatch = pendingData.batch_id; // now we have a batch_id
    }

    // Build schedule array for final call
    const scheduleArray = buildScheduleArray({ grouped: this.groupedSchedules }, mode === 'pending');

    // If finalizing, validate faculty_id presence to avoid DB errors
    if (mode === 'finalized') {
      const missingIds = scheduleArray.filter(x => !x.faculty_id);
      if (missingIds.length) {
        this.showError('Cannot finalize: some rows are missing faculty IDs. Please ensure each assignment has a faculty selected.');
        this.hide();
        return;
      }
    }

    const url = mode === 'finalized' 
      ? `${this.apiBase}/finalized-schedules` 
      : `${this.apiBase}/save-schedule`;

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ schedule: scheduleArray, batch_id: this.selectedBatch }),
    });

    if (!res.ok) {
      const text = await res.text();
      this.showError(`Save failed (${res.status}): ${text.substring(0, 300)}`);
      return;
    }

    const data = await res.json().catch(() => null);
    if (data && data.success) {
      this.showSuccess(mode === 'pending' ? 'Saved as pending.' : 'Schedule finalized.');
    } else {
      const serverMsg = data && (data.message || data.error) ? (data.message || data.error) : 'Failed to save schedule.';
      this.showError(serverMsg);
    }

  } catch (err) {
    console.error(err);
    this.showError('Network error while saving schedule.');
  } finally {
    this.hide();
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

/* Badges for suggestion flags */
.pa-badges { display: inline-flex; gap: 6px; margin-left: 8px; align-items: center; }
.badge { padding: 2px 6px; border-radius: 6px; font-size: 11px; color: #fff; }
.badge-dept { background: #16a34a; }
.badge-overload { background: #e11d48; }
.badge-conflict { background: #f59e0b; color: #111; }
.badge-course-section { background: #8b5cf6; color: #fff; }

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
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 8px;
  transition: background-color 0.3s ease, transform 0.2s ease;
  cursor: pointer;
  border: 1px solid transparent;
}
.faculty-item {
  border-radius: 8px;
  padding: 8px 10px;
  margin-bottom: 8px;
  transition: background 0.2s ease;
}

/* Visual indicator colors */
.faculty-item.suitable {
  background-color: rgba(0, 200, 0, 0.1);
  border-left: 5px solid #00b300;
}

.faculty-item.underload {
  background-color: rgba(255, 255, 0, 0.15);
  border-left: 5px solid #e6b800;
}

.faculty-item.overload {
  background-color: rgba(255, 165, 0, 0.2);
  border-left: 5px solid #ff9900;
}

.faculty-item.mismatch {
  background-color: rgba(255, 0, 0, 0.15);
  border-left: 5px solid #e60000;
}

/* Add a hover effect for clarity */
.faculty-item:hover {
  transform: scale(1.02);
  background-color: rgba(255, 255, 255, 0.2);
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

<style scoped>
/* Leave Prompt Overlay */
.leave-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.leave-modal {
  background: #fff7ed; /* warning tinted */
  padding: 18px 20px 16px 20px;
  border-radius: 10px;
  width: 380px;
  box-shadow: 0 12px 28px rgba(0,0,0,0.25);
  border: 1px solid #fdba74; /* amber-300 */
  position: relative;
}
.leave-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.leave-icon {
  font-size: 20px;
}
.leave-modal h3 {
  margin: 0 0 8px 0;
  color: #9a3412; /* amber-800 */
}
.leave-text {
  color: #7c2d12; /* amber-900 */
  margin: 4px 0 10px 0;
}
.leave-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 12px;
}
.leave-actions button {
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ddd;
  cursor: pointer;
}
.leave-actions button:first-child {
  background: #10b981;
  color: #fff;
  border-color: #10b981;
}
.leave-actions button:last-child {
  background: #e5e7eb;
}
.leave-close {
  position: absolute;
  top: 8px;
  right: 8px;
  background: transparent;
  border: none;
  font-size: 16px;
  line-height: 1;
  color: #7c2d12;
  cursor: pointer;
}
</style>