<template>
  <div class="pending-panel">
    <!-- ====== Header Controls ====== -->
    <div class="header grid">
      <div class="col-6 left-controls">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="🔍 Search by academic year or semester..."
          class="filter-select"
        />
      </div>
      <div class="col-6 right-controls">
        <button @click="exitSchedule" class="exit-btn">Exit</button>
      </div>
    </div>

    <!-- ====== Batch List ====== -->
    <div class="batch-list">
      <h3>📋 Pending Schedule Batches</h3>
      <table class="create-table">
        <thead>
          <tr>
            <th>Batch ID</th>
            <th>Academic Year</th>
            <th>Semester</th>
            <th>Created At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in filteredBatches" :key="b.batch_id">
            <td>{{ b.batch_id }}</td>
            <td>{{ b.academicYear }}</td>
            <td>{{ b.semester }}</td>
            <td>{{ formatDate(b.created_at) }}</td>
            <td>
              <button class="view-btn" @click="openBatch(b.batch_id)">👁 View</button>
              <button class="delete-btn" @click="deleteBatch(b.batch_id)">🗑 Delete</button>
            </td>
          </tr>
          <tr v-if="!filteredBatches.length">
            <td colspan="5" class="text-center">No pending batches found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ====== Modal for selected batch ====== -->
    <div v-if="showModal && selectedBatch" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>🧑‍🏫 Batch {{ selectedBatch }}</h3>
          <div class="faculty-filter">
            <label for="facultySelect">👩‍🏫 Filter Faculty:</label>
            <select id="facultySelect" v-model="facultyFilter" class="filter-select">
              <option value="">📋 Show All Faculty</option>
              <option
                v-for="(count, facultyName) in facultyCounts"
                :key="facultyName"
                :value="facultyName"
              >
                {{ facultyName }} ({{ count }})
              </option>
            </select>
          </div>
         <!-- ====== Header Buttons ====== -->
<div class="header-buttons">
  <div class="action-buttons">
    <button class="edit-btn" @click="toggleEditMode">
      {{ editMode ? 'Finish Editing' : 'Edit' }}
    </button>
    <button v-if="editMode" class="save-btn" @click="saveChanges">
      💾 Save Changes
    </button>
    <button class="undo-btn" @click="undoLastAction">
      ↩️ Undo Last Action
    </button>
    <button class="finalize-btn" @click="finalizeBatch">Finalize</button>
    <button class="delete-btn" @click="deleteBatch(selectedBatch)">Delete</button>
    <button class="exit-btn" @click="closeModal">Exit</button>
  </div>
</div>
        </div>

        <!-- ====== Summary Overview ====== -->
        <div class="summary-overview" v-if="pendingSchedules.length">
          <h4>📊 Summary Overview</h4>
          <div class="summary-cards">
            <div class="summary-card assigned">
              <h5>Total Assigned</h5>
              <p>{{ totalAssigned }}</p>
            </div>
            <div class="summary-card unassigned">
              <h5>Total Unassigned</h5>
              <p>{{ totalUnassigned }}</p>
            </div>
            <div class="summary-card conflicts">
              <h5>Conflicts</h5>
              <p>{{ totalConflicts }}</p>
            </div>
          </div>
        </div>

        <!-- ====== Quick Assign Unassigned Subjects ====== -->
        <div
          class="unassigned-top"
          v-if="pendingSchedules.length && pendingSchedules.some(s => !s.faculty || s.faculty === 'Unknown')"
        >
          <h4>Unassigned Subjects (Quick Assign)</h4>
          <button class="auto-assign-btn" @click="autoAssignAll">⚙️ Auto Assign All</button>

          <table class="create-table">
            <thead>
              <tr>
                <th>Subject Code</th>
                <th>Subject Title</th>
                <th>Course Section</th>
                <th>Units</th>
                <th>Possible Assignments</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="u in pendingSchedules.filter(s => !s.faculty || s.faculty === 'Unknown')"
                :key="u.id"
              >
                <td>{{ u.subject_code || u.subject_code_label || '—' }}</td>
                <td>{{ u.subject || u.subject_title || '—' }}</td>
                <td>{{ u.course_section || '—' }}</td>
                <td>{{ u.units || 3 }}</td>
                <td>
                  <select
                    v-if="getPossibleAssignments(u).length"
                    @change="assignSuggestion(u.id, JSON.parse($event.target.value))"
                  >
                    <option value="">Select Possible Assignment</option>
                    <option
                      v-for="(pa, i) in getPossibleAssignments(u)"
                      :key="i"
                      :value="JSON.stringify(pa)"
                    >
                      {{ pa.faculty_name || pa.faculty }} — {{ pa.time || pa.time_slot_label }}
                      ({{ pa.room_name || pa.classroom }})
                      <span v-if="suggestionFlags(pa, u).bestFit">⭐ Best Fit</span>
                    </option>
                  </select>
                  <span v-else>No possible assignments</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      <!-- ====== Faculty Groups Table ====== -->
