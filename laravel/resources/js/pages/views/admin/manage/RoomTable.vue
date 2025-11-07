<template>
  <table class="styled-table">
    <thead>
      <tr>
        <th>Room</th>
        <th>Type</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="room in roomList" :key="room.id">
        <td>{{ room.name }}</td>
        <td>{{ room.type }}</td>
        <td>
          <span :class="room.status === 'Available' ? 'status-yes' : 'status-no'">
            {{ room.status }}
          </span>
        </td>
        <td>
          <button @click="$emit('edit', room)">Edit</button>
          <button class="delete-btn" @click="$emit('delete', room)">Delete</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: "RoomTable",
  props: {
    roomList: {
      type: Array,
      required: true
    }
  },

};
</script>

<style scoped>
@import './table-styles.css';
</style>