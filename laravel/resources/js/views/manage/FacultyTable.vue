<template>
  <table class="styled-table">
    <thead>
      <tr>
        <th>Faculty</th>
        <th>Type</th>
        <th>Department</th>
        <th>Max Load</th>
        <th>Time Unavailable</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="faculty in facultyList" :key="faculty.id">
        <td>{{ faculty.name }}</td>
        <td>{{ faculty.type }}</td>
        <td>{{ faculty.department }}</td>
        <td>{{ faculty.max_load }}</td>
        <td>{{ faculty.time_unavailable }}</td>
        <td>
          <span :class="faculty.status === 'Active' ? 'status-yes' : 'status-no'">
            {{ faculty.status }}
          </span>
        </td>
        <td>
          <button @click="$emit('edit', faculty)">Edit</button>
          <button class="delete-btn" @click="$emit('delete', faculty)">Delete</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: "FacultyTable",
  props: {
    facultyList: Array
  }
};
</script>
