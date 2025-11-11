<template>
  <div>
    <!-- Filters -->
    <div class="filters">
      <div class="filter-group">
        <label>
          Academic Year:
          <select v-model="selectedAcademicYear" @change="onAcademicYearChange">
            <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </label>

        <label>
          Semester:
          <select v-model="selectedSemester" @change="loadFilteredSchedule">
            <option v-for="sem in semesters" :key="sem" :value="sem">{{ sem }}</option>
          </select>
        </label>

        <label>
          Course Section:
          <select v-model="selectedCourseSection" @change="filterSchedules">
            <option value="">All</option>
            <option v-for="section in courseSections" :key="section" :value="section">{{ section }}</option>
          </select>
        </label>

        <label>
          Faculty:
          <select v-model="selectedFaculty" @change="filterSchedules">
            <option value="">All</option>
            <option v-for="faculty in facultyList" :key="faculty" :value="faculty">{{ faculty }}</option>
          </select>
        </label>
      </div>

      <div class="actions">
        <button class="export-btn" @click="goToExport">Export</button>
        <button
          class="stage-btn"
          v-if="latestSchedule.length"
          :class="{ active: isSelectedActive() }"
          :disabled="isSelectedActive()"
          @click="!isSelectedActive() && confirmAndSetAsActive()"
          :title="isSelectedActive() ? 'This schedule is active' : 'Stage as active schedule'"
        >
          {{ isSelectedActive() ? 'Active' : 'Stage as Active Schedule' }}
        </button>
        <button
          class="archive-btn"
          v-if="latestSchedule.length"
          @click="archiveCurrentBatch"
          :title="isSelectedActive() ? 'You cannot archive the current active schedule' : 'Archive the current latest batch'"
        >
          Archive Current Batch
        </button>
        <button class="archives-open-btn" @click="openArchiveDrawer" title="View Archives">Archives</button>
         <button class="detect-btn" @click="detectConflicts">
      Detect Conflicts
    </button>
          </div>
    </div>
    
    <!-- Current Active Info -->
    <div v-if="activeScheduleInfo" class="active-banner">
      <strong>Active Schedule:</strong>
      {{ activeScheduleInfo.academicYear }} – {{ activeScheduleInfo.semester }}
    </div>

    <!-- View Toggle -->
    <div class="view-toggle">
      <button @click="activeView='faculty'" :class="{ active: activeView==='faculty' }">Faculty Loads</button>
      <button @click="activeView='schedules'" :class="{ active: activeView==='schedules' }">Class Schedules</button>
    </div>

    <!-- Faculty Loads View -->
    <div v-if="activeView==='faculty'" ref="facultyView">
      <div v-if="facultyLoads.length===0">No faculty loads available.</div>
      <div v-for="faculty in facultyLoads" :key="faculty.name" class="faculty-section">
        <h4>{{ formatFacultyHeader(faculty.name) }}</h4>
        <table class="styled-table">
          <thead>
            <tr>
              <th>Course Code</th>
              <th>Subject</th>
              <th>Time</th>
              <th>Room</th>
              <th>Course Section</th>
              <th>Units</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(load, idx) in faculty.loads" :key="idx">
              <td>{{ load.course_code || 'N/A' }}</td>
              <td>{{ load.subject || 'N/A' }}</td>
              <td>{{ load.time || 'N/A' }}</td>
              <td>{{ load.classroom || 'N/A' }}</td>
              <td>{{ load.course_section || 'N/A' }}</td>
              <td>{{ load.units || 0 }}</td>
            </tr>
          </tbody>
        </table>
        <div class="total-units">Total Load Units: {{ faculty.totalUnits }}</div>
      </div>
    </div>

    <!-- Class Schedules View -->
    <div v-if="activeView==='schedules'" ref="schedulesView">
      <div v-if="Object.keys(courseSchedules).length===0">No class schedules available.</div>
      <div v-for="(schedules, section) in courseSchedules" :key="section" class="course-section">
        <h4>{{ section }}</h4>
        <table class="styled-table">
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
            <tr v-for="schedule in schedules" :key="schedule.id">
              <td>{{ schedule.course_code || 'N/A' }}</td>
              <td>{{ schedule.subject || 'N/A' }}</td>
              <td>{{ schedule.classroom || 'N/A' }}</td>
              <td>{{ schedule.time || 'N/A' }}</td>
              <td>{{ schedule.faculty || 'N/A' }}</td>
              <td>{{ schedule.units || 0 }}</td>
            </tr>
          </tbody>
        </table>
        <div class="total-units">Total Load Units: {{ getSectionTotal(schedules) }}</div>
      </div>
    </div>

    <!-- Stage Confirmation Modal -->
    <div v-if="showStageModal" class="modal-backdrop">
      <div class="modal">
        <h3>Stage Schedule</h3>
        <p>
          Are you sure you want to set this schedule as the <strong>active</strong> schedule for
          <em>{{ selectedAcademicYear }}</em> – <em>{{ selectedSemester }}</em>?
        </p>
        <div class="modal-actions">
          <button class="confirm" @click="setAsActiveSchedule">Yes, Set Active</button>
          <button class="cancel" @click="showStageModal = false">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal for similar name -->
    <div v-if="showConfirmationModal" class="modal-backdrop">
      <div class="modal">
        <p>A schedule with a similar name is already active. Are you sure you want to proceed?</p>
        <div class="modal-actions">
          <button @click="setAsActiveSchedule" class="confirm">Yes, Proceed</button>
          <button @click="showConfirmationModal = false" class="cancel">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Archive Drawer -->
    <div v-if="showArchiveDrawer" class="archive-drawer-backdrop" @click.self="showArchiveDrawer=false">
      <aside class="archive-drawer">
        <div class="archive-drawer-header">
          <h3>Archived Schedules</h3>
          <button class="close" @click="showArchiveDrawer=false">✕</button>
        </div>
        <div class="archive-list" v-if="archives.length">
          <div class="archive-item" v-for="a in archives" :key="a.id">
            <div class="ai-main">
              <div class="ai-title">{{ a.academicYear }} – {{ a.semester }}</div>
              <div class="ai-meta">Batch: {{ a.batch_id }} • {{ formatDate(a.created_at) }} • {{ a.count || a.total || 0 }} rows</div>
            </div>
            <div class="ai-actions">
              <button class="restore" @click="restoreArchive(a)">Restore</button>
              <button class="delete" @click="deleteArchive(a)">Delete</button>
            </div>
          </div>
        </div>
        <div class="archive-empty" v-else>
          No archived schedules yet.
        </div>
      </aside>
    </div>

    <!-- Global loading modal -->
    <LoadingModal />
    <ConfirmModal
      :show="confirmOpen"
      :message="confirmMessage"
      @cancel="confirmOpen = false"
      @confirm="executeConfirmAction"
    />
  </div>
