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
      <div class="header-buttons">
        <div class="action-buttons">
          <button class="edit-btn" @click="toggleEditMode">
            {{ editMode ? 'Finish Editing' : 'Edit' }}
          </button>
          <button v-if="editMode" class="save-btn" @click="saveChanges">
            💾 Save Changes
          </button>
          <button class="finalize-btn" @click="finalizeBatch">Finalize</button>
          <button class="delete-btn" @click="deleteBatch(selectedBatch)">Delete</button>
          <button class="exit-btn" @click="closeModal">Exit</button>
        </div>
      </div>
    </div>

    <!-- ====== Summary Overview ====== -->
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


    <!-- ====== Faculty Groups ====== -->
    <div
      v-for="(facultySchedules, facultyName) in groupedByFaculty"
      :key="facultyName"
      class="faculty-section"
    >
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

              <td
                v-for="(col, cIndex) in tableColumns"
                :key="cIndex"
                draggable="true"
                @dragstart="startDrag($event, facultyName, index, col)"
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
                <span v-else>
                  {{ element[col.toLowerCase().replace(' ', '_')] }}
                </span>
              </td>
            </tr>
            <!-- Suggestion cards for unassigned rows (faculty Unknown) -->
            <tr v-if="element.faculty === 'Unknown' && (element.possible_assignments && element.possible_assignments.length)">
              <td v-if="editMode"></td>
              <td v-if="deleteMode"></td>
              <td :colspan="tableColumns.length">
                <div class="suggestions">
                  <div
                    v-for="(sug, si) in element.possible_assignments.slice(0,3)"
                    :key="si"
                    class="suggestion-card"
                  >
                    <div class="suggestion-main">
                      <div class="suggestion-left">
                        <div class="s-title">{{ sug.faculty_name || sug.faculty || sug.faculty_display || sug.faculty_name_display || 'Faculty' }}</div>
                        <div class="s-meta">{{ sug.time || sug.time_slot_label || sug.slot || '' }} • {{ sug.room_name || sug.classroom || sug.room || '' }} • {{ sug.units ? sug.units + 'u' : '' }}</div>
                      </div>
                      <div class="suggestion-right">
                        <button class="assign-btn" @click="assignSuggestion(element.id, sug)">Assign</button>
                      </div>
                    </div>
                    <div class="badges">
                      <span v-if="sug.deptMatch" class="badge dept">Dept</span>
                      <span v-if="sug.willExceed" class="badge overload">Overload</span>
                      <span v-if="sug.conflictsExistingSlot" class="badge conflict">Time</span>
                      <span v-if="sug.conflictsExistingRoom" class="badge room">Room</span>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </draggable>
      </table>

      <div class="total-units">
        Total Load Units:
        {{
          facultySchedules.reduce(
            (sum, s) =>
              sum + (Number(s.units || s.cells?.find(c => c.key === 'units')?.value) || 0),
            0
          )
        }}
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
      facultyFilter: "", // 🆕 For filtering by faculty
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
    facultyCounts() {
  const counts = {};
  if (!this.pendingSchedules?.length) return counts;
  for (const s of this.pendingSchedules) {
    if (s.faculty) {
      counts[s.faculty] = (counts[s.faculty] || 0) + 1;
    }
  }
  return counts;
},
 filteredBatches() {
    const q = this.searchQuery.toLowerCase();
    return !q
      ? this.batchList
      : this.batchList.filter(
          (b) =>
            b.academicYear?.toLowerCase().includes(q) ||
            b.semester?.toLowerCase().includes(q) ||
            b.batch_id?.toLowerCase().includes(q)
        );
  },

  // ✅ Faculty dropdown list
  uniqueFaculties() {
    if (!this.pendingSchedules?.length) return [];
    const names = [...new Set(this.pendingSchedules.map((s) => s.faculty))];
    return names.sort();
  },

  // ✅ Grouped & filtered schedules by faculty
  groupedByFaculty() {
  if (!this.pendingSchedules?.length) return {};

  const grouped = this.pendingSchedules.reduce((groups, s) => {
    const faculty = s.faculty || "Unknown";
    if (!groups[faculty]) groups[faculty] = [];
    groups[faculty].push({ ...s });
    return groups;
  }, {});

  if (this.facultyFilter) {
    return Object.fromEntries(
      Object.entries(grouped).filter(([faculty]) =>
        faculty.toLowerCase().includes(this.facultyFilter.toLowerCase())
      )
    );
  }

  return grouped;
}, totalAssigned() {
    return this.pendingSchedules.filter(s => s.faculty && s.faculty !== "Unknown").length;
  },
  totalUnassigned() {
    return this.pendingSchedules.filter(s => !s.faculty || s.faculty === "Unknown").length;
  },
  totalConflicts() {
    // simple conflict check: same faculty or same classroom at the same time
    let conflicts = 0;
    const seen = {};
    for (const s of this.pendingSchedules) {
      const keyFaculty = `${s.faculty}-${s.time}`;
      const keyClass = `${s.classroom}-${s.time}`;
      if (seen[keyFaculty]) conflicts++;
      if (seen[keyClass]) conflicts++;
      seen[keyFaculty] = true;
      seen[keyClass] = true;
    }
    return conflicts;
  },
  },

  methods: {
    async loadPendingSchedules() {
      this.show();
      try {
        const res = await fetch("/api/pending-schedules");
        const data = await res.json();
        this.batchList = data.pending || data.batches || [];
      } catch (err) {
        console.error(err);
        alert("Failed to load pending schedules.");
      } finally {
        this.hide();
      }
    },

    // === DRAG/DROP SWAP ===
    startDrag(event, facultyName, rowIndex, column) {
      this.dragData = { facultyName, rowIndex, column };
    },
   onDrop(event, targetFaculty, targetRow, targetColumn) {
    if (!this.editMode || !this.dragData) return;

    // Only allow swapping in the same column
    if (this.dragData.column !== targetColumn) return;

    const { facultyName, rowIndex, column } = this.dragData;

    // Get the actual rows from pendingSchedules
    const sourceRows = this.pendingSchedules.filter(s => s.faculty === facultyName);
    const targetRows = this.pendingSchedules.filter(s => s.faculty === targetFaculty);

    const source = sourceRows[rowIndex];
    const target = targetRows[targetRow];

    if (!source || !target) return;

    const key = column.toLowerCase().replace(" ", "_");

    // Swap values
    const temp = source[key];
    source[key] = target[key];
    target[key] = temp;

    this.pendingSchedules = [...this.pendingSchedules]; // force reactivity
    this.dragData = null;
  }
,

enableEdit(faculty, row, column) {
  if (!this.editMode) return; // 🆕 No editing if not in edit mode

  const key = column.toLowerCase().replace(" ", "_");

  if (this.isEditingCell(faculty, row, column)) {
    this.saveEdit(faculty, row, column);
    return;
  }

  this.editableCell = { faculty, row, column };
  this.editableValue = this.groupedByFaculty[faculty][row][key];
},


isEditingCell(faculty, row, column) {
  return (
    this.editableCell &&
    this.editableCell.faculty === faculty &&
    this.editableCell.row === row &&
    this.editableCell.column === column
  );
},

saveEdit(faculty, row, column) {
  const key = column.toLowerCase().replace(" ", "_");

  if (!this.editableValue || this.editableValue.trim() === "") {
    // Restore old value if input is empty
    this.editableCell = null;
    this.editableValue = "";
    return;
  }

  // Find the actual schedule in pendingSchedules
  const facultyRows = this.pendingSchedules.filter(s => s.faculty === faculty);
  const editedRow = facultyRows[row];
  if (!editedRow) return;

  // Update the value directly in pendingSchedules
  editedRow[key] = this.editableValue;

  this.editableCell = null;
  this.editableValue = "";
  this.pendingSchedules = [...this.pendingSchedules]; // force reactivity
}

,

async saveChanges() {
  if (!this.selectedBatch) return alert("No batch selected.");
  if (!this.pendingSchedules.length) return alert("No schedules to save.");

  this.show();
  try {
    // Prepare payload
    const schedulesToSave = this.pendingSchedules.map(s => ({
      id: s.id,
      subject_code: s.subject_code,
      subject: s.subject,
      time: s.time,
      classroom: s.classroom,
      course_section: s.course_section,
      units: Number(s.units),
      faculty: s.faculty
    }));

    const res = await fetch(`/api/pending-schedules/${this.selectedBatch}/update`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ schedules: schedulesToSave }),
    });

    const data = await res.json();

    if (data.success) {
      alert("✅ Changes saved successfully!");
      this.loadPendingSchedules();
      this.showModal = false; // optionally close modal
      this.editMode = false;
      this.editableCell = null;
      this.editableValue = "";
    } else {
      alert("❌ Failed to save changes: " + data.message);
    }
  } catch (err) {
    console.error(err);
    alert("Error saving schedule changes.");
  } finally {
    this.hide();
  }
}
,

    // === OTHER CONTROLS ===
 async openBatch(batchId) {
  this.selectedBatch = batchId;
  this.show();
  try {
    const res = await fetch(`/api/pending-schedules/${batchId}`);
    const data = await res.json();

    // Server may return an object with `grouped` and `unassigned` arrays if saved from CreatePanel
    if (data.grouped || data.unassigned) {
      let groupedRaw = data.grouped || [];
      // grouped may be an array of rows or an object keyed by faculty -> rows
      let grouped = [];
      if (Array.isArray(groupedRaw)) grouped = groupedRaw;
      else if (groupedRaw && typeof groupedRaw === 'object') {
        // flatten values (each value may be an array of rows)
        Object.values(groupedRaw).forEach(v => {
          if (Array.isArray(v)) grouped.push(...v);
          else if (v) grouped.push(v);
        });
      }
      // grouped is now an array of rows with faculty attached
      this.pendingSchedules = grouped.map((s) => ({
        id: s._localId || s.id || s.subject_id || `${s.courseCode || s.subject}-${Math.random()}`,
        subject_code: s.courseCode || s.course_code || "",
        faculty: s.faculty || s.faculty_name || "Unknown",
        subject: s.subject || s.subject_title || "Untitled",
        time: s.time || s.time_slot || "",
        classroom: s.classroom || s.room_name || s.room || "",
        course_section: s.courseSection || s.course_section || "",
        units: Number(s.units || 0),
      }));

      // Also keep unassigned (assignable later) by appending them with faculty 'Unknown' and keeping original possible_assignments
      const unassigned = data.unassigned || [];
      unassigned.forEach(u => {
        this.pendingSchedules.push({
          id: u._localId || u.id || `${u.subject_code || u.subject}-${Math.random()}`,
          subject_code: u.course_code || u.courseCode || u.subject_code || "",
          faculty: u.faculty || "Unknown",
          subject: u.subject_display || u.subject_title || u.subject || "Untitled",
          time: u.time || u.time_slot_label || "",
          classroom: u.classroom || u.room_name || "",
          course_section: u.course_section || u.courseSection || "",
          units: Number(u.units || 0),
          // keep possible_assignments so UI can allow assigning
          possible_assignments: u.possible_assignments || u.possible_assignments_original || []
        });
      });

      this.showModal = true;
    } else {
      // Backwards-compatible: older API responses
      let schedules = [];
      if (data.pending && !Array.isArray(data.pending)) {
        for (const group of Object.values(data.pending)) schedules.push(...group);
      } else {
        schedules = data.pending || data.schedules || [];
      }

      this.pendingSchedules = schedules.map((s) => ({
        id: s._localId || s.id,
        subject_code: s.courseCode || s.course_code || "",
        faculty: s.faculty || "Unknown",
        subject: s.subject || "Untitled",
        time: s.time || "",
        classroom: s.classroom || "",
        course_section: s.courseSection || s.course_section || "",
        units: Number(s.units || 0),
      }));

      this.showModal = true;
    }
  } catch (err) {
    console.error(err);
    alert("Failed to load batch details.");
  } finally {
    this.hide();
  }
}
,

    toggleEditMode() {
      this.editMode = !this.editMode;
      this.editableCell = null;
      if (this.editMode) alert("✅ You can drag or double-click to edit cells.");
    },
    toggleDeleteMode() {
      this.deleteMode = !this.deleteMode;
      this.selectedRows = [];
    },
    toggleAll(event, list) {
      if (event.target.checked)
        this.selectedRows = [...new Set([...this.selectedRows, ...list.map((s) => s.id)])];
      else
        this.selectedRows = this.selectedRows.filter(
          (id) => !list.map((s) => s.id).includes(id)
        );
    },
    async deleteSelectedRows() {
      if (!this.selectedRows.length) return alert("No rows selected.");
      if (!confirm("Delete selected schedules?")) return;
      this.show();
      try {
        const res = await fetch(`/api/pending-schedules/delete-multiple`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ids: this.selectedRows }),
        });
        const data = await res.json();
        if (data.success) {
          alert("🗑 Selected schedules deleted.");
          this.pendingSchedules = this.pendingSchedules.filter(
            (s) => !this.selectedRows.includes(s.id)
          );
          this.selectedRows = [];
          this.deleteMode = false;
        } else alert("❌ Failed to delete selected schedules.");
      } catch (err) {
        console.error(err);
        alert("Error deleting selected schedules.");
      } finally {
        this.hide();
      }
    },
    async finalizeBatch() {
      if (!confirm("Finalize this batch?")) return;
      this.show();
      try {
        const res = await fetch(`/api/pending-schedules/${this.selectedBatch}/finalize`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
        });
        const data = await res.json();
        if (data.success) {
          alert("✅ Batch finalized successfully!");
          this.showModal = false;
          this.loadPendingSchedules();
        } else alert("❌ Failed to finalize batch: " + data.message);
      } catch (err) {
        console.error(err);
        alert("Error finalizing batch.");
      } finally {
        this.hide();
      }
    },
    async deleteBatch(batchId) {
      if (!confirm("Are you sure you want to delete this batch?")) return;
      this.show();
      try {
        const res = await fetch(`/api/pending-schedules/${batchId}`, { method: "DELETE" });
        const data = await res.json();
        if (data.success) {
          alert("🗑 Batch deleted successfully.");
          this.showModal = false;
          this.loadPendingSchedules();
        } else alert("❌ Failed to delete batch.");
      } catch (err) {
        console.error(err);
        alert("Error deleting batch.");
      } finally {
        this.hide();
      }
    },
    assignSuggestion(rowId, suggestion) {
      // Find the pendingSchedules entry by id and apply suggestion fields
      const idx = this.pendingSchedules.findIndex((r) => r.id === rowId);
      if (idx === -1) return;

      const row = this.pendingSchedules[idx];

      // Map common fields from suggestion into the pending row
      row.faculty = suggestion.faculty || suggestion.faculty_name || suggestion.faculty_display || suggestion.faculty_name_display || row.faculty;
      row.time = suggestion.time || suggestion.time_slot_label || suggestion.slot || row.time;
      row.classroom = suggestion.room_name || suggestion.classroom || suggestion.room || row.classroom;
      row.units = Number(suggestion.units || row.units || 0);

      // Remove possible_assignments now that we've assigned it
      delete row.possible_assignments;

      // Force reactivity
      this.pendingSchedules = [...this.pendingSchedules];
      alert(`Assigned ${row.subject || row.subject_code} to ${row.faculty}`);
    },
    closeModal() {
      this.showModal = false;
      this.pendingSchedules = [];
      this.selectedRows = [];
      this.editMode = false;
      this.deleteMode = false;
    },
    formatDate(dateStr) {
      const d = new Date(dateStr);
      return d.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      });
    },
    exitSchedule() {
      this.pendingSchedules = [];
      this.selectedBatch = null;
    },
  },
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
  gap: 10px;
  flex-wrap: wrap;
  padding: 8px 0;
}
.suggestion-card {
  background: #fff;
  border: 1px solid #e1e4e8;
  padding: 8px 12px;
  border-radius: 8px;
  min-width: 220px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.suggestion-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
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


</style>
