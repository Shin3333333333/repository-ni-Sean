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

        <!-- 🎯 Focused Unassigned Section -->
<!-- 🎯 Unassigned Subjects Section -->
<div class="sidebar-section">
  <h5>Unassigned Subjects Overview</h5>

  <div class="summary-box">
    <p><strong>Total Subjects:</strong> {{ summary?.total_curriculum_subjects ?? 0 }}</p>
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
      <div class="ua-reason" v-if="findUnassignedReason(u.subject_id)">
    ⚠️ {{ findUnassignedReason(u.subject_id).description }}
  </div>


<div class="ua-suggestions">
  <p><strong>Possible Assignments (AI Suggestions):</strong></p>
  <ul>
    <li
      v-for="pa in availableAssignments(u)"

      :key="`${u.subject_id}-${pa.faculty_id}`"
      class="faculty-item"
      :class="{
        overload: facultyState(pa.faculty_id, u.units, u.department).willExceed,
        mismatch: facultyState(pa.faculty_id, u.units, u.department).mismatch,
        underload: facultyState(pa.faculty_id, u.units, u.department).underload
      }"

    >
      👩‍🏫 {{ pa.faculty_name || 'Unknown Faculty' }}
      ({{ getFacultyDepartment(pa.faculty_id) || 'No Dept' }},
      Load: {{ facultyState(pa.faculty_id, u.units).current }}/{{ facultyState(pa.faculty_id, u.units).max }})

      <button
        v-if="!facultyState(pa.faculty_id, u.units).willExceed"
       @click.stop="assignToFaculty(u, pa)"

        class="assign-btn"
      >
        Assign
      </button>

      <button
        v-else
        @click.stop="confirmOverloadAssign(u, pa)"

        class="assign-btn overload-btn"
      >
        ⚠️ Assign
      </button>

      <div class="state-hint">
        <small v-if="facultyState(pa.faculty_id, u.units).mismatch">🚫 Dept Mismatch</small>
        <small v-else-if="facultyState(pa.faculty_id, u.units).willExceed">⚠️ Overload</small>
        <small v-else-if="facultyState(pa.faculty_id, u.units).underload">🟡 Underload</small>
        <small v-else>✅ Suitable</small>
      </div>
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
      usedSlots: {},     // map key -> true (key format: `${faculty_id}|${time_slot_label}`)
      usedRooms: {}, 
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
      this.conflicts = result.conflicts || result.conflict_list || [];
      this.unassignedReasons = result.unassigned_reasons || result.conflicts?.filter(c => c.type === "unassigned_reason") || [];

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
  getFacultyDepartment(fid) {
  const f = this.facultyList.find(f => f.id === fid);
  return f ? f.department : null;
},

findFacultyById(fid) {
  return this.facultyList.find(f => f.id === fid) || {};
},

facultyState(fid, subjectUnits = 0, subjectDept = null) {
  const f = this.facultyList.find(f => f.id === fid);
  if (!f) return { current: 0, max: 0, willExceed: false, mismatch: false, underload: false };

  const current = this.facultyLoads[f.name] || f.current_load || 0;
  const max = f.max_load || 12;
  const willExceed = current + subjectUnits > max;
  const underload = current + subjectUnits < max * 0.5;
  const mismatch = subjectDept && !f.department?.includes(subjectDept);

  return { current, max, willExceed, mismatch, underload };
},


    findUnassignedReason(subjectId) {
    return this.unassignedReasons.find(
      (r) => r.subject_id === subjectId
    );
  },
confirmOverloadAssign(subject, option) {
  const facultyName =
    option.faculty_name ||
    this.findFacultyById(option.faculty_id)?.name ||
    "Unknown";

  const confirmAssign = confirm(
    `Assigning "${subject.subject_display || subject.subject_title || subject.subject}" to ${facultyName} will exceed their max load. Proceed?`
  );
  if (!confirmAssign) return;

  // mark used slot & room (Vue 3: direct assignment is reactive)
  const slotKey = `${option.faculty_id}|${option.time_slot_label}`;
  const roomKey = `${option.room_id}|${option.time_slot_label}`;
  this.usedSlots[slotKey] = true;
  this.usedRooms[roomKey] = true;

  // remove from unassigned
  this.unassigned = this.unassigned.filter(
    (u) => u._localId !== subject._localId
  );

  // add to groupedSchedules using option data
  const facultyNameFinal = option.faculty_name || facultyName;
  if (!this.groupedSchedules[facultyNameFinal]) {
    this.groupedSchedules[facultyNameFinal] = [];
  }

  this.groupedSchedules[facultyNameFinal].push({
    subject: subject.subject_display || subject.subject_title || subject.subject,
    time: option.time_slot_label || subject.time || "",
    classroom: option.room_name || subject.room_name || "",
    courseCode: subject.course_code || subject.courseCode || "",
    courseSection: subject.course_section || subject.courseSection || "",
    units: subject.units || 0,
    faculty: facultyNameFinal,
    subject_id: subject.subject_id,
    _localId: subject._localId,
    _assignedManually: true
  });

  this.showSuccess(
    `Assigned "${subject.subject_display || subject.subject_title}" to ${facultyNameFinal} (overload)`
  );
},

