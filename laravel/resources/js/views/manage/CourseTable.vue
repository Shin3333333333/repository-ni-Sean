<template>
  <table class="styled-table">
    <thead>
      <tr>
        <th>Course/Section</th>
        <th>Year Level</th>
        <th>Students</th>
        <th>Curriculum</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="course in courseList" :key="course.id">
        <td>{{ course.name }}</td>
        <td>{{ course.year }}</td>
        <td>{{ course.students }}</td>
        <td>{{ course.curriculum ? course.curriculum.name : '-' }}</td>
        <td>
          <button @click="$emit('edit', course)">Edit</button>
          <button class="delete-btn" @click="$emit('delete', course)">Delete</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: "CourseTable",
  props: {
    courseList: {
      type: Array,
      required: true
    }
  }
};
</script>
