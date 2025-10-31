<template>
  <div class="export-page">
    <div class="header">
      <h3>Export Schedules</h3>
      <div class="actions">
        <button class="btn secondary" @click="$router.push('/schedule')">Back</button>
        <button class="btn" @click="exportXlsx">Export XLSX</button>
        <button class="btn" @click="exportPdf">Export PDF</button>
      </div>
    </div>

    <div class="view-toggle">
      <button class="chip" :class="{ active: activePanel==='both' }" @click="activePanel='both'">Show Both</button>
      <button class="chip" :class="{ active: activePanel==='faculty' }" @click="activePanel='faculty'">Faculty Loads</button>
      <button class="chip" :class="{ active: activePanel==='classes' }" @click="activePanel='classes'">Class Schedules</button>
    </div>

    <div class="filters">
      <label>
        Academic Year:
        <select v-model="selectedAcademicYear" @change="rebuildFromSelection">
          <option v-for="y in academicYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </label>
      <label>
        Semester:
        <select v-model="selectedSemester" @change="rebuildFromSelection">
          <option v-for="s in semesters" :key="s" :value="s">{{ s }}</option>
        </select>
      </label>
      <label>
        Batch:
        <select v-model="selectedBatchId" @change="rebuildFromSelection">
          <option v-for="b in batches" :key="b" :value="b">{{ b }}</option>
    </select>
      </label>
    </div>

    <div class="meta" v-if="selectedAcademicYear || selectedSemester">
      <strong>Academic Year:</strong> {{ selectedAcademicYear || '—' }}
      &nbsp;•&nbsp;
      <strong>Semester:</strong> {{ selectedSemester || '—' }}
      &nbsp;•&nbsp;
      <strong>Batch:</strong> {{ selectedBatchId || '—' }}
    </div>

    <div class="export-container" v-if="facultyLoads.length || Object.keys(courseSchedules).length">
      <!-- Faculty Loads Panel -->
      <section v-if="facultyLoads.length && (activePanel==='both' || activePanel==='faculty')" class="panel" ref="facultyPanel">
        <h3 class="panel-title">Faculty Loads</h3>
        <div class="panel-body">
          <div v-for="fac in facultyLoads" :key="fac.name" class="block">
            <h4 class="subheading">{{ fac.name }}</h4>
            <table class="schedule-table wide compact">
            <thead>
              <tr>
                <th>Subject Code</th>
                <th>Subject</th>
                <th>Room</th>
                <th>Time</th>
                <th>Course Section</th>
                <th>Units</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in fac.loads" :key="idx">
                <td>{{ row.course_code || 'N/A' }}</td>
                <td>{{ row.subject || 'N/A' }}</td>
                <td>{{ row.classroom || 'N/A' }}</td>
                <td>{{ row.time || 'N/A' }}</td>
                <td>{{ row.course_section || 'N/A' }}</td>
                <td>{{ row.units || 0 }}</td>
              </tr>
            </tbody>
            </table>
            <div class="total-units">Total Load Units: {{ fac.totalUnits }}</div>
          </div>
        </div>
      </section>

      <!-- Class Schedules Panel -->
      <section v-if="activePanel==='both' || activePanel==='classes'" class="panel" ref="classPanel">
        <h3 class="panel-title">Class Schedules</h3>
        <div class="panel-body">
          <div v-for="(schedules, section) in courseSchedules" :key="section" class="block">
            <h4 class="subheading">{{ section }}</h4>
            <table class="schedule-table wide compact">
        <thead>
          <tr>
                  <th>Subject Code</th>
            <th>Subject</th>
                  <th>Room</th>
            <th>Time</th>
                  <th>Faculty</th>
                  <th>Units</th>
          </tr>
        </thead>
        <tbody>
                <tr v-for="row in schedules" :key="row.id">
                  <td>{{ row.course_code || 'N/A' }}</td>
                  <td>{{ row.subject || 'N/A' }}</td>
                  <td>{{ row.classroom || 'N/A' }}</td>
                  <td>{{ row.time || 'N/A' }}</td>
                  <td>{{ row.faculty || 'N/A' }}</td>
                  <td>{{ row.units || 0 }}</td>
          </tr>
        </tbody>
      </table>
            <div class="total-units">Total Load Units: {{ getSectionTotal(schedules) }}</div>
          </div>
        </div>
      </section>
    </div>

    <div v-else class="empty">No schedules to export.</div>
  </div>
  
</template>

<script>
import * as XLSX from "xlsx";

