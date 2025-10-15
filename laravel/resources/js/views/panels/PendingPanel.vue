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
              <button class="view-btn" @click="openBatch(b.batch_id)">View</button>
            </td>
          </tr>
          <tr v-if="!filteredBatches.length">
            <td colspan="5" class="text-center">No pending batches found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ====== Selected Batch Modal ====== -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <h3>🧑‍🏫 Batch {{ selectedBatch }}</h3><div class="action-buttons">
  <button class="edit-btn" @click="editBatch">✏️ Edit</button>
  <button class="finalize-btn" @click="finalizeBatch">✅ Finalize</button>
  <button class="delete-btn" @click="deleteBatch(selectedBatch)">🗑 Delete</button>
</div>


        <div
          v-for="(facultySchedules, facultyName) in groupedByFaculty"
          :key="facultyName"
          class="faculty-section"
        >
          <h4>{{ facultyName }}</h4>
          
          <table border="1" cellpadding="5" cellspacing="0" class="create-table">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Time</th>
                <th>Classroom</th>
                <th>Course Code</th>
                <th>Units</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(sched, idx) in facultySchedules" :key="idx">
                <td>{{ sched.subject }}</td>
                <td>{{ sched.time }}</td>
                <td>{{ sched.classroom }}</td>
                <td>{{ sched.course_code }}</td>
                <td>{{ sched.units }}</td>
              </tr>
            </tbody>
          </table>
          <div class="total-units">
            Total Load Units:
            {{
              facultySchedules.reduce((sum, s) => sum + (Number(s.units) || 0), 0)
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
import "/resources/css/create.css";

export default {
  components: { LoadingModal },
  data() {
    return {
      searchQuery: "",
      selectedBatch: "",
      batchList: [],
      pendingSchedules: [],
      showModal: false,
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
      if (!this.searchQuery) return this.batchList;
      const q = this.searchQuery.toLowerCase();
      return this.batchList.filter(
        (b) =>
          b.academicYear?.toLowerCase().includes(q) ||
          b.semester?.toLowerCase().includes(q) ||
          b.batch_id?.toLowerCase().includes(q)
      );
    },
    groupedByFaculty() {
      return this.pendingSchedules.reduce((groups, s) => {
        if (!groups[s.faculty]) groups[s.faculty] = [];
        groups[s.faculty].push(s);
        return groups;
      }, {});
    },
  },
  methods: {
    async loadPendingSchedules() {
      this.show();
      try {
        const res = await fetch("/api/pending-schedules");
        const data = await res.json();
        this.batchList = data.batches || [];
      } catch (err) {
        console.error(err);
        alert("Failed to load pending schedules.");
      } finally {
        this.hide();
      }
    },
    async openBatch(batchId) {
      this.selectedBatch = batchId;
      this.show();
      try {
        const res = await fetch(`/api/pending-schedules/${batchId}`);
        const data = await res.json();
        this.pendingSchedules = data.pending || [];
        this.showModal = true;
      } catch (err) {
        console.error(err);
        alert("Failed to load batch details.");
      } finally {
        this.hide();
      }
    },async editBatch() {
  alert("Edit mode coming soon — you can implement an inline editor or navigate to a schedule form.");
},

async finalizeBatch() {
  if (!confirm("Finalize this batch? It will be moved to the official schedule.")) return;

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
    } else {
      alert("❌ Failed to finalize batch: " + data.message);
    }
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
    const res = await fetch(`/api/pending-schedules/${batchId}`, {
      method: "DELETE",
    });
    const data = await res.json();
    if (data.success) {
      alert("🗑 Batch deleted successfully.");
      this.showModal = false;
      this.loadPendingSchedules();
    } else {
      alert("❌ Failed to delete batch.");
    }
  } catch (err) {
    console.error(err);
    alert("Error deleting batch.");
  } finally {
    this.hide();
  }
},

    closeModal() {
      this.showModal = false;
      this.pendingSchedules = [];
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
      this.selectedBatch = "";
    },
  },
};
</script>

<style scoped>
/* ===== Modal Design ===== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  overflow-y: auto;
 z-index: 5000; /* Ensure it's higher than sidebar/nav */
  padding-top: 50px;
}

.modal-content {
  background: white;
  width: 90%;
  max-width: 1000px;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
  max-height: 80vh;
  overflow-y: auto;
}

.close-btn {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  margin-top: 20px;
  cursor: pointer;
}
.close-btn:hover {
  background: #c0392b;
}
</style>