<div v-for="(facultySchedules, facultyName) in groupedByFaculty" :key="facultyName" class="faculty-section">
  <h4>{{ facultyName }}</h4>

  <table class="create-table">
    <thead>
      <tr>
        <th v-if="editMode">⇅</th>
        <th v-if="deleteMode">
          <input type="checkbox" @change="toggleAll($event, facultySchedules)" />
        </th>
        <th v-for="col in tableColumns" :key="col">{{ col }}</th>
      </tr>
    </thead>

    <draggable
      v-model="groupedByFaculty[facultyName]"
      :disabled="!editMode"
      handle=".drag-handle"
      item-key="id"
      tag="tbody"
    >
      <template #item="{ element, index }">
        <tr>
          <td v-if="editMode" class="drag-handle" style="cursor: grab;">☰</td>
          <td v-if="deleteMode">
            <input type="checkbox" v-model="selectedRows" :value="element.id" />
          </td>

          <!-- Each column individually draggable -->
          <td
            v-for="(col, cIndex) in tableColumns"
            :key="cIndex"
            draggable="true"
            @dragstart="startDrag($event, element, col)"
            @dragover.prevent
           @drop="onDrop($event, facultyName, index, col)"
            class="draggable-cell"
            :class="{ editable: editMode }"
            @dblclick="enableEdit(facultyName, index, col)"
          >
            <input
              v-if="isEditingCell(facultyName, index, col)"
              v-model="editableValue"
              @blur="saveEdit(facultyName, index, col)"
              @keyup.enter="saveEdit(facultyName, index, col)"
              class="edit-input"
              autofocus
            />
            <div v-else>
              <div class="cell-content">
                <div class="cell-main">{{ element[col.toLowerCase().replace(' ', '_')] }}</div>

                <!-- Inline suggestions for unassigned subjects (optional) -->
                <div
                  v-if="col === 'Subject' && element.faculty === 'Unknown' && (element.possible_assignments && element.possible_assignments.length)"
                  class="suggestions-inline"
                >
                  <div
                    v-for="(sug, si) in element.possible_assignments"
                    :key="si"
                    class="suggestion-row"
                  >
                    <div class="s-left">
                      <div class="s-title">{{ sug.faculty_name || sug.faculty || 'Faculty' }}</div>
                      <div class="s-meta">{{ sug.time || sug.time_slot_label || '' }} • {{ sug.room_name || sug.classroom || '' }}</div>
                    </div>
                    <div class="s-right">
                      <div class="badges">
                        <span
                          v-if="element.assigned_suggestion && (element.assigned_suggestion.faculty_id == (sug.faculty_id || sug.id))"
                          class="badge assigned"
                        >Assigned</span>
                      </div>
                      <div class="s-actions">
                        <button
                          class="assign-btn small"
                          @click.stop="assignSuggestion(element.id, sug)"
                          :disabled="suggestionFlags(sug, element).conflictsExistingSlot || suggestionFlags(sug, element).conflictsExistingRoom || (element.assigned_suggestion && (element.assigned_suggestion.faculty_id == (sug.faculty_id || sug.id)))"
                        >Assign</button>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </td>

        </tr>
      </template>
    </draggable>
  </table>

  <div class="total-units">
    Total Load Units: {{ getFacultyTotal(facultySchedules) }}
  </div>
</div>

        <button class="close-btn" @click="closeModal">Close</button>
      </div>
    </div>

    <LoadingModal />
  </div>
</template>


<script>
import LoadingModal from "../../components/LoadingModal.vue";
import { useLoading } from "../../composables/useLoading";
import draggable from "vuedraggable";
import "/resources/css/create.css";

