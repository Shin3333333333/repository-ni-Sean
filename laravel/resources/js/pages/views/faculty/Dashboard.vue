<template>
  <div class="faculty-dashboard">
    <div class="dashboard-header">
      <h1 class="header-title">Faculty Dashboard</h1>
      <p class="header-subtitle">Your current teaching schedule</p>
      <div>
        <button class="btn" @click="exportPdf">Export PDF</button>
        <button class="btn" @click="exportXlsx">Export XLSX</button>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading your schedule...</p>
    </div>

    <div v-if="!loading && schedule.length === 0" class="empty-state">
      <div class="empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
        </svg>
      </div>
      <h2 class="empty-title">No Schedule Found</h2>
      <p class="empty-description">There is currently no active schedule assigned to you.</p>
    </div>

    <div v-if="!loading && schedule.length > 0">
      <div class="stats-container">
        <div class="stat-card">
          <h4 class="stat-title">Total Load</h4>
          <p class="stat-value">{{ totalLoad }} units</p>
        </div>
        <div class="stat-card">
          <h4 class="stat-title">Subjects</h4>
          <p class="stat-value">{{ subjects.length }}</p>
        </div>
      </div>

      <div class="view-tabs">
        <button :class="{ active: currentView === 'matrix' }" @click="currentView = 'matrix'">Matrix</button>
        <button :class="{ active: currentView === 'table' }" @click="currentView = 'table'">Table</button>
      </div>

      <div v-if="currentView === 'matrix'" class="schedule-grid">
        <div v-for="item in schedule" :key="item.id" class="schedule-card">
          <div class="card-header">
            <h3 class="subject-title">{{ item.subject }}</h3>
            <p class="course-info">{{ item.course_code }} - {{ item.course_section }}</p>
          </div>
          <div class="card-body">
            <div class="info-row">
              <span class="info-label">Time:</span>
              <span class="info-value">{{ item.time_start }} - {{ item.time_end }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Day:</span>
              <span class="info-value">{{ item.day }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Classroom:</span>
              <span class="info-value">{{ item.room?.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="currentView === 'table'" class="schedule-table-container">
        <table class="schedule-table" ref="scheduleTable">
          <thead>
            <tr>
              <th>Course Code</th>
              <th>Section</th>
              <th>Subject</th>
              <th>Day</th>
              <th>Time</th>
              <th>Room</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in schedule" :key="item.id">
              <td>{{ item.course_code }}</td>
              <td>{{ item.course_section }}</td>
              <td>{{ item.subject }}</td>
              <td>{{ item.day }}</td>
              <td>{{ item.time_start }} - {{ item.time_end }}</td>
              <td>{{ item.room?.name }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5" style="text-align: right; font-weight: bold;">Total Load Units:</td>
              <td style="font-weight: bold;">{{ totalLoad }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';
import * as XLSX from 'xlsx';
import emitter from '@/eventBus';

export default {
  name: 'FacultyDashboard',
  data() {
    return {
      schedule: [],
      totalLoad: 0,
      subjects: [],
      loading: true,
      currentView: 'matrix',
    };
  },
  async created() {
    await this.fetchSchedule();
  },
  mounted() {
    emitter.on('schedule-updated', this.fetchSchedule);
  },
  beforeUnmount() {
    emitter.off('schedule-updated', this.fetchSchedule);
  },
  methods: {
    async fetchSchedule() {
      this.loading = true;
      try {
        const response = await axios.get('/faculty/schedule');
        this.schedule = response.data.schedule;
        this.totalLoad = response.data.totalLoad;
        this.subjects = response.data.subjects;
      } catch (error) {
        console.error("Error fetching schedule:", error);
      } finally {
        this.loading = false;
      }
    },
    exportPdf() {
      try {
        const tableHTML = this.generateScheduleTableHTML();
        if (!tableHTML) return alert('No schedule data to export.');

        const styles = `
          <style>
            @page { size: A4 portrait; margin: 12mm }
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; }
            th { background: #f5f5f5; }
            tfoot { font-weight: bold; }
          </style>
        `;

        const printWindow = window.open('', '_blank');
        if (!printWindow) return alert('Popup blocked! Please allow popups for this site to export PDF.');

        printWindow.document.write(`<!doctype html><html><head><title>Faculty Schedule</title>${styles}</head><body>${tableHTML}</body></html>`);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
      } catch (e) {
        console.error('PDF export failed', e);
        alert('Failed to export PDF.');
      }
    },

    exportXlsx() {
      try {
        if (this.schedule.length === 0) return alert('No schedule data to export.');

        const data = this.schedule.map(item => ({
          'Course Code': item.course_code,
          'Section': item.course_section,
          'Subject': item.subject,
          'Day': item.day,
          'Time': `${item.time_start} - ${item.time_end}`,
          'Room': item.room?.name || 'N/A',
        }));

        const ws = XLSX.utils.json_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Schedule');
        XLSX.writeFile(wb, 'FacultySchedule.xlsx');
      } catch (e) {
        console.error('XLSX export failed', e);
        alert('Failed to export XLSX.');
      }
    },

    generateScheduleTableHTML() {
      if (!this.schedule || this.schedule.length === 0) return '';

      let table = '<table class="schedule-table">';
      table += '<thead><tr><th>Course Code</th><th>Section</th><th>Subject</th><th>Day</th><th>Time</th><th>Room</th></tr></thead>';
      table += '<tbody>';
      this.schedule.forEach(item => {
        table += `<tr>
          <td>${item.course_code}</td>
          <td>${item.course_section}</td>
          <td>${item.subject}</td>
          <td>${item.day}</td>
          <td>${item.time_start} - ${item.time_end}</td>
          <td>${item.room?.name || 'N/A'}</td>
        </tr>`;
      });
      table += '</tbody>';
      table += `<tfoot><tr><td colspan="5" style="text-align: right; font-weight: bold;">Total Load Units:</td><td style="font-weight: bold;">${this.totalLoad}</td></tr></tfoot>`;
      table += '</table>';
      return table;
    },
  },
};
</script>

<style scoped>
.faculty-dashboard {
  padding: 2rem;
  background-color: #f9fafb;
  min-height: 100vh;
}

.dashboard-header {
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #111827;
}

.header-subtitle {
  font-size: 1.125rem;
  color: #6b7280;
}

.btn {
  padding: 8px 12px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  margin-left: 10px;
}

.btn:hover {
  background: #1d4ed8;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  background-color: #ffffff;
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border-left-color: #3b82f6;
  animation: spin 1s ease infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  color: #9ca3af;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #111827;
}

.empty-description {
  color: #6b7280;
}

.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background-color: #ffffff;
  padding: 1.5rem;
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.stat-title {
  font-size: 1rem;
  font-weight: 600;
  color: #4b5563;
  margin: 0 0 0.5rem 0;
}

.stat-value {
  font-size: 2rem;
  font-weight: 800;
  color: #111827;
  margin: 0;
}

.view-tabs {
  margin-bottom: 1.5rem;
}

.view-tabs button {
  padding: 10px 20px;
  font-size: 1rem;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #6b7280;
  border-bottom: 2px solid transparent;
}

.view-tabs button.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.schedule-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.schedule-card {
  background-color: #ffffff;
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.schedule-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.subject-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
}

.course-info {
  font-size: 0.875rem;
  color: #4b5563;
  margin: 0;
}

.card-body {
  padding: 1.5rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-label {
  font-weight: 500;
  color: #6b7280;
}

.info-value {
  font-weight: 600;
  color: #111827;
}

.schedule-table-container {
  background-color: #ffffff;
  padding: 1.5rem;
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.schedule-table {
  width: 100%;
  border-collapse: collapse;
}

.schedule-table th, .schedule-table td {
  border: 1px solid #e5e7eb;
  padding: 0.75rem 1rem;
  text-align: left;
}

.schedule-table th {
  background-color: #f9fafb;
  font-weight: 600;
  color: #374151;
}
</style>