export default {
  data() {
    return {
      selectedAcademicYear: "",
      selectedSemester: "",
      selectedBatchId: "",
      latestSchedule: [],
      facultyLoads: [],
      courseSchedules: {},
      allSchedules: [],
      academicYears: [],
      semesters: [],
      batches: [],
      activePanel: 'both',
    };
  },
  created() {
    this.loadSchedules();
  },
  methods: {
    async loadSchedules() {
      try {
        const res = await fetch(`/api/finalized-schedules`);
        const data = await res.json();
        const schedules = Array.isArray(data) ? data : data.schedules || [];
        if (!schedules.length) {
          this.latestSchedule = [];
          this.courseSchedules = {};
          return;
        }
        this.allSchedules = schedules;
        this.academicYears = [...new Set(schedules.map(s => s.academicYear).filter(Boolean))];
        this.semesters = [...new Set(schedules.map(s => s.semester).filter(Boolean))];
        this.selectedAcademicYear = this.academicYears[0] || "";
        this.selectedSemester = this.semesters[0] || "";
        this.rebuildFromSelection();
      } catch (e) {
        console.error('Failed to load schedules for export', e);
      }
    },
    rebuildFromSelection() {
      const filtered = this.allSchedules.filter(s => s.academicYear === this.selectedAcademicYear && s.semester === this.selectedSemester);
      const batchIds = [...new Set(filtered.map(s => s.batch_id))];
      // Build batches list sorted by newest first
      this.batches = [...batchIds].sort((a,b) => {
        const da = (filtered.find(s => s.batch_id===a) || {}).created_at;
        const db = (filtered.find(s => s.batch_id===b) || {}).created_at;
        return new Date(db) - new Date(da);
      });
      if (!this.selectedBatchId || !this.batches.includes(this.selectedBatchId)) this.selectedBatchId = this.batches[0] || "";
      const latest = filtered.filter(s => s.batch_id === this.selectedBatchId);
      this.latestSchedule = latest;
      // Build faculty loads
      const groupedFaculty = {};
      latest.forEach(s => {
        const name = s.faculty || 'Unassigned';
        if (!groupedFaculty[name]) groupedFaculty[name] = [];
        groupedFaculty[name].push(s);
      });
      this.facultyLoads = Object.entries(groupedFaculty).map(([name, loads]) => ({
        name,
        loads,
        totalUnits: loads.reduce((sum, l) => sum + (parseFloat(l.units) || 0), 0),
      }));
      // Group by course_section
      const grouped = {};
      latest.forEach(s => {
        const section = s.course_section || 'Unassigned Section';
        if (!grouped[section]) grouped[section] = [];
        grouped[section].push(s);
      });
      this.courseSchedules = grouped;
    },
    getSectionTotal(list) {
      return (list || []).reduce((sum, r) => sum + (parseFloat(r.units) || 0), 0);
    },
    exportXlsx() {
      try {
        const titleSuffix = `${this.selectedAcademicYear || ''} ${this.selectedSemester || ''}`.trim();
        const wb = XLSX.utils.book_new();

        // Sheet 1: Faculty Loads
        if (this.activePanel === 'faculty' || this.activePanel === 'both') {
            const facultyRows = [];
            facultyRows.push([`Faculty Loads - ${titleSuffix}`]);
            facultyRows.push([]); // Spacer

            this.facultyLoads.forEach(f => {
                facultyRows.push([`FACULTY: ${f.name.toUpperCase()}`]);
                facultyRows.push(["Subject Code", "Subject", "Room", "Time", "Course Section", "Units"]);
                f.loads.forEach(r => facultyRows.push([
                    r.course_code || "",
                    r.subject || "",
                    r.classroom || "",
                    r.time || "",
                    r.course_section || "",
                    Number(r.units || 0)
                ]));
                facultyRows.push(["", "", "", "", "Total Load Units:", f.totalUnits]);
                facultyRows.push([]); // Spacer row
            });
            
            const wsFaculty = XLSX.utils.aoa_to_sheet(facultyRows);
            wsFaculty['!cols'] = [{ wch: 15 }, { wch: 40 }, { wch: 20 }, { wch: 25 }, { wch: 25 }, { wch: 10 }];
            XLSX.utils.book_append_sheet(wb, wsFaculty, 'Faculty Loads');
        }

        // Sheet 2: Class Schedules
        if (this.activePanel === 'classes' || this.activePanel === 'both') {
            const classRows = [];
            classRows.push([`Class Schedules - ${titleSuffix}`]);
            classRows.push([]);

            Object.entries(this.courseSchedules).forEach(([section, list]) => {
                classRows.push([`SECTION: ${section.toUpperCase()}`]);
                classRows.push(["Subject Code", "Subject", "Room", "Time", "Faculty", "Units"]);
                list.forEach(r => classRows.push([
                    r.course_code || "",
                    r.subject || "",
                    r.classroom || "",
                    r.time || "",
                    r.faculty || "",
                    Number(r.units || 0)
                ]));
                const sectionTotal = list.reduce((s, r) => s + (parseFloat(r.units) || 0), 0);
                classRows.push(["", "", "", "", "Total Load Units:", sectionTotal]);
                classRows.push([]);
            });
            const wsClass = XLSX.utils.aoa_to_sheet(classRows);
            wsClass['!cols'] = [{ wch: 15 }, { wch: 40 }, { wch: 20 }, { wch: 25 }, { wch: 25 }, { wch: 10 }];
            XLSX.utils.book_append_sheet(wb, wsClass, 'Class Schedules');
        }

        if (!wb.SheetNames.length) {
            return alert('Nothing to export for the selected view.');
        }

        const filename = `Schedule_${titleSuffix || 'export'}.xlsx`;
        XLSX.writeFile(wb, filename);
      } catch (e) {
        console.error('XLSX export failed', e);
        alert('Failed to export XLSX.');
      }
    },
    exportPdf() {
      const fac = document.querySelector('.faculty-loads-for-export');
      try {
        const styles = `
          <style>
            @page { size: A4 portrait; margin: 12mm }
            body { font-family: Arial, sans-serif; }
            .panel-title { font-size: 18px; font-weight: 800; margin: 0 0 10px 0; padding-bottom: 6px; border-bottom: 2px solid #111827 }
            h4 { margin: 12px 0 6px 0; font-weight: 700 }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; }
            th { background: #f5f5f5; }
            .total-units { text-align: right; font-weight: 600; margin: 6px 0 12px 0; }
            .panel { break-after: page; }
          </style>
        `;

        const printPanel = (html, title) => {
          const w = window.open('', '_blank');
          if (!w) { alert('Popup blocked. Please allow popups to export.'); return; }
          w.document.write(`<!doctype html><html><head><title>${title}</title>${styles}</head><body>${html}</body></html>`);
          w.document.close();
          w.onload = () => setTimeout(() => { w.print(); w.close(); }, 200);
        };

        const fac = this.$refs.facultyPanel ? this.$refs.facultyPanel.outerHTML : '';
        const cls = this.$refs.classPanel ? this.$refs.classPanel.outerHTML : '';
        
        if (this.activePanel === 'faculty') {
          if (!fac) return alert('Nothing to export');
          printPanel(fac, 'Faculty Loads');
        } else if (this.activePanel === 'classes') {
          if (!cls) return alert('Nothing to export');
          printPanel(cls, 'Class Schedules');
        } else { // 'both'
          if (!fac && !cls) return alert('Nothing to export');
          const title = 'Faculty Loads & Class Schedules';
          const combined = (fac || '') + (cls || '');
          printPanel(combined, title);
        }
      } catch (e) {
        console.error('PDF export failed', e);
        alert('Failed to export PDF.');
      }
    },
  },
};
</script>