export default {
  components: { LoadingModal, draggable },
  data() {
    return {
      searchQuery: "",
      selectedBatch: null,
      batchList: [],
      pendingSchedules: [],
      showModal: false,
      editMode: false,
      deleteMode: false,
      selectedRows: [],
      editableCell: null,
      editableValue: "",
      tableColumns: ["Subject Code","Subject", "Time", "Classroom",  "Course Section", "Units"],
      dragData: null,
      facultyFilter: "",
      usedSlots: {},
      usedRooms: {},
      actionHistory: [], // Stack to store undoable actions
    };
  },
  setup() {
    const { show, hide } = useLoading();
    return { show, hide };
  },
  mounted() {
    this.loadPendingSchedules();
  },
  computed: {
    filteredBatches() {
      const q = this.searchQuery.toLowerCase();
      return !q ? this.batchList : this.batchList.filter(
        (b) => b.academicYear?.toLowerCase().includes(q) ||
               b.semester?.toLowerCase().includes(q) ||
               b.batch_id?.toLowerCase().includes(q)
      );
    },
    facultyCounts() {
      const counts = {};
      for (const s of this.pendingSchedules) {
        if (s.faculty && s.faculty.toLowerCase() !== 'unknown') counts[s.faculty] = (counts[s.faculty] || 0) + 1;
      }
      return counts;
    },
    groupedByFaculty() {
      const grouped = {};
      for (const s of this.pendingSchedules) {
        if (s.faculty && s.faculty.toLowerCase() !== 'unknown') {
          if (!grouped[s.faculty]) grouped[s.faculty] = [];
          grouped[s.faculty].push(s);
        }
      }
      if (this.facultyFilter) {
        return Object.fromEntries(Object.entries(grouped).filter(([f]) => f.toLowerCase().includes(this.facultyFilter.toLowerCase())));
      }
      return grouped;
    },
    totalAssigned() {
      return this.pendingSchedules.filter(s => s.faculty && s.faculty !== "Unknown").length;
    },
    totalUnassigned() {
      return this.pendingSchedules.filter(s => !s.faculty || s.faculty === "Unknown").length;
    },
totalConflicts() {
  let conflicts = 0;
  const seenFac = {};
  const seenRoom = {};

  for (const s of this.pendingSchedules) {
    // Skip unassigned subjects
    if (!s.faculty || s.faculty === "Unknown" || !s.time || !s.classroom) continue;

    const keyFac = `${s.faculty}|${s.time}`;
    const keyRoom = `${s.classroom}|${s.time}`;

    if (seenFac[keyFac]) conflicts++;
    else seenFac[keyFac] = true;

    if (seenRoom[keyRoom]) conflicts++;
    else seenRoom[keyRoom] = true;
  }

  return conflicts;
}

  },
  methods: { 
    checkAssignmentConflict(newAssignment) {
  const newSlot = this.normalizeSlotLabel(newAssignment.time_slot_label || newAssignment.time);
  if (!newSlot) return false;

  const newRoom = newAssignment.room_id || newAssignment.room_name;
  const newFaculty = newAssignment.faculty_id || newAssignment.faculty_name;

  for (const s of this.pendingSchedules) {
    // skip same subject
    if (s.id === newAssignment.id) continue;

    // check if subject is already assigned (AI or manual)
    const slot = this.normalizeSlotLabel(s.assigned_time || s.time);
    const room = s.assigned_room_id || s.room_id || s.classroom;
    const fac = s.assigned_faculty_id || s.faculty_id || s.faculty;

    if (!slot || !room || !fac) continue;

    // ✅ Faculty time overlap
    if (String(fac) === String(newFaculty) && this.slotLabelsOverlap(slot, newSlot)) {
      console.warn("❌ Faculty overlap detected:", s.subject, s.time);
      return true;
    }

    // ✅ Room time overlap
    if (String(room) === String(newRoom) && this.slotLabelsOverlap(slot, newSlot)) {
      console.warn("❌ Room overlap detected:", s.subject, s.time);
      return true;
    }
  }

  return false;
}
,

      linkedColumns() {
    return {
      "Subject": ["subject", "subject_code", "course_section"],
      "Subject Code": ["subject_code", "course_section", "subject"],
      "Course Section": ["course_section","subject_code", "subject"],
    };
  },// Start dragging a cell/subject
 // Drag start
startDrag(event, subject, col) {
  if (!this.editMode) return; // block drag if not in edit mode
  this.dragData = { sourceId: subject.id, col };
  event.dataTransfer.effectAllowed = "move";
},
// Drop handler
onDrop(event, targetFaculty, targetRowIndex, targetCol) {
  if (!this.dragData) return;

  const { sourceId, col: sourceCol } = this.dragData;
  const sourceRow = this.pendingSchedules.find(s => s.id === sourceId);
  const facultyRows = this.pendingSchedules.filter(s => s.faculty === targetFaculty);
  const targetRow = facultyRows[targetRowIndex];
  if (!sourceRow || !targetRow) return;

  // Save action for undo (capture full prevValue so undo restores everything)
  this.actionHistory.push({
    type: "drag",
    affectedRows: [
      { id: sourceRow.id, prevValue: { ...sourceRow } },
      { id: targetRow.id, prevValue: { ...targetRow } }
    ]
  });

  // Determine which keys to swap based on the column mapping.
  // But enforce we only ever touch subject identity fields (no time/room/units/faculty)
  const mapped = this.linkedColumns()[targetCol] || [targetCol.toLowerCase().replace(" ", "_")];

  // Allowed keys to actually swap (subject identity only)
  const allowedSwapKeys = ["subject", "subject_code", "course_section"];

  // Perform the swap only for allowed keys
  mapped.forEach(colKey => {
    const key = colKey.toLowerCase().replace(" ", "_");
    if (allowedSwapKeys.includes(key)) {
      const tmp = sourceRow[key];
      sourceRow[key] = targetRow[key];
      targetRow[key] = tmp;
    }
  });

  // IMPORTANT: Do NOT swap time / classroom / units / faculty.
  // If you *want* the dragged subject to become visually in the other faculty row,
  // do NOT change `faculty`, `time`, `classroom`, or `units` here. We intentionally leave them.

  // If either row has possible_assignments or assigned_suggestion, keep them consistent:
  // we swap possible assignment references for those identity fields so suggestions remain tied.
  if (allowedSwapKeys.some(k => mapped.map(m=>m.toLowerCase().replace(" ", "_")).includes(k))) {
    // swap assigned_suggestion references if present, so 'Assigned suggestion' follows the subject identity
    const tmpAssigned = sourceRow.assigned_suggestion;
    sourceRow.assigned_suggestion = targetRow.assigned_suggestion;
    targetRow.assigned_suggestion = tmpAssigned;

    // swap possible_assignments arrays so the subject options travel with subject identity
    const tmpPossible = sourceRow.possible_assignments;
    sourceRow.possible_assignments = targetRow.possible_assignments;
    targetRow.possible_assignments = tmpPossible;

    // also swap _original_possible_assignments if present
    const tmpOrig = sourceRow._original_possible_assignments;
    sourceRow._original_possible_assignments = targetRow._original_possible_assignments;
    targetRow._original_possible_assignments = tmpOrig;
  }

  // Re-run suggestion refresh so UI updates and conflicts get recalculated
  this.refreshAISuggestions();

  // trigger reactivity
  this.pendingSchedules = [...this.pendingSchedules];
  this.dragData = null;
}

,
onSubjectDropped(event) {
  const { item, from, to } = event;
  const draggedId = item.dataset.id;

  // Find the dragged subject
  const draggedSubject = this.pendingSchedules.find(s => s.id == draggedId);
  if (!draggedSubject) return;

  // Determine target faculty
  const targetFaculty = to.dataset.faculty;
  if (!targetFaculty || targetFaculty === draggedSubject.faculty) return;

  // Find all subjects in target faculty
  const targetSubjects = this.pendingSchedules.filter(s => s.faculty === targetFaculty);

  // Find the index in target faculty where it is dropped
  const toIndex = Array.from(to.children).indexOf(item);

  // Remove from old faculty array
  const fromIndex = this.pendingSchedules.indexOf(draggedSubject);
  if (fromIndex > -1) this.pendingSchedules.splice(fromIndex, 1);

  // Insert into new position
  let insertIndex = toIndex;
  if (insertIndex > targetSubjects.length) insertIndex = targetSubjects.length;

  // Update the faculty
  draggedSubject.faculty = targetFaculty;

  // Insert back into main array at the correct index
  const firstTargetIndex = this.pendingSchedules.findIndex(s => s.faculty === targetFaculty);
  if (firstTargetIndex > -1) {
    this.pendingSchedules.splice(firstTargetIndex + insertIndex, 0, draggedSubject);
  } else {
    this.pendingSchedules.push(draggedSubject);
  }

  // Refresh assignments for the dragged subject
  draggedSubject.possible_assignments = this.getFilteredAssignmentsForFaculty(
    draggedSubject._original_possible_assignments,
    targetFaculty
  );

  this.$forceUpdate();
},


// ✅ Helper to re-filter possible_assignments per faculty (local only)
getFilteredAssignmentsForFaculty(originalAssignments, facultyName) {
  const currentFacultySchedules = this.pendingSchedules.filter(
    s => s.faculty === facultyName
  );

return originalAssignments.filter(a => {
  if (this.checkAssignmentConflict({
    id: subject.id,
    faculty_id: a.faculty_id,
    room_id: a.room_id,
    time_slot_label: a.time_slot || a.time
  })) return false;

    // Room conflict: same room + overlapping time
    const roomConflict = this.pendingSchedules.some(s => {
      if (!s.classroom || !s.time) return false;
      return s.classroom === a.room && this.slotLabelsOverlap(s.time, aSlot);
    });

    return !facultyConflict && !roomConflict;
  });
}
,

// Optional drag handlers (keep if you already use them)
handleDragStart(facultyName, event) {
  this.dragSourceFaculty = facultyName;
},
handleDrop(facultyName, event) {
  // optional: persist changes or validate
},
onMove({ draggedContext, relatedContext }) {
  const draggedItem = draggedContext.element;
  const relatedItem = relatedContext.element;
  if (!draggedItem || !relatedItem) return true;
  return true; // allow move
},

  enableEdit(faculty, row, col) {
  if (!this.editMode) return;
  const key = col.toLowerCase().replace(" ", "_");
  this.editableCell = { faculty, row, col };
  const facultyRows = this.pendingSchedules.filter(s => s.faculty === faculty);
  this.editableValue = facultyRows[row][key];
},
isEditingCell(faculty, row, col) {
  return this.editableCell &&
         this.editableCell.faculty === faculty &&
         this.editableCell.row === row &&
         this.editableCell.col === col;
},

     // 3️⃣ Record a manual edit
  saveEdit(faculty, rowIndex, col) {
    const key = col.toLowerCase().replace(" ", "_");
    const facultyRows = this.pendingSchedules.filter(s => s.faculty === faculty);
    const editedRow = facultyRows[rowIndex];
    if (!editedRow) return;

    // Save previous value
    this.actionHistory.push({
      type: "edit",
      subjectId: editedRow.id,
      prevValue: editedRow[key]
    });

    editedRow[key] = this.editableValue;
    this.editableCell = null;
    this.editableValue = "";
    this.pendingSchedules = [...this.pendingSchedules];
  },
  // 4️⃣ Undo function
  undoLastAction() {
    if (!this.actionHistory.length) return alert("Nothing to undo!");

    const lastAction = this.actionHistory.pop();

    if (lastAction.type === "assign") {
      const target = this.pendingSchedules.find(s => s.id === lastAction.subjectId);
      if (!target) return;
      Object.assign(target, lastAction.prevState);
      this.refreshAISuggestions();

    } else if (lastAction.type === "drag") {
      lastAction.affectedRows.forEach(row => {
        const target = this.pendingSchedules.find(s => s.id === row.id);
        if (target) Object.assign(target, row.prevValue);
      });

    } else if (lastAction.type === "edit") {
      const target = this.pendingSchedules.find(s => s.id === lastAction.subjectId);
      if (target) {
        const key = this.editableCell?.col.toLowerCase().replace(" ", "_") || "";
        if (key) target[key] = lastAction.prevValue;
      }
    }

    this.pendingSchedules = [...this.pendingSchedules];
  },
    parseSlotLabel(label) {
  if (!label || typeof label !== 'string') return null;
  try {
    const s = label.replace(/–/g, '-').trim();
    const m = s.match(/^([A-Za-z]+)\s+(\d{1,2}):(\d{2})\s*[-–]\s*(\d{1,2}):(\d{2})$/);
    if (!m) return null;
    const day = m[1];
    const sh = parseInt(m[2], 10), sm = parseInt(m[3], 10);
    const eh = parseInt(m[4], 10), em = parseInt(m[5], 10);
    return { day, start: sh * 60 + sm, end: eh * 60 + em };
  } catch (e) {
    return null;
  }
},

   assignToFaculty(subject, suggestion) {
      try {
        subject.assigned_faculty = suggestion.faculty_name;
        subject.assigned_room = suggestion.room_name;
        subject.assigned_time = suggestion.time_slot_label;
        subject.assigned_faculty_id = suggestion.faculty_id;
        subject.assigned_room_id = suggestion.room_id;
        subject.assigned_day = suggestion.time_day;
        subject.assigned_start = suggestion.time_start;
        subject.assigned_end = suggestion.time_end;

        // After assigning, remove overlapping options
        this.filterConflictingAssignments(
          suggestion.faculty_id,
          suggestion.room_id,
          suggestion.time_day,
          suggestion.time_start,
          suggestion.time_end
        );

        // ✅ Automatically re-filter all remaining unassigned subjects
        this.refreshAISuggestions();
      } catch (error) {
        console.error("Error assigning to faculty:", error);
      }
    },

    // ✅ Added improved overlap handling like CreatePanel
    filterConflictingAssignments(facultyId, roomId, day, start, end) {
      this.pendingSchedules.forEach((subject) => {
        // Skip already assigned subjects
        if (subject.assigned_faculty_id) return;

        subject.possible_assignments = subject.possible_assignments.filter((option) => {
          const isSameFaculty = option.faculty_id === facultyId;
          const isSameRoom = option.room_id === roomId;
          const isSameDay = option.time_day === day;

          const overlaps =
            isSameDay &&
            ((option.time_start < end && option.time_end > start) || // partial overlap
             (option.time_start >= start && option.time_start < end) || // start within
             (option.time_end > start && option.time_end <= end)); // end within

          // Remove if overlapping by faculty OR by room
          if ((isSameFaculty || isSameRoom) && overlaps) {
            return false;
          }
          return true;
        });
      });
    },
  
    updateFacultyAssignments(selectedFacultyId, assignedSubject) {
      const { time_day, time_start, time_end, room_id } = assignedSubject;

      this.pendingSchedules.forEach(subject => {
        subject.possible_assignments = subject.possible_assignments.filter(pa => {
          if (pa.faculty_id !== selectedFacultyId) return true;

          const timeOverlap =
            pa.time_day === time_day &&
            ((pa.time_start < time_end && pa.time_end > time_start) ||
             (pa.time_end > time_start && pa.time_start < time_end));

          const roomConflict =
            pa.room_id === room_id &&
            pa.time_day === time_day &&
            ((pa.time_start < time_end && pa.time_end > time_start) ||
             (pa.time_end > time_start && pa.time_start < time_end));

          return !timeOverlap && !roomConflict;
        });
      });

      // ✅ Trigger re-filter to auto update all suggestions
      this.refreshAISuggestions();
    },

    autoAssignAll() {
      let unassigned = this.pendingSchedules.filter(s => !s.faculty || s.faculty === 'Unknown');

      while (unassigned.length > 0) {
        let progress = false;

        for (const subj of unassigned) {
          const valid = this.filteredAssignments(subj);
          if (valid.length > 0) {
            const best = valid[0];
            this.assignSuggestion(subj.id, best);
            progress = true;
          }
        }

        unassigned = this.pendingSchedules.filter(s => !s.faculty || s.faculty === 'Unknown');

        if (!progress) break;
      }

      // ✅ Recompute conflicts after batch assigning
      this.refreshAISuggestions();
    },

getPossibleAssignments(subject) {
  let list = subject.possible_assignments || subject.payload?.possible_assignments || [];
  if (!Array.isArray(list)) return [];

  // ✅ Compute match quality
  list = list.map(pa => {
    const flags = this.suggestionFlags(pa, subject);
    let score = 0;

    // Higher score = better match
    if (flags.deptMatch) score += 3;
    if (!flags.willExceed) score += 2;
    if (flags.underload) score += 1;
    if (!flags.conflictsExistingSlot && !flags.conflictsExistingRoom) score += 2;

    pa.matchScore = score;
    pa.flags = flags;
    return pa;
  });

  // ✅ Sort so best matches are first
  list.sort((a, b) => b.matchScore - a.matchScore);

  // ✅ Group to limit 2 per faculty
  const grouped = {};
  for (const pa of list) {
    const name = pa.faculty_name || pa.faculty || 'Unknown';
    if (!grouped[name]) grouped[name] = [];
    if (grouped[name].length < 2) grouped[name].push(pa);
  }

  // ✅ Flatten and return sorted list
  return Object.values(grouped).flat();
},


    // other existing methods unchanged below ↓↓↓
    suggestionFlags(pa, subject) {
      const faculty = this.findFacultyById(pa.faculty_id) || {};
      const current = faculty.current_load || pa.faculty_current_load || 0;
      const max = faculty.max_load || pa.faculty_max_load || 12;
      const willExceed = (current + (subject?.units || 0)) > max;
      const underload = current < (max/2);
      const subjDept = (subject?.course_code||'').toString().substring(0,2).toUpperCase();
      const facDept = (faculty.department||pa.faculty_department||'').toString().substring(0,2).toUpperCase();
      const deptMatch = subjDept && facDept && subjDept === facDept;
      const conflictsExistingSlot = this.checkSlotConflict(pa.faculty_id, pa.time_slot_label||pa.time||'');
      const conflictsExistingRoom = this.checkRoomConflict(pa.room_id, pa.time_slot_label||pa.time||'');
      return { deptMatch, willExceed, underload, conflictsExistingSlot, conflictsExistingRoom };
    },
  checkTimeConflict(pa) {
    return this.pendingSchedules.some(s =>
      s.faculty === pa.faculty_name &&
      s.time &&
      pa.time &&
      this.isTimeOverlap(s.time, pa.time)
    );
  },


checkRoomConflict(roomIdentifier, slotLabel) {
  if (!roomIdentifier || !slotLabel) return false;
  const norm = this.normalizeSlotLabel(slotLabel);
  if (!norm) return false;
  for (const key in this.usedRooms) {
    const parts = key.split("||");
    const rid = parts[0];
    const savedLabel = parts.slice(1).join("||");
    if (String(rid) === String(roomIdentifier) && this.slotLabelsOverlap(savedLabel, norm)) {
      return true;
    }
  }
  return false;
},

  isTimeOverlap(timeA, timeB) {
    const parse = t => {
      const [day, times] = t.split(" ");
      const [start, end] = times.split("-");
      return { day, start, end };
    };
    try {
      const a = parse(timeA);
      const b = parse(timeB);
      if (a.day !== b.day) return false;
      return !(a.end <= b.start || b.end <= a.start);
    } catch {
      return false;
    }
  },
    getBestMatch(subject) {
    if (!this.possible_assignments || !this.possible_assignments.length) return null;

    const valid = this.possible_assignments
      .filter(a => 
        a.subject_code === subject.subject_code &&
        a.faculty_current_load < a.faculty_max_load &&
        !this.hasConflict(a, subject)
      )
      .sort((a, b) => {
        // same-department first, then lower current load
        if (a.faculty_department === subject.department && b.faculty_department !== subject.department) return -1;
        if (b.faculty_department === subject.department && a.faculty_department !== subject.department) return 1;
        return a.faculty_current_load - b.faculty_current_load;
      });

    return valid[0] || null;
  },

  hasConflict(assignment, subject) {
    // simple check: same timeslot or same room conflict
    return this.pendingSchedules.some(s =>
      s.faculty === assignment.faculty_name &&
      s.time_slot_label === assignment.time_slot_label &&
      s.room_name === assignment.room_name
    );
  },
 hasTimeConflict(slotA, slotB) {
  if (!slotA || !slotB) return false;

  const normalize = s =>
    s.replace(/\s+/g, " ")
     .replace(/[–—]/g, "-")
     .trim();

  const parseSlot = slot => {
    slot = normalize(slot);
    const parts = slot.split(" ");
    const day = parts[0];
    const times = parts.slice(1).join(" ");
    const [rawStart, rawEnd] = times.split("-");
    const start = this.toMinutes(rawStart);
    const end = this.toMinutes(rawEnd);
    return { day, start, end };
  };

  const a = parseSlot(slotA);
  const b = parseSlot(slotB);
  if (!a || !b || !a.day || !b.day) return false;
  if (a.day.toLowerCase() !== b.day.toLowerCase()) return false;

  // Overlap check
  return a.start < b.end && b.start < a.end;
},

toMinutes(t) {
  if (!t) return 0;
  // Extract hours and minutes even if given like "6", "06", "6:30"
  const match = t.match(/(\d{1,2})(?::(\d{1,2}))?/);
  if (!match) return 0;
  const h = parseInt(match[1]);
  const m = parseInt(match[2] || "0");
  return h * 60 + m;
},


timeToMinutesFlexible(t) {
  if (!t) return 0;
  t = t.toString().trim();
  // Handle both "6" and "06:00" formats
  const parts = t.split(":");
  const hour = parseInt(parts[0].replace(/\D/g, "")) || 0;
  const minute = parseInt(parts[1]) || 0;
  return hour * 60 + minute;
},


timeToMinutes(timeStr) {
  const [hourStr, minuteStr] = timeStr.split(":");
  const hour = parseInt(hourStr);
  const minute = parseInt(minuteStr) || 0;
  return hour * 60 + minute;
},

normalizeSlotLabel(label) {
  const p = this.parseSlotLabel(label);
  if (!p) return label || "";
  const fmt = (n) => String(Math.floor(n / 60)).padStart(2, "0") + ":" + String(n % 60).padStart(2, "0");
  return `${p.day} ${fmt(p.start)}-${fmt(p.end)}`;
},
intervalsOverlap(aStart, aEnd, bStart, bEnd) {
  return !(aEnd <= bStart || bEnd <= aStart);
},
slotLabelsOverlap(aLabel, bLabel) {
  const a = this.parseSlotLabel(aLabel);
  const b = this.parseSlotLabel(bLabel);
  if (!a || !b || a.day !== b.day) return false;
  return this.intervalsOverlap(a.start, a.end, b.start, b.end);
},
markUsedSlotAndRoom(facultyIdentifier, roomIdentifier, slotLabel) {
  // Use a safe separator that won't appear in names: '||'
  const norm = this.normalizeSlotLabel(slotLabel);
  if (!norm) return;
  if (facultyIdentifier) {
    // stringify so both IDs and names work uniformly
    this.usedSlots[`${String(facultyIdentifier)}||${norm}`] = true;
  }
  if (roomIdentifier) {
    this.usedRooms[`${String(roomIdentifier)}||${norm}`] = true;
  }
},

    suggestionFlags(pa, subject) {
      const faculty = this.findFacultyById(pa.faculty_id) || {};
      const current = faculty.current_load || pa.faculty_current_load || 0;
      const max = faculty.max_load || pa.faculty_max_load || 12;
      const willExceed = (current + (subject?.units || 0)) > max;
      const underload = current < (max/2);
      const subjDept = (subject?.course_code||'').toString().substring(0,2).toUpperCase();
      const facDept = (faculty.department||pa.faculty_department||'').toString().substring(0,2).toUpperCase();
      const deptMatch = subjDept && facDept && subjDept === facDept;
      const conflictsExistingSlot = this.checkSlotConflict(pa.faculty_id, pa.time_slot_label||pa.time||'');
      const conflictsExistingRoom = this.checkRoomConflict(pa.room_id, pa.time_slot_label||pa.time||'');
      return { deptMatch, willExceed, underload, conflictsExistingSlot, conflictsExistingRoom };
    },
    

checkSlotConflict(facultyIdentifier, slotLabel) {
  if (!facultyIdentifier || !slotLabel) return false;
  const norm = this.normalizeSlotLabel(slotLabel);
  if (!norm) return false;
  for (const key in this.usedSlots) {
    const parts = key.split("||");
    const fid = parts[0];
    const savedLabel = parts.slice(1).join("||"); // join back in case separator appears in label
    if (String(fid) === String(facultyIdentifier) && this.slotLabelsOverlap(savedLabel, norm)) {
      return true;
    }
  }
  return false;
},
   checkRoomConflict(roomId, slotLabel) {
  if (!roomId || !slotLabel) return false;
  const norm = this.normalizeSlotLabel(slotLabel);
  for (const key in this.usedRooms) {
    const [rid, savedLabel] = key.split("|");
    if (String(rid) === String(roomId) && this.slotLabelsOverlap(savedLabel, norm)) {
      return true;
    }
  }
  return false;
},
    findFacultyById(id) {
      for (const s of this.pendingSchedules) {
        if (Array.isArray(s.possible_assignments)) {
          const f = s.possible_assignments.find(x => String(x.faculty_id||x.id) === String(id));
          if (f) return f;
        }
      }
      return null;
    },
// --- FIXED ASSIGNMENT FLOW ---
assignSuggestion(subjectId, assignment) {
  if (!this.editMode) return alert("Enable Edit mode to assign subjects.");

  const target = this.pendingSchedules.find(s => s.id === subjectId);
  if (!target || (target.faculty && target.faculty !== 'Unknown')) return;

  const conflict = this.checkAssignmentConflict({
    id: subjectId,
    faculty_id: assignment.faculty_id,
    room_id: assignment.room_id,
    time_slot_label: assignment.time_slot_label || assignment.time,
  });

  if (conflict) {
    alert("❌ Conflict detected: This faculty or room already has an overlapping schedule!");
    return;
  }

  this.actionHistory.push({
    type: "assign",
    subjectId,
    prevState: { faculty: target.faculty, classroom: target.classroom, time: target.time }
  });

  target.faculty = assignment.faculty_name || assignment.faculty;
  target.classroom = assignment.room_name || assignment.classroom;
  target.time = assignment.time_slot_label || assignment.time;
  target.assigned_suggestion = assignment;

  const units = target.units || 3;
  assignment.faculty_current_load = (assignment.faculty_current_load || 0) + units;

  this.markUsedSlotAndRoom(assignment.faculty_id, assignment.room_id, assignment.time_slot_label || assignment.time);
  this.refreshAISuggestions();
},

// --- FIXED RE-EVALUATION LOGIC ---
refreshAISuggestions() {
  const facultyLimit = 2; // max 2 suggestions per faculty

  for (const s of this.pendingSchedules) {
    // skip assigned subjects
    if (s.faculty && s.faculty !== 'Unknown') continue;

    const allOpts = s._original_possible_assignments || s.possible_assignments || [];
    const byFaculty = {};

    allOpts.forEach(opt => {
      const slot = opt.time_slot_label || opt.time;

      // ✅ Filter out any option that overlaps with existing assignments (AI or manual)
      const conflict = this.pendingSchedules.some(ps => {
        if (ps.id === s.id) return false;
        if (!ps.faculty || !ps.time || !ps.classroom) return false;

        const psSlot = this.normalizeSlotLabel(ps.time);
        const optSlot = this.normalizeSlotLabel(slot);
        const sameDayOverlap = this.slotLabelsOverlap(psSlot, optSlot);

        return (
          sameDayOverlap &&
          (
            ps.faculty_id === opt.faculty_id ||           // same faculty
            ps.faculty === opt.faculty_name ||            // or same faculty name
            ps.classroom === opt.room_name ||             // or same room
            ps.room_id === opt.room_id
          )
        );
      });

      if (conflict) return; // ❌ skip conflicting assignment

      // continue normal scoring
      const fid = opt.faculty_id || `name-${opt.faculty_name}`;
      if (!byFaculty[fid]) byFaculty[fid] = [];

      const willExceed = (opt.faculty_current_load + (s.units || 0)) > (opt.faculty_max_load || 20);
      const subjDept = (s.subject_code || "").substring(0, 2).toUpperCase();
      const deptMatch = subjDept === (opt.faculty_department || "").substring(0, 2).toUpperCase();

      byFaculty[fid].push({ opt, willExceed, deptMatch, score: opt.score || 0 });
    });

    const selectedOpts = [];
    for (const fid in byFaculty) {
      const arr = byFaculty[fid];
      arr.sort((a, b) => {
        if (b.deptMatch !== a.deptMatch) return b.deptMatch - a.deptMatch;
        if (a.willExceed !== b.willExceed) return a.willExceed - b.willExceed;
        return (b.score || 0) - (a.score || 0);
      });

      const localUsed = [];
      let count = 0;
      for (const entry of arr) {
        if (count >= facultyLimit) break;
        const candidateSlot = entry.opt.time_slot_label || entry.opt.time;
        if (localUsed.some(used => this.slotLabelsOverlap(used, candidateSlot))) continue;
        localUsed.push(candidateSlot);
        selectedOpts.push(entry.opt);
        count++;
      }
    }

    selectedOpts.sort((a, b) => (b.score || 0) - (a.score || 0));
    s.possible_assignments = selectedOpts;
  }

  this.$forceUpdate();
},

    getFacultyTotal(facultySchedules) {
      return facultySchedules.reduce((sum,s)=>sum+(Number(s.units||0)),0);
    },
    formatDate(dateStr){ const d=new Date(dateStr); return d.toLocaleDateString("en-US",{month:"short",day:"numeric",year:"numeric"}); },
    exitSchedule(){ this.pendingSchedules=[]; this.selectedBatch=null; },
    closeModal(){ this.showModal=false; this.pendingSchedules=[]; this.selectedRows=[]; this.editMode=false; this.deleteMode=false; },
    toggleEditMode(){ this.editMode=!this.editMode; this.editableCell=null; if(this.editMode) alert("✅ You can drag or double-click to edit cells."); },
    saveEdit(faculty,row,col){ const key=col.toLowerCase().replace(" ","_"); const facultyRows=this.pendingSchedules.filter(s=>s.faculty===faculty); const editedRow=facultyRows[row]; if(!editedRow) return; editedRow[key]=this.editableValue; this.editableCell=null; this.editableValue=""; this.pendingSchedules=[...this.pendingSchedules]; },
    async loadPendingSchedules(){ this.show(); try{ const res=await fetch("/api/pending-schedules"); const data=await res.json(); this.batchList=data.pending||data.batches||[]; } catch(err){ console.error(err); alert("Failed to load pending schedules."); } finally{ this.hide(); } },
async openBatch(batchId) {
  this.selectedBatch = batchId;
  this.show();

  try {
    const res = await fetch(`/api/pending-schedules/${batchId}`);
    const data = await res.json();

    let grouped = [];
    let unassigned = [];

    if (data.grouped || data.unassigned) {
      grouped = Object.values(data.grouped || {}).flat();
      unassigned = data.unassigned || [];
    }

    // --- Build full pendingSchedules list ---
    this.pendingSchedules = [
      ...grouped.map(s => {
        const baseAssignments = s.possible_assignments?.length
          ? s.possible_assignments
          : s.payload?.possible_assignments || [];

        return {
          id: s._localId || s.id || `${s.courseCode || s.subject}-${Math.random()}`,
          subject_code: s.courseCode || s.course_code || "",
          faculty: s.faculty || s.faculty_name || "Unknown",
          assigned_faculty_id: s.assigned_faculty_id || s.faculty_id || null,
          assigned_room_id: s.assigned_room_id || s.room_id || null,
          subject: s.subject || s.subject_title || "Untitled",
          time: s.time || s.time_slot || s.time_slot_label || "",
          classroom: s.classroom || s.room_name || s.room || "",
          course_section: s.courseSection || s.course_section || "",
          units: Number(s.units || 0),
          payload: s.payload || null,
          possible_assignments: [...baseAssignments],
          _original_possible_assignments: [...baseAssignments],
        };
      }),
      ...unassigned.map(u => {
        const derivedOptions = u.possible_assignments || u.payload?.possible_assignments || [];

        return {
          id: u._localId || u.id || `${u.subject_code || u.subject}-${Math.random()}`,
          subject_code: u.course_code || u.CourseCode || "",
          faculty: u.faculty || "Unknown",
          assigned_faculty_id: u.assigned_faculty_id || u.faculty_id || null,
          assigned_room_id: u.assigned_room_id || u.room_id || null,
          subject: u.subject_display || u.subject_title || u.subject || "Untitled",
          time: u.time || u.time_slot_label || "",
          classroom: u.classroom || u.room_name || "",
          course_section: u.course_section || u.courseSection || "",
          units: Number(u.units || 0),
          payload: u.payload || null,
          possible_assignments: [...derivedOptions],
          _original_possible_assignments: [...derivedOptions],
        };
      }),
    ];

    // --- Reset and rebuild used slots/rooms from existing assignments ---
    this.usedSlots = {};
    this.usedRooms = {};

    const findIdsForAssigned = (s) => {
      // prefer explicit IDs
      if (s.assigned_faculty_id || s.assigned_room_id) {
        return {
          facultyIdentifier: s.assigned_faculty_id || s.faculty || null,
          roomIdentifier: s.assigned_room_id || s.classroom || null,
          slot: s.time || s.time_slot_label || "",
        };
      }

      // else match against original possible assignments
      const arr = s._original_possible_assignments || s.possible_assignments || [];
      const match = arr.find(opt => {
        const slot = opt.time_slot_label || opt.time || "";
        const roomName = opt.room_name || opt.room || opt.room_id || "";
        const facultyName = opt.faculty_name || opt.faculty || "";
        return (
          (s.time && this.slotLabelsOverlap(s.time, slot)) &&
          (s.classroom && String(s.classroom).toLowerCase() === String(roomName).toLowerCase()) &&
          (s.faculty && String(s.faculty).toLowerCase() === String(facultyName).toLowerCase())
        );
      });

      if (match) {
        return {
          facultyIdentifier: match.faculty_id || match.faculty_name || s.faculty || null,
          roomIdentifier: match.room_id || match.room_name || s.classroom || null,
          slot: match.time_slot_label || match.time || s.time || "",
        };
      }

      // fallback to names
      return {
        facultyIdentifier: s.faculty || null,
        roomIdentifier: s.classroom || null,
        slot: s.time || "",
      };
    };

    for (const s of this.pendingSchedules) {
      if (s.faculty && s.faculty !== "Unknown" && (s.time || s.classroom)) {
        const { facultyIdentifier, roomIdentifier, slot } = findIdsForAssigned(s);
        if (slot) this.markUsedSlotAndRoom(facultyIdentifier, roomIdentifier, slot);
      }
    }

    // --- Finalize modal and refresh AI suggestions ---
    this.showModal = true;
    this.refreshAISuggestions();
    this.$forceUpdate();

  } catch (err) {
    console.error(err);
    alert("Failed to load batch details.");
  } finally {
    this.hide();
  }
},


    async saveChanges(){ if(!this.selectedBatch) return alert("No batch selected."); if(!this.pendingSchedules.length) return alert("No schedules to save."); this.show(); try{ const schedulesToSave = this.pendingSchedules.map(s => ({
    id: s.id,
    subject_code: s.subject_code,
    subject: s.subject,
    time: s.time,
    classroom: s.classroom,
    course_section: s.course_section,
    units: Number(s.units),
    faculty: s.faculty,
    payload: s.payload || {} // ensure it's an object
}));
 const res=await fetch(`/api/pending-schedules/${this.selectedBatch}/update`,{method:"PUT",headers:{"Content-Type":"application/json"},body:JSON.stringify({schedules:schedulesToSave})}); const data=await res.json(); if(data.success){ alert("✅ Changes saved successfully!"); this.loadPendingSchedules(); this.showModal=false; this.editMode=false; this.editableCell=null; this.editableValue=""; }else alert("❌ Failed to save changes: "+data.message); }catch(err){console.error(err); alert("Error saving schedule changes.");}finally{this.hide();}},
  }
};
</script>

