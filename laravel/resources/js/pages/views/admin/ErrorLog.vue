<template>
  <div class="error-log">

    <div class="page-title">
      <h1>Error Log</h1>
      <button class="refresh-btn" @click="refreshErrors" :disabled="loading">{{ loading ? 'Refreshing...' : 'Refresh' }}</button>
    </div>

    <div class="filters">
      <div class="filter-group">
        <label>Academic Year:</label>
        <select v-model="selectedYear">
          <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Semester:</label>
        <select v-model="selectedSemester">
          <option v-for="sem in semesters" :key="sem" :value="sem">{{ sem }}</option>
        </select>
      </div>

      <button class="clear-filters-btn" @click="clearFilters">Clear Filters</button>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
    </div>
    <div v-else-if="errors.length > 0">
      <table class="styled-table">
        <thead>
          <tr>
            <th>Conflict Type</th>
            <th>Description</th>
            <th>Severity</th>
            <th>Solved</th>
            <th>Options</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="error in errors" :key="error.id">
            <td>{{ error.type }}</td>
            <td>{{ error.description }}</td>
            <td>{{ error.severity }}</td>
            <td :class="error.resolved ? 'solved-yes' : 'solved-no'">
              {{ error.resolved ? "Yes" : "No" }}
            </td>
            <td>
              <button class="action-btn auto" @click="autoFix(error)">Auto Fix</button>
              <button class="action-btn manual" @click="manualFix(error)">Manual Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="no-errors-container">
      ✅ No errors detected
    </div>

  </div>
</template>

<script>
import { errorStore } from "../../../assets/errorStore";

export default {
  name: "ErrorLog",
  emits: ['manual-fix'],
  data() {
    return {
      selectedYear: null,
      selectedSemester: null,
      academicYears: [],
      semesters: [],
      loading: false,
    };
  },
  computed: {
    errors() {
      return errorStore.errors;
    },
  },
  watch: {
    selectedYear() {
      this.fetchErrors();
    },
    selectedSemester() {
      this.fetchErrors();
    },
  },
  async created() {
    await this.fetchErrors();
    this.academicYears.sort().reverse(); // Sort years in descending order
    this.selectedYear = this.academicYears.length > 0 ? this.academicYears[0] : null;
    this.selectedSemester = this.semesters.length > 0 ? this.semesters[0] : null;
  },
  methods: {
    async fetchErrors() {
      this.loading = true;
      try {
        const params = new URLSearchParams();
        if (this.selectedYear) {
          params.append('academic_year', this.selectedYear);
        }
        if (this.selectedSemester) {
          params.append('semester', this.selectedSemester);
        }

        const response = await fetch(`/api/errors?${params}`);
        if (!response.ok) throw new Error("Failed to fetch errors");
        const data = await response.json();
        errorStore.setErrors(data.errors);
        this.academicYears = data.academicYears;
        this.semesters = data.semesters;
      } catch (error) {
        console.error(error);
        errorStore.setErrors([]);
      } finally {
        this.loading = false;
      }
    },
    refreshErrors() {
      this.fetchErrors();
    },
    async autoFix(error) {
      try {
        const response = await fetch(`/api/errors/${error.id}/fix`, { method: 'POST' });
        if (!response.ok) throw new Error('Failed to apply auto-fix');
        const fixedError = await response.json();
        errorStore.updateError(fixedError);
      } catch (err) {
        console.error('Auto-fix failed:', err);
        alert('Auto-fix failed. Please try again.');
      }
    },
    manualFix(error) {
      this.$emit('manual-fix', error);
    },
    clearFilters() {
      this.selectedYear = this.academicYears.length > 0 ? this.academicYears[0] : null;
      this.selectedSemester = this.semesters.length > 0 ? this.semesters[0] : null;
    }
  }
};
</script>

<style scoped>
.page-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.page-title h1 {
  font-size: 1.5rem;
  font-weight: 700;
}

.refresh-btn {
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 6px 10px;
  font-weight: bold;
  cursor: pointer;
}
.refresh-btn:hover {
  background: #c0392b;
}

.refresh-btn:disabled {
  background: #e0e0e0;
  cursor: not-allowed;
}

.filters {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  align-items: flex-end;
}

.filters label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 14px;
}

.filters select {
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  min-width: 120px;
}

.styled-table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 12px;
  overflow: hidden;
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  margin-bottom: 30px;
}

.styled-table th,
.styled-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #ddd;
  text-align: left;
}

.styled-table th {
  background: #f5f5f5;
  font-weight: 600;
}

.styled-table tr:last-child td {
  border-bottom: none;
}

.no-errors-container {
  text-align: center;
  padding: 40px;
  font-size: 1.2rem;
  color: #2ecc71;
  font-weight: 600;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.solved-yes {
  color: #27ae60;
  font-weight: bold;
}
.solved-no {
  color: #e74c3c;
  font-weight: bold;
}

.action-btn {
  padding: 6px 12px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  margin-right: 8px;
}
.action-btn.auto {
  background: #3498db;
  color: white;
}
.action-btn.auto:hover {
  background: #2980b9;
}
.action-btn.manual {
  background: #f39c12;
  color: white;
}
.action-btn.manual:hover {
  background: #d68910;
}

.clear-filters-btn {
  background: #95a5a6;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 6px 10px;
  font-weight: bold;
  cursor: pointer;
}

.clear-filters-btn:hover {
  background: #7f8c8d;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.radio-group {
  display: flex;
  gap: 16px;
  align-items: center;
}

.radio-group label {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border-left-color: #3498db;
  animation: spin 1s ease infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>