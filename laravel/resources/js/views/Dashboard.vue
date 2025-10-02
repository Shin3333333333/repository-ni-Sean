<template>
  <div class="dashboard">

    <!-- Top row: Graph + Scheduling Activities -->
    <div class="top-row">
      <!-- Faculty Load Overview Chart -->
      <div class="chart-card">
        <h3>Faculty Load Overview</h3>
        <canvas id="facultyChart"></canvas>
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
              <td>June 2025</td>
            </tr>
            <tr>
              <td>Last Conflict Detected</td>
              <td>May 2025</td>
            </tr>
            <tr>
              <td>Last Conflict Resolved</td>
              <td>June 2025</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Stats Counters -->
    <div class="stats-row">
      <div class="stat-card">
        <h4>Subjects Offered</h4>
        <p>76</p>
      </div>
      <div class="stat-card">
        <h4>Faculty Members</h4>
        <p>7</p>
      </div>
      <div class="stat-card">
        <h4>Total Rooms</h4>
        <p>6</p>
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
  computed: {
    conflicts() {
      return errorStore.unresolvedCount;
    },
    conflictsClass() {
      return this.conflicts > 0 ? "conflicts-red" : "conflicts-green";
    },
  },
  mounted() {
    const ctx = document.getElementById("facultyChart").getContext("2d");
    const colors = ["#3498db", "#e74c3c", "#2ecc71", "#9b59b6", "#f39c12"];

    new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["Prof. Smith", "Dr. Brown", "Ms. Johnson", "Mr. Lee"],
        datasets: [
          {
            label: "Load (hours)",
            data: [12, 8, 15, 10],
            backgroundColor: colors,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
      },
    });
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

.chart-card {
  flex: 2;
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