<style scoped>
.pending-panel {
  padding: 20px;
}
.create-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
.create-table th,
.create-table td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: left;
}
.create-table th {
  background-color: #f4f4f4;
  font-weight: bold;
}
.text-center {
  text-align: center;
}
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.modal-content {
  background: #fff;
  width: 90%;
  max-width: 1200px;
  border-radius: 10px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
  max-height: 90vh;
  overflow-y: auto;
  animation: fadeIn 0.2s ease-in-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}
.header-buttons {
  display: flex;
  gap: 10px;
}
.edit-btn,
.finalize-btn,
.delete-btn,
.close-btn,
.view-btn,
.exit-btn {
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: white;
  font-size: 14px;
  transition: 0.2s;
}
.edit-btn {
  background: #3498db;
}
.finalize-btn {
  background: #2ecc71;
}
.delete-btn {
  background: #e74c3c;
}
.view-btn {
  background: #8e44ad;
}
.exit-btn {
  background: #7f8c8d;
}
.close-btn {
  background: #e74c3c;
  margin-top: 20px;
}
.save-btn {
  background: #f1c40f;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  cursor: pointer;
}
.save-btn:hover {
  background: #d4ac0d;
}

.faculty-section {
  margin-bottom: 30px;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  background: #fafafa;
}
.faculty-section h4 {
  margin-bottom: 10px;
  color: #2c3e50;
}
.total-units {
  margin-top: 10px;
  font-weight: bold;
  color: #34495e;
}
.filter-select {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
.draggable-cell.editable {
  background: #fcfcfc;
  cursor: grab;
  transition: background 0.2s;
}
.draggable-cell.editable:hover {
  background: #eef5ff;
}
.edit-input {
  width: 100%;
  padding: 5px;
  border: 1px solid #bbb;
  border-radius: 4px;
  font-size: 14px;
  outline: none;
}
.edit-input:focus {
  border-color: #3498db;
  background: #f8fcff;
}
.faculty-filter {
  margin-top: 10px;
  margin-bottom: 15px;
}

.filter-select {
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
  width: 250px;
  cursor: pointer;
}
.filter-select:focus {
  border-color: #3498db;
  outline: none;
}
.summary-overview {
  margin-bottom: 20px;
}
.summary-cards {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}
.summary-card {
  flex: 1 1 150px;
  padding: 15px;
  border-radius: 8px;
  color: white;
  text-align: center;
}

.suggestions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  padding: 8px 0;
}
.suggestion {
  display: inline-block;
  border: 1px solid #e6e6e6;
  padding: 6px 8px;
  border-radius: 6px;
  background: #fafafa;
  min-width: 160px;
}
.suggestions-inline { display:grid; gap:8px; margin-top:6px; align-items:start }