assignToFaculty(subject, option) {
  // check availability
  const slotKey = `${option.faculty_id}|${option.time_slot_label}`;
  const roomKey = `${option.room_id}|${option.time_slot_label}`;

  // If slot/room already used, block and show message
  if (this.usedSlots[slotKey] || this.usedRooms[roomKey]) {
    this.showError("Selected time/room already taken. Choose another option.");
    return;
  }

  // check load
  const fobj = this.findFacultyById(option.faculty_id) || {};
  const facultyName = option.faculty_name || fobj.name || "Unknown";
  const currentLoad = fobj.current_load ?? 0;
  const maxLoad = fobj.max_load ?? 12;
  if (currentLoad + (subject.units || 0) > maxLoad) {
    return this.confirmOverloadAssign(subject, option);
  }

  // ✅ Vue 3: mark slot & room directly (no $set)
  this.usedSlots[slotKey] = true;
  this.usedRooms[roomKey] = true;

  // remove from unassigned
  this.unassigned = this.unassigned.filter(u => u._localId !== subject._localId);

  // add to groupedSchedules using option data
  if (!this.groupedSchedules[facultyName]) {
    this.groupedSchedules[facultyName] = [];
  }

  this.groupedSchedules[facultyName].push({
    subject: subject.subject_display || subject.subject_title || subject.subject,
    time: option.time_slot_label || subject.time || "",
    classroom: option.room_name || subject.room_name || "",
    courseCode: subject.course_code || subject.courseCode || "",
    courseSection: subject.course_section || subject.courseSection || "",
    units: subject.units || 0,
    faculty: facultyName,
    subject_id: subject.subject_id,
    _localId: subject._localId,
    _assignedManually: true
  });

  // optimistic local update
  if (fobj) {
    fobj.current_load = (fobj.current_load || 0) + (subject.units || 0);
  }

  this.showSuccess(
    `Assigned "${subject.subject_display || subject.subject_title}" to ${facultyName}`
  );
},