</template>

<script>
import emitter from '../../../eventBus';
import LoadingModal from "../../../components/LoadingModal.vue";
import ConfirmModal from "../../../components/ConfirmModal.vue";
import { useLoading } from "../../../composables/useLoading";
import { useToast } from "../../../composables/useToast";
import * as XLSX from "xlsx";
import axios from '../../../axios';

export default {
  components: { LoadingModal, ConfirmModal },
  data() {
    return {
      activeView: 'faculty',
      academicYears: [],
      semesters: [],
      selectedAcademicYear: "",
      selectedSemester: "",
      courseSections: [],
      selectedCourseSection: "",
      facultyList: [],
      selectedFaculty: "",
      latestSchedule: [],
      allSchedules: [], // ← store all fetched schedules
      facultyLoads: [],
      courseSchedules: {},
      showStageModal: false,
      showConfirmationModal: false,
      activeScheduleInfo: null,
      // Archives UI
      showArchiveDrawer: false,
      archives: [],
      isTogglingDisabled: false,
      professors: [],
      professorsByName: {},
      // confirm modal state
      confirmOpen: false,
      confirmMessage: '',
      confirmAction: null,
      availableSchedules: [],
    };
  },
  setup() {
    const { show, hide } = useLoading();
    const { success, error, info } = useToast();
    return { showLoading: show, hideLoading: hide, toastSuccess: success, toastError: error, toastInfo: info };
  },
  created() {
    this.loadLatestSchedule();
    this.fetchActiveScheduleInfo();
    this.loadArchives();
    this.fetchProfessors();
  },
  mounted() {
    emitter.on('schedule-updated', this.refreshData);
    emitter.on('schedule-created', this.refreshData);
  },
  beforeUnmount() {
    emitter.off('schedule-updated', this.refreshData);
    emitter.off('schedule-created', this.refreshData);
  },
  methods: {
    async refreshData() {
      await this.loadLatestSchedule();
      await this.fetchActiveScheduleInfo();
      await this.loadArchives();
    },

    onAcademicYearChange() {
      this.loadLatestSchedule();
      this.fetchActiveScheduleInfo();
      this.loadArchives();
    },
    onAcademicYearChange() {
      const availableSemesters = this.availableSchedules
        .filter(s => s.academicYear === this.selectedAcademicYear)
        .map(s => s.semester);
      this.semesters = [...new Set(availableSemesters)];
      if (!this.semesters.includes(this.selectedSemester)) {
        this.selectedSemester = this.semesters[0] || '';
      }
      this.loadFilteredSchedule();
    },
    exportXlsx() {
      try {
        const wb = XLSX.utils.book_new();
        const titleSuffix = `${this.selectedAcademicYear || ''} ${this.selectedSemester || ''}`.trim();
        const container = this.activeView === 'faculty' ? this.$refs.facultyView : this.$refs.schedulesView;
        if (!container) { this.toastError('Nothing to export'); return; }

        const tables = Array.from(container.querySelectorAll('table'));
        if (!tables.length) { this.toastError('No tables to export'); return; }

        const toWch = (px) => Math.max(8, Math.round((px || 80) / 7));

        tables.forEach((table, idx) => {
          const sheet = XLSX.utils.table_to_sheet(table, { raw: true });
          // Set column widths based on header cell widths if available
          const headerRow = table.tHead && table.tHead.rows && table.tHead.rows[0];
          if (headerRow) {
            const widths = Array.from(headerRow.cells).map(th => ({ wch: toWch(parseInt(getComputedStyle(th).width)) }));
            if (widths.length) sheet['!cols'] = widths;
          }
          // Name the sheet using the section header (preceding h4) if present
          let name = `Table ${idx + 1}`;
          const heading = table.previousElementSibling;
          if (heading && heading.tagName === 'H4') {
            name = heading.textContent.trim().slice(0, 31) || name; // Excel sheet name limit 31
          }
          // Ensure unique sheet names
          let finalName = name;
          let suffix = 1;
          while (wb.SheetNames.includes(finalName)) {
            const base = name.slice(0, 28);
            finalName = `${base}-${++suffix}`;
          }
          XLSX.utils.book_append_sheet(wb, sheet, finalName);
        });

        const filename = `Schedule_${titleSuffix || 'export'}.xlsx`;
        XLSX.writeFile(wb, filename);
      } catch (e) {
        console.error('XLSX export failed', e);
        this.toastError('Failed to export XLSX');
      }
    },
    exportPdf() {
      try {
        const container = this.activeView === 'faculty' ? this.$refs.facultyView : this.$refs.schedulesView;
        if (!container) { this.toastError('Nothing to export'); return; }
        const html = container.innerHTML;
        const w = window.open('', '_blank');
        if (!w) { this.toastError('Popup blocked. Allow popups to export'); return; }
        const styles = `
          <style>
            body { font-family: Arial, sans-serif; }
            h4 { margin: 16px 0; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
            th { background: #f5f5f5; }
            .total-units { text-align: right; font-weight: 600; margin-top: 8px; }
          </style>
        `;
        w.document.write(`<!doctype html><html><head><title>Schedule</title>${styles}</head><body>${html}</body></html>`);
        w.document.close();
        w.focus();
        w.print();
        w.close();
      } catch (e) {
        console.error('PDF export failed', e);
        this.toastError('Failed to export PDF');
      }
    },
    async fetchProfessors() {
      try {
        const res = await axios.get('/professors');
        this.professors = res.data;
        const byName = {};
        (Array.isArray(this.professors) ? this.professors : []).forEach(p => {
          const key = (p.name || '').toString().trim().toLowerCase();
          if (key) byName[key] = p;
        });
        this.professorsByName = byName;
      } catch (e) {
        console.warn('Failed to fetch professors list', e);
      }
    },
    humanizeFacultyType(raw) {
      if (!raw) return null;
      const toTitleCaseHyphen = (str) => str
        .replace(/_/g, '-')
        .split('-')
        .map(s => s.trim())
        .filter(Boolean)
        .map(s => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase())
        .join('-');
      return String(raw)
        .split(',')
        .map(s => s.trim())
        .filter(Boolean)
        .map(toTitleCaseHyphen)
        .join(', ');
    },
    formatFacultyHeader(name) {
      const key = (name || '').toString().trim().toLowerCase();
      const prof = this.professorsByName[key] || null;
      const type = this.humanizeFacultyType(prof ? (prof.type || prof.faculty_type) : null);
      const dept = prof ? (prof.department || null) : null;
      if (type && dept) return `${name} (${type}, ${dept})`;
      if (type) return `${name} (${type})`;
      if (dept) return `${name} (${dept})`;
      return name || 'N/A';
    },
    formatFacultyCell(name) {
      return this.formatFacultyHeader(name);
    },
    async detectConflicts() {
      this.showLoading();
      try {
        const payload = {
          academic_year: this.selectedAcademicYear,
          semester: this.selectedSemester,
        };
        const res = await axios.post('/detect-conflicts', payload);
        const data = res.data;
        if (data?.message) this.toastSuccess(data.message); else this.toastInfo('Conflict detection finished');
        emitter.emit('schedule-updated');
        if (data.redirect) this.$router.push(data.redirect);
      } catch (err) {
        console.error(err);
        this.toastError('Conflict detection failed');
      } finally {
        this.hideLoading();
      }
    },
    async loadLatestSchedule() {
      this.showLoading();
      try {
        const res = await axios.get(`/finalized-schedules`);
        const data = res.data;
        const schedules = Array.isArray(data) ? data : data.schedules || [];

        this.allSchedules = schedules; 

        if (!schedules.length) {
          this.latestSchedule = [];
          this.facultyLoads = [];
          this.courseSchedules = {};
          this.academicYears = [];
          this.semesters = [];
          this.courseSections = [];
          this.facultyList = [];
          return;
        }

        // Group schedules by academic year and semester to find the latest batch
        const groupedByYearAndSem = schedules.reduce((acc, schedule) => {
          const key = `${schedule.academicYear}|${schedule.semester}`;
          if (!acc[key]) {
            acc[key] = [];
          }
          acc[key].push(schedule);
          return acc;
        }, {});

        let latestBatch = [];
        let latestTimestamp = 0;

        for (const key in groupedByYearAndSem) {
          const schedulesInGroup = groupedByYearAndSem[key];
          const mostRecentScheduleInGroup = schedulesInGroup.reduce((latest, current) => {
            const currentTimestamp = new Date(current.created_at).getTime();
            return currentTimestamp > new Date(latest.created_at).getTime() ? current : latest;
          });

          const groupTimestamp = new Date(mostRecentScheduleInGroup.created_at).getTime();
          if (groupTimestamp > latestTimestamp) {
            latestTimestamp = groupTimestamp;
            latestBatch = schedulesInGroup.filter(s => s.batch_id === mostRecentScheduleInGroup.batch_id);
          }
        }

        this.latestSchedule = latestBatch;

        // Extract unique academic years and semesters from all schedules
        const allYears = [...new Set(schedules.map(s => s.academicYear))];
        this.academicYears = allYears.sort((a, b) => b.localeCompare(a)); // Sort descending

        this.availableSchedules = schedules.map(s => ({ academicYear: s.academicYear, semester: s.semester }));

        if (this.latestSchedule.length > 0) {
          this.selectedAcademicYear = this.latestSchedule[0].academicYear;
          this.selectedSemester = this.latestSchedule[0].semester;
        } else if (this.academicYears.length > 0) {
          this.selectedAcademicYear = this.academicYears[0];
          const availableSemesters = [...new Set(this.availableSchedules.filter(s => s.academicYear === this.selectedAcademicYear).map(s => s.semester))];
          this.semesters = availableSemesters;
          this.selectedSemester = this.semesters[0] || '';
        } else {
          this.selectedAcademicYear = '';
          this.selectedSemester = '';
        }

        this.onAcademicYearChange(); // This will set semesters and load the filtered schedule

      } catch (err) {
        console.error('Failed to load latest schedule', err);
        this.toastError('Failed to load schedule data.');
      } finally {
        this.hideLoading();
      }
    },

    loadFilteredSchedule() {
      if (!this.allSchedules.length) return;

      // Filter by academic year and semester
      const filtered = this.allSchedules.filter(
        s =>
          s.academicYear === this.selectedAcademicYear &&
          s.semester === this.selectedSemester
      );

      if (!filtered.length) {
        this.latestSchedule = [];
        this.facultyLoads = [];
        this.courseSchedules = {};
        return;
      }

      // Use latest batch from filtered set
      const latestBatchId = filtered.sort(
        (a, b) => new Date(b.created_at) - new Date(a.created_at)
      )[0].batch_id;

      const latestBatchSchedules = filtered.filter(s => s.batch_id === latestBatchId);
      this.latestSchedule = latestBatchSchedules;

      // Set course sections for the filter dropdown
      const sections = new Set(this.latestSchedule.map(s => s.course_section));
      this.courseSections = Array.from(sections).sort();

      const faculty = new Set(this.latestSchedule.map(s => s.faculty));
      this.facultyList = Array.from(faculty).sort();

      this.processFacultyLoads(this.latestSchedule);
      this.processCourseSchedules(this.latestSchedule);
    },

    async fetchActiveScheduleInfo() {
      try {
        const res = await axios.get('/active-schedule');
        this.activeScheduleInfo = res.data || null;
      } catch (e) {
        console.warn('Failed to fetch active schedule info', e);
        this.activeScheduleInfo = null;
      }
    },

    isSelectedActive() {
      if (!this.activeScheduleInfo) return false;
      return (
        this.activeScheduleInfo.academicYear === this.selectedAcademicYear &&
        this.activeScheduleInfo.semester === this.selectedSemester
      );
    },

    confirmAndSetAsActive() {
      const activeName = this.activeScheduleInfo?.academicYear;
      const selectedName = this.selectedAcademicYear;

      if (activeName && selectedName.startsWith(activeName) && selectedName !== activeName) {
        this.showConfirmationModal = true;
      } else {
        this.showStageModal = true;
      }
    },

    async setAsActiveSchedule() {
      if (this.isTogglingDisabled) return;
      this.isTogglingDisabled = true;
      this.showLoading();
      try {
        const batchId = this.latestSchedule[0]?.batch_id;
        if (!batchId) {
          this.toastError('Cannot set active schedule: Batch ID is missing.');
          return;
        }

        const payload = {
          academicYear: this.selectedAcademicYear,
          semester: this.selectedSemester,
          batch_id: batchId,
        };

        await axios.post('/set-active-schedule', payload);
        this.toastSuccess('Schedule has been set as active.');
        emitter.emit('schedule-updated'); // Emit event
        this.showStageModal = false;
      } catch (err) {
        console.error('Failed to set active schedule', err);
        this.toastError(err.response?.data?.message || 'Failed to set active schedule.');
      } finally {
        this.hideLoading();
        this.isTogglingDisabled = false;
      }
    },

    async unsetActiveSchedule() {
      this.showLoading();
      try {
        this.isTogglingDisabled = true;
        const payload = {
          academicYear: this.selectedAcademicYear,
          semester: this.selectedSemester,
        };
        await axios.post(`/unset-active-schedule`, payload);
        this.toastSuccess("Active schedule has been deactivated");
        emitter.emit('schedule-updated');
      } catch (err) {
        console.error(err);
        this.toastError("Failed to deactivate active schedule");
      } finally {
        this.isTogglingDisabled = false;
        this.hideLoading();
      }
    },

    openArchiveDrawer() {
      this.showArchiveDrawer = true;
    },

    async loadArchives() {
      try {
        const res = await axios.get('/archives');
        this.archives = res.data;
      } catch (err) {
        console.error('Failed to load archives', err);
        this.toastError('Could not load archives.');
      }
    },

    async archiveCurrentBatch() {
      if (this.isSelectedActive()) {
        this.toastError("Cannot archive an Active Schedule");
        return;
      }
      const ay = this.selectedAcademicYear;
      const sem = this.selectedSemester;
      this.confirmMessage = `Are you sure you want to archive the schedule for ${ay} – ${sem}?`;
      this.confirmOpen = true;
      this.confirmAction = async () => {
        this.confirmOpen = false;
        this.showLoading();
        try {
          if (!this.latestSchedule.length) {
            this.toastError('No schedule data to archive.');
            return;
          }
          const batchId = this.latestSchedule[0].batch_id;
          const payload = {
            academicYear: this.selectedAcademicYear,
            semester: this.selectedSemester,
            batch_id: batchId,
          };
          await axios.post('/archive-batch', payload);
          this.toastSuccess('Current batch archived');
          emitter.emit('schedule-updated');
        } catch (err) {
          console.error(err);
          this.toastError(err.response?.data?.message || 'Failed to archive current batch');
        } finally {
          this.hideLoading();
        }
      };
    },

    async restoreArchive(archive) {
      this.confirmAction = async () => {
        this.showLoading();
        try {
          await axios.post(`/archives/${archive.academicYear}/${archive.semester}/${archive.batch_id}/restore`);
          this.toastSuccess(`Restored: ${archive.academicYear} – ${archive.semester}`);
          emitter.emit('schedule-updated');
        } catch (err) {
          console.error('Failed to restore archive', err);
          this.toastError(err.response?.data?.message || 'Failed to restore archive.');
        } finally {
          this.hideLoading();
          this.confirmOpen = false;
        }
      };
      this.confirmMessage = `Are you sure you want to restore the schedule for ${archive.academicYear} – ${archive.semester}? This will overwrite any existing schedules for this period.`;
      this.confirmOpen = true;
    },

    async deleteArchive(archive) {
      this.confirmAction = async () => {
        this.showLoading();
        try {
          await axios.delete(`/archives/${archive.academicYear}/${archive.semester}/${archive.batch_id}`);
          this.toastSuccess('Archive deleted.');
          emitter.emit('schedule-updated');
        } catch (err) {
          console.error('Failed to delete archive', err);
          this.toastError(err.response?.data?.message || 'Failed to delete archive.');
        } finally {
          this.hideLoading();
          this.confirmOpen = false;
        }
      };
      this.confirmMessage = `Are you sure you want to permanently delete the archived schedule for ${archive.academicYear} – ${archive.semester}?`;
      this.confirmOpen = true;
    },

    formatDate(d) {
      try { return new Date(d).toLocaleString(); } catch { return d; }
    },

    filterSchedules() {
      if (!this.latestSchedule.length) return;
      let rows = this.latestSchedule;

      if (this.selectedCourseSection) {
        rows = rows.filter(r => r.course_section === this.selectedCourseSection);
      }

      if (this.selectedFaculty) {
        rows = rows.filter(r => r.faculty === this.selectedFaculty);
      }

      this.processFacultyLoads(rows);
      this.processCourseSchedules(rows);
    },

    processFacultyLoads(rows = null) {
      const data = rows || this.latestSchedule || [];
      const grouped = {};
      data.forEach(s => {
        const facultyName = s.faculty || "Unassigned";
        if (!grouped[facultyName]) grouped[facultyName] = [];
        grouped[facultyName].push(s);
      });

      this.facultyLoads = Object.entries(grouped).map(([name, loads]) => ({
        name,
        loads,
        totalUnits: loads.reduce((sum, l) => sum + (parseFloat(l.units) || 0), 0),
      }));
    },

    processCourseSchedules(rows = null) {
      const data = rows || this.latestSchedule || [];
      const grouped = {};
      data.forEach(s => {
        const section = s.course_section || "Unassigned Section";
        if (!grouped[section]) grouped[section] = [];
        grouped[section].push(s);
      });

      this.courseSchedules = grouped;
    },

    goToExport() {
      this.$router.push('/export');
    },
    getSectionTotal(schedules) {
      if (!Array.isArray(schedules)) return 0;
      return schedules.reduce((sum, s) => sum + (parseFloat(s.units) || 0), 0);
    },

    executeConfirmAction() {
      if (this.confirmAction) {
        this.confirmAction();
      }
    },
  },
};
</script>