<style scoped>
.export-page { max-width: none; margin: 0; padding: 12px; }
.header { display:flex; align-items:center; justify-content:space-between; margin-bottom: 10px; }
.actions { display:flex; gap:8px }
.btn { padding:8px 12px; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer }
.btn:hover { background:#1d4ed8 }
.btn.secondary { background:#6b7280 }
.btn.secondary:hover { background:#4b5563 }
.filters { display:flex; gap:12px; align-items:center; margin-bottom: 10px }
.filters label { display:flex; gap:6px; align-items:center }
.filters select { padding:6px 8px; border:1px solid #e5e7eb; border-radius:6px }
.view-toggle { display:flex; gap:8px; margin: 8px 0 12px 0 }
.chip { padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:999px; cursor:pointer; font-size:12px }
.chip.active { background:#111827; color:#fff; border-color:#111827 }
.meta { color:#475569; margin-bottom: 12px }
.faculty-section { margin-bottom: 16px }
.course-section { margin-bottom: 16px }
.panel-title { font-size: 22px; font-weight: 800; margin: 0 0 10px 0; padding-bottom: 6px; border-bottom: 2px solid #111827 }
.panel { margin-bottom: 18px; }
.panel-body { display:block }
.block { margin-bottom: 14px }
.subheading { margin: 12px 0 8px 0; font-weight: 700; font-size: 1.1rem; padding-bottom: 4px; border-bottom: 1px solid #dee2e6; }
.schedule-table { width:100%; border-collapse:collapse; background:white }
.schedule-table.wide { table-layout: fixed }
.schedule-table.compact th, .schedule-table.compact td { padding:6px 8px; font-size:12px }
.schedule-table th, .schedule-table td { border:1px solid #e5e7eb; padding:8px; text-align:left }
.schedule-table th { background:#f5f5f5; font-weight:600 }
.total-units { text-align:right; font-weight:600; margin-top:8px; color:#374151 }
.empty { color:#64748b; text-align:center; padding:20px }
</style>