<template>
  <div class="error-log">

    <div class="filters">
      <label>
        Academic Year:
        <select v-model="selectedYear">
          <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
        </select>
      </label>

      <label>
        Semester:
        <select v-model="selectedSemester">
          <option v-for="sem in semesters" :key="sem" :value="sem">{{ sem }}</option>
        </select>
      </label>
    </div>

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
        <tr v-if="!errors.length">
          <td colspan="5" class="no-errors">✅ No errors detected</td>
        </tr>
      </tbody>
    </table>

    <button class="revalidate-btn" @click="refreshErrors">Re-Validate</button>
  </div>
</template>

<script>
import { errorStore } from "../assets/errorStore";

export default {
  name: "ErrorLog",
  data() {
    return {
      selectedYear: "2025-2026",
      selectedSemester: "1st Semester",
      academicYears: ["2024-2025", "2025-2026", "2026-2027"],
      semesters: ["1st Semester", "2nd Semester"],
    };
  },
  computed: {
    errors() {
      return errorStore.errors;
    },
  },
  created() {
    this.fetchErrors();
  },
  methods: {
    async fetchErrors() {
      try {
        const response = await fetch("/api/errors");
        if (!response.ok) throw new Error("Failed to fetch errors");
        const data = await response.json();
        errorStore.setErrors(data);
      } catch (error) {
        console.error(error);
        errorStore.setErrors([]);
      }
    },
    refreshErrors() {
      this.fetchErrors();
    },
    autoFix(error) {
      alert(`AI auto-fixing error: ${error.type}`);
      error.resolved = true;
    },
    manualFix(error) {
      alert(`Manual editing: ${error.type}`);
    }
  }
};
</script>

<style scoped>
.page-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
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

.filters {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.filters label {
  display: flex;
  flex-direction: row;
  align-items: center;
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

.no-errors {
  text-align: center;
  padding: 16px;
  font-style: italic;
  color: #2ecc71;
  font-weight: 600;
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

.revalidate-btn {
  background: #2ecc71;
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.revalidate-btn:hover {
  background: #27ae60;
}
</style>