availableAssignments(subject) {
  const list = subject.possible_assignments || [];
  return list.filter(opt => {
    const slotKey = `${opt.faculty_id}|${opt.time_slot_label}`;
    const roomKey = `${opt.room_id}|${opt.time_slot_label}`;
    return !this.usedSlots[slotKey] && !this.usedRooms[roomKey];
  });
},
,

  suggestedFaculties(subject) {
  if (!subject.department) return [];

  return this.facultyList
    .map(f => {
      // current load; fallback to 0
      const currentLoad = f.current_load ?? 0;
      const willExceed = currentLoad + (subject.units || 0) > f.max_load;

      return {
        ...f,
        currentLoad,
        canAssign: currentLoad < f.max_load,
        willExceed,
      };
    })
    .filter(f => f.department?.includes(subject.department))
    .sort((a, b) => {
      // prioritize those who can take without overload
      if (a.canAssign && !b.canAssign) return -1;
      if (!a.canAssign && b.canAssign) return 1;
      return a.currentLoad - b.currentLoad; // lower load first
    })
    .slice(0, 5); // top 5 suggestions
},

  suggestedRooms(subject) {
    return this.roomList
      .filter(r => r.status === "Active")
      .slice(0, 3);
  },
  
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
        subjectCode: "",
        subject: "",
        time: "",
        classroom: "",
        courseCode: "",
        courseSection: "",
        units: 0,
        faculty: groupKey,
        _localId: Date.now() + Math.random(),
      });
    },
    removeEntry(groupKey, index) {
      this.groupedSchedules[groupKey].splice(index, 1);
    },
   async saveSchedule(status = "pending") {
    if (!this.groupedSchedules || Object.keys(this.groupedSchedules).length === 0) {
      this.showError("No schedule to save. Generate a schedule first.");
      return;
    }

    if (!this.academicYear) {
      this.showError("Please provide Academic Year before saving.");
      return;
    }

    this.loading = true;
    this.show();

    try {
      const scheduleItems = [];

      // Include groupedSchedules
      Object.values(this.groupedSchedules).forEach(entries => {
        entries.forEach(e => {
          scheduleItems.push({
            faculty: e.faculty || e._selectedFaculty || "Unknown",
            subject: e.subject || e.subject_title || "Untitled",
            time: e.time || null,
            classroom: e.classroom || null,
            course_code: e.courseCode || e.course_code || "",
            course_section: e.courseSection || e.course_section || "",
            units: Number(e.units) || 0,
            academicYear: this.academicYear,
            semester: this.semester,
            status,
          });
        });
      });

      // Include unassigned as pending too
      this.unassigned.forEach(u => {
        scheduleItems.push({
          faculty: null,
          subject: u.subject || u.subject_title || "Untitled",
          time: u.time || null,
          classroom: u.classroom || null,
          course_code: u.courseCode || u.course_code || "",
          course_section: u.courseSection || u.course_section || "",
          units: Number(u.units) || 0,
          academicYear: this.academicYear,
          semester: this.semester,
          status,
          unassigned: true
        });
      });

      const payload = { schedule: scheduleItems };

      const res = await fetch("/api/save-schedule", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const result = await res.json();
      if (!result.success) {
        this.showError(result.message || "Failed to save schedule.");
        return;
      }

      this.showSuccess(result.message || "Schedule saved successfully!");
    } catch (err) {
      console.error(err);
      this.showError("An error occurred while saving schedule.");
    } finally {
      this.hide();
      this.loading = false;
    }
  },
    // 🧩 UPDATED: send semester_id instead of semester text
    async generateSchedule() {
      

      if (!this.academicYear) {
        this.showError("Please provide Academic Year before generating.");
        return;
      }
      this.groupedSchedules = {}; // clear old schedule
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
        semester: this.semester,  // <- send the string directly
      }),

        });
        console.log("Sending request with semester_id:", semester_id, "academicYear:", this.academicYear);
        
        const result = await res.json();
        console.log("API response schedule:", result.schedule);
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
          subject: s.subject_title,
          time: s.time_slot,
          classroom: s.room_name,
          courseCode: s.course_code,
          courseSection: s.course_section,
          units: s.units,
          faculty: s.faculty_name,
          subject_id: s.subject_id ?? s.id ?? null,
          _localId: `${Date.now()}-${idx}-${Math.random()}`,
        });


        });

     this.unassigned = Array.isArray(result.unassigned)
  ? result.unassigned.map((u, idx) => ({
      ...u,
      subject_display:
        u.subject_display ||
        `${u.course_code ? u.course_code + ' - ' : ''}${u.subject_title || u.subject || 'Untitled'}`,
      _selectedFaculty: "",
      _selectedRoom: "",
      _localId: `${Date.now()}-${idx}-${Math.random()}`,
    }))
  : [];



        this.summary = result.summary || null;
        this.conflicts = result.conflicts || result.conflict_list || []; // 🆕

        this.groupedSchedules = Object.keys(grouped).length ? grouped : {};
        this.sidebarOpen = true;

        await this.refreshFacultiesAndRooms();
                  // Merge current_load info from unassigned possible_assignments and assigned schedule
          const loads = {};
          // from assigned schedule (result.schedule) - result is available in this scope
          if (Array.isArray(result.schedule)) {
            result.schedule.forEach(s => {
              const fid = s.faculty_id;
              const units = Number(s.units || 0);
              if (fid) loads[fid] = (loads[fid] || 0) + units;
            });
          }
          // Merge into facultyList
          this.facultyList.forEach(f => {
            if (loads[f.id] !== undefined) {
              f.current_load = loads[f.id];
            } else if (f.current_load === undefined) {
              f.current_load = 0;
            }
            if (f.max_load === undefined) f.max_load = f.max_load || 12;
          });
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
          fetch("/api/professors").then((r) => r.json()).catch(() => ({ items: [] })),
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

the assignementfrom the suggested doesn't work