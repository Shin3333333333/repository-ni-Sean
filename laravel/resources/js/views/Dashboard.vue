<template>
  <div class="dashboard">

    <!-- Top row: Graphs + Scheduling Activities -->
    <div class="top-row">
      <!-- Faculty Load Overview Charts -->
      <div class="charts-container">
        <div class="chart-card">
          <h3>Full-time Faculty Load</h3>
          <canvas id="fulltimeChart"></canvas>
        </div>
        <div class="chart-card">
          <h3>Part-time Faculty Load</h3>
          <canvas id="parttimeChart"></canvas>
        </div>
      </div>

      <!-- Scheduling Activities -->
      <div class="activities-card">
        <h3>Scheduling Activities</h3>
        <table class="activities-table">
          <thead>
            <tr>
              <th>Activity</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Last Generated Schedule</td>
              <td>{{ lastGeneratedDisplay || '—' }}</td>
            </tr>
            <tr>
              <td>Last Conflict Detected</td>
              <td>{{ lastConflictDetectedDisplay || '—' }}</td>
            </tr>
            <tr>
              <td>Last Conflict Resolved</td>
              <td>{{ lastConflictResolvedDisplay || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Stats Counters -->
    <div class="stats-row">
      <div class="stat-card">
        <h4>Subjects Offered</h4>
        <p>{{ subjectsOffered }}</p>
      </div>
      <div class="stat-card">
        <h4>Faculty Members</h4>
        <p>{{ facultyCount }}</p>
      </div>
      <div class="stat-card">
        <h4>Total Rooms</h4>
        <p>{{ roomCount }}</p>
      </div>
      <div class="stat-card" :class="conflictsClass">
        <h4>Conflicts Detected</h4>
        <p>{{ conflicts }}</p>
      </div>
    </div>

  </div>
</template>

<script>
import Chart from "chart.js/auto";
import { errorStore } from "../assets/errorStore";

export default {
  name: "Dashboard",
  data() {
    return {
      activeAcademicYear: '',
      activeSemester: '',
      activeBatchId: null,
      latestBatchId: null,
      latestRows: [],
      fulltimeChart: null,
      parttimeChart: null,
      professors: [],
      isRendering: false,
      isMounted: false,
      lastGeneratedDisplay: null,
      lastConflictDetectedDisplay: null,
      lastConflictResolvedDisplay: null,
      subjectsOffered: 0,
      facultyCount: 0,
      roomCount: 0,
    };
  },
  computed: {
    conflicts() {
      return errorStore.unresolvedCount;
    },
    conflictsClass() {
      return this.conflicts > 0 ? "conflicts-red" : "conflicts-green";
    },
  },
  watch: {
    latestRows() {
      if (this.isMounted) {
        this.$nextTick(() => {
          this.renderFacultyCharts();
        });
      }
    },
    professors() {
      if (this.isMounted) {
        this.$nextTick(() => {
          this.renderFacultyCharts();
        });
      }
    },
  },
  async mounted() {
    await this.fetchActiveScheduleInfo();
    await this.fetchProfessors();
    await this.loadLatestForActive();
    await this.$nextTick(); // Wait for DOM to be ready
    this.renderFacultyCharts();
    this.isMounted = true;
  },
  methods: {
    async fetchProfessors() {
      try {
        const res = await fetch('/api/professors');
        if (!res.ok) throw new Error(`Server returned ${res.status}`);
        this.professors = await res.json();
      } catch (err) {
        console.error('Failed to fetch professors', err);
      }
    },
    async fetchActiveScheduleInfo() {
      try {
        const res = await fetch(`/api/active-schedule`);
        if (!res.ok) return;
        const data = await res.json();
        this.activeAcademicYear = data?.academicYear || '';
        this.activeSemester = data?.semester || '';
        this.activeBatchId = data?.batch_id || null;
      } catch (e) {
        console.error('Failed to fetch active schedule', e);
      }
    },
    async loadLatestForActive() {
      try {
        const res = await fetch(`/api/finalized-schedules`);
        if (!res.ok) throw new Error(`Server returned ${res.status}`);
        const payload = await res.json();
        const schedules = Array.isArray(payload) ? payload : payload.schedules || [];

        let filtered = schedules;
        if (this.activeAcademicYear && this.activeSemester) {
          filtered = schedules.filter(
            s => s.academicYear === this.activeAcademicYear && s.semester === this.activeSemester
          );
        }
        if (!filtered.length) {
          this.latestRows = [];
          this.updateDerivedStats();
          return;
        }

        if (this.activeBatchId) {
          this.latestRows = filtered.filter(s => s.batch_id === this.activeBatchId);
          this.latestBatchId = this.activeBatchId;
        } else {
          const latestBatchId = filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0].batch_id;
          this.latestBatchId = latestBatchId;
          this.latestRows = filtered.filter(s => s.batch_id === latestBatchId);
        }
        this.updateDerivedStats();
      } catch (err) {
        console.error(err);
      }
    },
    updateDerivedStats() {
      const rows = this.latestRows || [];
      // Last generated
      const latestDate = rows.length ? new Date(Math.max(...rows.map(r => new Date(r.created_at).getTime() || 0))) : null;
      this.lastGeneratedDisplay = latestDate ? latestDate.toLocaleString() : null;

      // Stats (reflect staged batch only)
      this.subjectsOffered = rows.length;
      const faculties = new Set();
      const rooms = new Set();
      rows.forEach(r => {
        if (r.faculty) faculties.add(r.faculty);
        if (r.classroom) rooms.add(r.classroom);
      });
      this.facultyCount = faculties.size;
      this.roomCount = rooms.size;
    },
    getProfessorTypeByName(name) {
      const professor = this.professors.find(p => p.name === name);
      return professor?.type || null;
    },
    renderFacultyCharts() {
      // Prevent multiple simultaneous renders
      if (this.isRendering) return;
      this.isRendering = true;

      try {
        const rows = this.latestRows || [];

        // Aggregate total units per faculty, separating by type
        const fulltimeLoadMap = new Map();
        const parttimeLoadMap = new Map();

        rows.forEach(r => {
          const name = r.faculty || 'Unassigned';
          const units = parseFloat(r.units) || 0;
          const professorType = this.getProfessorTypeByName(name);

          if (professorType === 'Full-time' || professorType === 'full-time') {
            fulltimeLoadMap.set(name, (fulltimeLoadMap.get(name) || 0) + units);
          } else if (professorType === 'Part-time' || professorType === 'part-time') {
            parttimeLoadMap.set(name, (parttimeLoadMap.get(name) || 0) + units);
          }
        });

        // Render Full-time chart
        const fulltimeLabels = Array.from(fulltimeLoadMap.keys());
        const fulltimeData = Array.from(fulltimeLoadMap.values());
        this.fulltimeChart = this.renderChart('fulltimeChart', fulltimeLabels, fulltimeData, this.fulltimeChart);

        // Render Part-time chart
        const parttimeLabels = Array.from(parttimeLoadMap.keys());
        const parttimeData = Array.from(parttimeLoadMap.values());
        this.parttimeChart = this.renderChart('parttimeChart', parttimeLabels, parttimeData, this.parttimeChart);
      } catch (error) {
        console.error('Error rendering faculty charts:', error);
      } finally {
        this.isRendering = false;
      }
    },
    renderChart(canvasId, labels, data, existingChart) {
      try {
        // Destroy existing chart properly
        if (existingChart && typeof existingChart.destroy === 'function') {
          try {
            // Stop all animations before destroying
            existingChart.stop();
            existingChart.destroy();
            // Clear the canvas
            const oldCanvas = document.getElementById(canvasId);
            if (oldCanvas) {
              const oldCtx = oldCanvas.getContext("2d");
              if (oldCtx) {
                oldCtx.clearRect(0, 0, oldCanvas.width, oldCanvas.height);
              }
            }
          } catch (e) {
            console.warn(`Error destroying chart: ${e.message}`);
          }
        }

        const canvas = document.getElementById(canvasId);
        if (!canvas) {
          console.error(`Canvas element with id "${canvasId}" not found`);
          return null;
        }

        const ctx = canvas.getContext("2d");
        if (!ctx) {
          console.error(`Could not get 2D context for "${canvasId}"`);
          return null;
        }

        // If no data, show empty chart with a message
        if (!labels || labels.length === 0) {
          labels = ['No data'];
          data = [0];
        }

        const colors = labels.map((_, i) => ["#3498db", "#e74c3c", "#2ecc71", "#9b59b6", "#f39c12", "#1abc9c", "#34495e"][i % 7]);

        return new Chart(ctx, {
          type: "bar",
          data: {
            labels,
            datasets: [
              {
                label: "Load (units)",
                data,
                backgroundColor: colors,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
              duration: 0 // Disable animations to prevent race conditions
            },
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
          },
        });
      } catch (error) {
        console.error(`Error rendering chart for ${canvasId}:`, error);
        return null;
      }
    },
  },
};
</script>


<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 20px;
}

.top-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.charts-container {
  flex: 2;
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.chart-card {
  flex: 1;
  min-width: 300px;
  background: #fff;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  height: 300px;
}

.chart-card canvas {
  width: 100% !important;
  height: 100% !important;
}

.activities-card {
  flex: 1;
  background: #fff;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.activities-card h3 {
  margin-bottom: 10px;
}

/* Table design like manage/schedule */
.activities-table {
  width: 100%;
  border-collapse: collapse;
}
.activities-table th,
.activities-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}
.activities-table th {
  background-color: #f5f5f5;
  font-weight: 600;
}

/* Stats row */
.stats-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.stat-card {
  flex: 1;
  min-width: 200px;
  background: #f5f5f5;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.stat-card h4 {
  margin: 0 0 8px;
  font-weight: 600;
}

.stat-card p {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 0;
}

/* Conflicts detected colors */
.conflicts-red {
  background: #ffe6e6;
  border: 1px solid #ff4d4d;
  color: #b30000;
}

.conflicts-green {
  background: #e6ffe6;
  border: 1px solid #4dff4d;
  color: #006600;
}
</style>