<style scoped>
.filters { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.filter-group { display:flex; align-items:center; gap:20px; }
.filters label { display:flex; flex-direction:row; align-items:center; gap:8px; font-size:13px; }
.filters select { padding:6px 10px; border:1px solid #ddd; border-radius:4px; background:white; min-width:120px; font-size: 13px; }
.actions { display:flex; gap:10px; }
.export-btn, .stage-btn, .archive-btn, .archives-open-btn, .detect-btn { padding:6px 12px; color:white; border:none; border-radius:4px; font-weight:600; cursor:pointer; font-size: 11px; text-align: center; }
.export-btn:hover, .stage-btn:hover, .archive-btn:hover, .archives-open-btn:hover, .detect-btn:hover { filter: brightness(1.2); }
.export-btn { background-color:#27ae60; }
.stage-btn { background-color:#2980b9; }
.archive-btn { background-color:#7c3aed; }
.archives-open-btn { background-color:#475569; }
.detect-btn { background-color: #f39c12; }
.export-btn:hover { background-color:#1e8449; }
.stage-btn:hover { background-color:#1f6391; }
.stage-btn.active { background-color:#1e3a8a; cursor: pointer; }
.stage-btn:disabled { opacity:.75; cursor:not-allowed; }
.view-toggle { display:flex; gap:10px; margin-bottom:20px; }
.view-toggle button { padding:10px 20px; border:1px solid #ddd; background:white; color:#333; cursor:pointer; border-radius:5px; font-weight:500; transition:background-color .2s, color .2s; font-size: 13px; }
.view-toggle button.active { background-color:#121212; color:white; }
.styled-table { width:100%; border-collapse:collapse; border-radius:12px; overflow:hidden; background:white; box-shadow:0 4px 12px rgba(0,0,0,.05); margin-bottom:30px; }
.styled-table th, .styled-table td { padding:10px 14px; border-bottom:1px solid #ddd; text-align:left; font-size: 13px; }
.styled-table th { background:#f5f5f5; font-weight:600; }
.faculty-section { margin-bottom:40px; }
.total-units { text-align:right; font-weight:600; margin-top:8px; color:#374151; font-size: 13px; }

.active-banner {
  background-color: #e8f8f5;
  color: #1e8449;
  padding: 10px 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 13px;
  font-weight: 500;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5000;
}
.modal {
  background: white;
  padding: 25px 30px;
  border-radius: 10px;
  width: 400px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,.2);
}
.modal-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 20px;
}
.modal-actions .confirm {
  background-color: #27ae60;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
}
.modal-actions .cancel {
  background-color: #ccc;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
}

/* Archive drawer */
.archive-drawer-backdrop { position: fixed; inset:0; background: rgba(0,0,0,0.4); display:flex; justify-content:flex-end; z-index:6000 }
.archive-drawer { width: 420px; background:white; height:100%; box-shadow: -8px 0 24px rgba(0,0,0,0.2); padding:16px; overflow:auto }
.archive-drawer-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px }
.archive-drawer-header h3 { margin:0 }
.archive-drawer-header .close { background:transparent; border:none; font-size:20px; cursor:pointer }
.archive-list { display:flex; flex-direction:column; gap:10px }
.archive-item { display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px }
.ai-title { font-weight:700 }
.ai-meta { font-size:12px; color:#64748b }
.ai-actions { display:flex; gap:8px }
.ai-actions .restore { background:#0ea5e9; color:white; border:none; border-radius:8px; padding:6px 10px; cursor:pointer }
.ai-actions .delete { background:#ef4444; color:white; border:none; border-radius:8px; padding:6px 10px; cursor:pointer }
.archive-empty { color:#64748b; padding:20px 0; text-align:center }
</style>