<template>
  <div class="create-page">
    <nav class="top-nav">
      <button :class="{ active: currentView === 'create' }" @click="currentView = 'create'">Create Schedule</button>
      <button :class="{ active: currentView === 'pending' }" @click="currentView = 'pending'">Pending Schedule</button>
    </nav>

    <CreatePanel v-if="currentView === 'create'" />
    <PendingPanel v-else />
  </div>
</template>

<script>
import CreatePanel from "./panels/CreatePanel.vue";
import PendingPanel from "./panels/PendingPanel.vue";

export default {
  components: { CreatePanel, PendingPanel },
  data() {
    return { currentView: "create" };
  },
};
</script>


<style>
/* ✅ Simple nav styling */
.top-nav {
  display: flex;
  justify-content: flex-start;
  gap: 1rem;
  background-color: #f2f2f2;
  padding: 10px 20px;
  border-bottom: 1px solid #ccc;
}
.top-nav button {
  background: none;
  border: none;
  font-weight: 600;
  cursor: pointer;
  padding: 8px 12px;
}
.top-nav button.active {
  border-bottom: 2px solid #007bff;
  color: #007bff;
}
.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.edit-btn,
.finalize-btn,
.delete-btn {
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: white;
}

.edit-btn { background: #3498db; }
.finalize-btn { background: #2ecc71; }
.delete-btn { background: #e74c3c; }

.edit-btn:hover { background: #2980b9; }
.finalize-btn:hover { background: #27ae60; }
.delete-btn:hover { background: #c0392b; }

</style>
