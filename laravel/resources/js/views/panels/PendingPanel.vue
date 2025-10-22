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
        <div class="unassigned-top" v-if="pendingSchedules.length && pendingSchedules.some(s => !s.faculty || s.faculty === 'Unknown')">
          <h4>Unassigned Subjects (Quick Assign)</h4>
          <div class="unassigned-list">
            <div v-for="u in pendingSchedules.filter(s => !s.faculty || s.faculty === 'Unknown')" :key="u.id" class="unassigned-quick">
              <div class="ua-left">{{ u.subject }} <small>({{ u.course_section }})</small></div>
              <div class="ua-right">
                <div class="ua-suggestions-inline">
                  <template v-if="(u.possible_assignments || u.payload?.possible_assignments || []).length">
                    <div v-for="(pa, i) in (u.possible_assignments || u.payload?.possible_assignments || []).slice(0,2)" :key="i" class="suggestion-row">
                      <div class="s-left">
                        <div class="s-title">{{ pa.faculty_name || pa.faculty || 'Faculty' }}</div>
                        <div class="s-meta">{{ pa.time_slot_label || pa.time || '' }} • {{ pa.room_name || pa.classroom || '' }}</div>
                      </div>
                      <div class="s-right">
                        <div class="badges">
                          <span v-if="suggestionFlags(pa, u).deptMatch" class="badge dept">Dept</span>
                          <span v-if="suggestionFlags(pa, u).willExceed" class="badge overload">Overload</span>
                          <span v-if="suggestionFlags(pa, u).conflictsExistingSlot" class="badge conflict">Time</span>
                          <span v-if="suggestionFlags(pa, u).conflictsExistingRoom" class="badge room">Room</span>
                          <span v-if="suggestionFlags(pa, u).underload" class="badge">Underload</span>
                          <span v-if="u.assigned_suggestion && (u.assigned_suggestion.faculty_id == (pa.faculty_id || pa.id))" class="badge assigned">Assigned</span>
                        </div>
                        <div class="s-actions">
                          <button class="assign-btn small" @click.stop="assignSuggestion(u.id, pa)" :disabled="suggestionFlags(pa, u).conflictsExistingSlot || suggestionFlags(pa, u).conflictsExistingRoom || (u.assigned_suggestion && (u.assigned_suggestion.faculty_id == (pa.faculty_id || pa.id)))">
                            Assign
                          </button>
                        </div>
                      </div>
                    </div>
                  </template>
                  <span v-else>No suggestions</span>
                </div>
              </div>
            </div>
          </div>
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
            <tbody>
              <tr v-for="(element, index) in facultySchedules" :key="element.id">
                <td v-if="editMode">☰</td>
                <td v-if="deleteMode">
                  <input type="checkbox" v-model="selectedRows" :value="element.id" />
                </td>
                <td v-for="(col, cIndex) in tableColumns" :key="cIndex" class="draggable-cell" :class="{ editable: editMode }" @dblclick="enableEdit(facultyName, index, col)">
                  <input v-if="isEditingCell(facultyName, index, col)" v-model="editableValue" @blur="saveEdit(facultyName, index, col)" @keyup.enter="saveEdit(facultyName, index, col)" class="edit-input" autofocus />
                  <div v-else>
                    <div class="cell-content">
                      <div class="cell-main">{{ element[col.toLowerCase().replace(' ', '_')] }}</div>
                      <div v-if="col === 'Subject' && element.faculty === 'Unknown' && (element.possible_assignments && element.possible_assignments.length)" class="suggestions-inline">
                        <div v-for="(sug, si) in element.possible_assignments.slice(0,2)" :key="si" class="suggestion-row">
                          <div class="s-left">
                            <div class="s-title">{{ sug.faculty_name || sug.faculty || 'Faculty' }}</div>
                            <div class="s-meta">{{ sug.time || sug.time_slot_label || '' }} • {{ sug.room_name || sug.classroom || '' }}</div>
                          </div>
                          <div class="s-right">
                            <div class="badges">
                              <span v-if="element.assigned_suggestion && (element.assigned_suggestion.faculty_id == (sug.faculty_id || sug.id))" class="badge assigned">Assigned</span>
                            </div>
                            <div class="s-actions">
                              <button class="assign-btn small" @click.stop="assignSuggestion(element.id, sug)" :disabled="suggestionFlags(sug, element).conflictsExistingSlot || suggestionFlags(sug, element).conflictsExistingRoom || (element.assigned_suggestion && (element.assigned_suggestion.faculty_id == (sug.faculty_id || sug.id)))">
                                Assign
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
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
      const seen = {};
      for (const s of this.pendingSchedules) {
        const keyFac = `${s.faculty}-${s.time}`;
        const keyRoom = `${s.classroom}-${s.time}`;
        if (seen[keyFac]) conflicts++;
        if (seen[keyRoom]) conflicts++;
        seen[keyFac] = true;
        seen[keyRoom] = true;
      }
      return conflicts;
    }
  },
  methods: {
    normalizeSlotLabel(label) {
      if (!label) return '';
      const m = label.match(/^([A-Za-z]+)\s+(\d{1,2}):(\d{2})[-–](\d{1,2}):(\d{2})$/);
      if (!m) return label;
      const pad = n => String(Math.floor(n/60)).padStart(2,'0')+':'+String(n%60).padStart(2,'0');
      return `${m[1]} ${pad(parseInt(m[2])*60+parseInt(m[3]))}-${pad(parseInt(m[4])*60+parseInt(m[5]))}`;
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
    checkSlotConflict(facultyId, timeLabel) {
      // Convert HH:MM to minutes
function timeToMinutes(str) {
  const [h, m] = str.split(":").map(Number);
  return h * 60 + m;
}

// Check if two time ranges overlap
function isOverlapping(time1, time2) {
  const [start1, end1] = time1.split("-").map(timeToMinutes);
  const [start2, end2] = time2.split("-").map(timeToMinutes);
  return start1 < end2 && start2 < end1; // Only true if actual overlap
}

      return facultyId && timeLabel && !!this.usedSlots[`${facultyId}|${this.normalizeSlotLabel(timeLabel)}`];
    },
    checkRoomConflict(roomId, timeLabel) {
      return roomId && timeLabel && !!this.usedRooms[`${roomId}|${this.normalizeSlotLabel(timeLabel)}`];
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
assignSuggestion(rowId, suggestion) {
  const row = this.pendingSchedules.find(r => r.id === rowId);
  if (!row) return;

  row.faculty = suggestion.faculty_name || suggestion.faculty || row.faculty;
  row.time = suggestion.time_slot_label || suggestion.time || row.time;
  row.classroom = suggestion.room_name || suggestion.room || row.classroom;
  row.units = Number(suggestion.units || row.units || 0);
  row.assigned_suggestion = { ...suggestion };

  // Mark slots as used
  const normTime = row.time;
  this.usedSlots[`${suggestion.faculty_id}|${normTime}`] = true;
  this.usedRooms[`${suggestion.room_id || suggestion.room}|${normTime}`] = true;

  // Refresh all possible assignments dynamically
  this.refreshAISuggestions();
}
,
refreshAISuggestions() {
  for (const s of this.pendingSchedules) {
    // Skip already assigned subjects
    if (s.faculty && s.faculty !== 'Unknown') continue;

    const allOptions = s._original_possible_assignments || [];

    // Map faculty_id → options for this subject only
    const facultyMap = {};
    allOptions.forEach(opt => {
      const normTime = opt.time_slot_label || opt.time;
      if (this.checkSlotConflict(opt.faculty_id, normTime)) return;
      if (this.checkRoomConflict(opt.room_id || opt.room, normTime)) return;

      if (!facultyMap[opt.faculty_id]) facultyMap[opt.faculty_id] = [];
      facultyMap[opt.faculty_id].push(opt);
    });

    const suggestions = [];

    // For each faculty, sort options and pick top 2
    for (const facultyId in facultyMap) {
      const arr = facultyMap[facultyId];

      arr.sort((a, b) => {
        // Department match is highest priority
        const deptA = a.department === s.department ? 1 : 0;
        const deptB = b.department === s.department ? 1 : 0;
        if (deptB - deptA !== 0) return deptB - deptA;

        // Then underload (less loaded is better)
        const loadA = (a.faculty_current_load || 0) / (a.faculty_max_load || 12);
        const loadB = (b.faculty_current_load || 0) / (b.faculty_max_load || 12);
        if (loadA - loadB !== 0) return loadA - loadB;

        // Then earliest time
        return (a.time_slot_label || a.time || "").localeCompare(b.time_slot_label || b.time || "");
      });

      suggestions.push(...arr.slice(0, 2)); // top 2 per faculty
    }

    // Sort overall suggestions: best department match on top
    suggestions.sort((a, b) => {
      const deptA = a.department === s.department ? 1 : 0;
      const deptB = b.department === s.department ? 1 : 0;
      return deptB - deptA; // best match first
    });

    s.possible_assignments = suggestions;
  }
}

,
    getFacultyTotal(facultySchedules) {
      return facultySchedules.reduce((sum,s)=>sum+(Number(s.units||0)),0);
    },
    formatDate(dateStr){ const d=new Date(dateStr); return d.toLocaleDateString("en-US",{month:"short",day:"numeric",year:"numeric"}); },
    exitSchedule(){ this.pendingSchedules=[]; this.selectedBatch=null; },
    closeModal(){ this.showModal=false; this.pendingSchedules=[]; this.selectedRows=[]; this.editMode=false; this.deleteMode=false; },
    toggleEditMode(){ this.editMode=!this.editMode; this.editableCell=null; if(this.editMode) alert("✅ You can drag or double-click to edit cells."); },
    enableEdit(faculty,row,col){ if(!this.editMode) return; const key=col.toLowerCase().replace(" ","_"); this.editableCell={faculty,row,col}; this.editableValue=this.groupedByFaculty[faculty][row][key]; },
    isEditingCell(faculty,row,col){ return this.editableCell&&this.editableCell.faculty===faculty&&this.editableCell.row===row&&this.editableCell.column===col; },
    saveEdit(faculty,row,col){ const key=col.toLowerCase().replace(" ","_"); const facultyRows=this.pendingSchedules.filter(s=>s.faculty===faculty); const editedRow=facultyRows[row]; if(!editedRow) return; editedRow[key]=this.editableValue; this.editableCell=null; this.editableValue=""; this.pendingSchedules=[...this.pendingSchedules]; },
    async loadPendingSchedules(){ this.show(); try{ const res=await fetch("/api/pending-schedules"); const data=await res.json(); this.batchList=data.pending||data.batches||[]; } catch(err){ console.error(err); alert("Failed to load pending schedules."); } finally{ this.hide(); } },
    async openBatch(batchId){ this.selectedBatch=batchId; this.show(); try{ const res=await fetch(`/api/pending-schedules/${batchId}`); const data=await res.json(); let grouped=[],unassigned=[]; if(data.grouped||data.unassigned){ grouped=Object.values(data.grouped||{}).flat(); unassigned=data.unassigned||[]; this.pendingSchedules=[...grouped.map(s=>({ id:s._localId||s.id||`${s.courseCode||s.subject}-${Math.random()}`, subject_code:s.courseCode||s.course_code||"", faculty:s.faculty||s.faculty_name||"Unknown", subject:s.subject||s.subject_title||"Untitled", time:s.time||s.time_slot||"", classroom:s.classroom||s.room_name||s.room||"", course_section:s.courseSection||s.course_section||"", units:Number(s.units||0), payload:s.payload||null, possible_assignments:s.possible_assignments||s.payload?.possible_assignments||[], _original_possible_assignments:s.possible_assignments?.length?[...s.possible_assignments]:s.payload?.possible_assignments?[...s.payload.possible_assignments]:[] }))]; unassigned.forEach(u=>{ const derivedOptions=u.possible_assignments||u.payload?.possible_assignments||[]; this.pendingSchedules.push({ id:u._localId||u.id||`${u.subject_code||u.subject}-${Math.random()}`, subject_code:u.course_code||u.CourseCode||"", faculty:u.faculty||"Unknown", subject:u.subject_display||u.subject_title||u.subject||"Untitled", time:u.time||u.time_slot_label||"", classroom:u.classroom||u.room_name||"", course_section:u.course_section||u.courseSection||"", units:Number(u.units||0), payload:u.payload||null, possible_assignments:derivedOptions, _original_possible_assignments:derivedOptions.length?[...derivedOptions]:u.payload?.possible_assignments?[...u.payload.possible_assignments]:[] }); }); } this.showModal=true; this.refreshAISuggestions(); this.$forceUpdate(); } catch(err){ console.error(err); alert("Failed to load batch details."); } finally{ this.hide(); } },
    async saveChanges(){ if(!this.selectedBatch) return alert("No batch selected."); if(!this.pendingSchedules.length) return alert("No schedules to save."); this.show(); try{ const schedulesToSave=this.pendingSchedules.map(s=>({ id:s.id, subject_code:s.subject_code, subject:s.subject, time:s.time, classroom:s.classroom, course_section:s.course_section, units:Number(s.units), faculty:s.faculty })); const res=await fetch(`/api/pending-schedules/${this.selectedBatch}/update`,{method:"PUT",headers:{"Content-Type":"application/json"},body:JSON.stringify({schedules:schedulesToSave})}); const data=await res.json(); if(data.success){ alert("✅ Changes saved successfully!"); this.loadPendingSchedules(); this.showModal=false; this.editMode=false; this.editableCell=null; this.editableValue=""; }else alert("❌ Failed to save changes: "+data.message); }catch(err){console.error(err); alert("Error saving schedule changes.");}finally{this.hide();}},
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