/* Stacked full-width suggestion rows */
.suggestion-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 10px;
  border: 1px solid #eee;
  background: #fff;
  border-radius: 6px;
}
.suggestion-row + .suggestion-row { margin-top: 6px }
.s-left { flex: 1 1 auto; padding-right: 12px }
.s-right { display: flex; flex-direction: column; gap:6px; align-items: flex-end }
.s-right .s-actions { margin-top: 4px }
.cell-content { display:flex; flex-direction:column }
.cell-main { font-weight:500 }
.assign-btn.small { padding:4px 6px; font-size:12px }
.suggestion-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Contain suggestion cards inside a scrollable area to avoid overflowing the cell */
.contained-suggestions {
  max-height: 220px;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 6px 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}
.s-title {
  font-weight: 600;
  color: #2c3e50;
}
.s-meta {
  font-size: 12px;
  color: #666;
}
.assign-btn {
  background: #2ecc71;
  border: none;
  color: white;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}
.assign-btn:hover { filter: brightness(0.95); }
.badges { margin-top: 8px; display:flex; gap:6px; flex-wrap:wrap }
.badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; color: white }
.badge.dept { background: #3498db }
.badge.overload { background: #e67e22 }
.badge.conflict { background: #e74c3c }
.badge.room { background: #9b59b6 }
.summary-card.assigned { background: rgb(150, 201, 175); }
.summary-card.unassigned { background: rgb(226, 194, 133); }
.summary-card.conflicts { background: rgb(214, 118, 118); color: #333; }
.summary-card h5 {
  margin-bottom: 5px;
  font-size: 14px;
}
.summary-card p {
  font-size: 18px;
  font-weight: bold;
  margin: 0;
}

/* Unassigned quick assign styles */
.unassigned-top { margin: 12px 0; padding: 10px; background: #fff; border: 1px solid #eee; border-radius: 8px }
.unassigned-list { display:flex; flex-direction:column; gap:8px }
.unassigned-quick { display:flex; justify-content:space-between; align-items:center; padding:8px; border-bottom:1px dashed #f0f0f0 }
.ua-left { font-weight:600 }
.ua-suggestions-inline { display:grid; gap:8px; grid-template-columns: 1fr; align-items:start }

/* Modal overlay centered and covering the page */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.modal-content {
  background: white;
  border-radius: 8px;
  padding: 16px;
  max-width: 95%;
  max-height: 90%;
  overflow: auto;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

</style>

