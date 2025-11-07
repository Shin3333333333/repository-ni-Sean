<template>
  <div v-if="show" class="modal-overlay" @click="close">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Curriculums</h3>
      </div>

      <div class="table-wrap">
        <table class="styled-table">
          <thead>
            <tr>
              <th style="width:60%">Name</th>
              <th style="width:20%">Created</th>
              <th style="width:20%">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="curr in curriculums" :key="curr.id">
              <td>
                <template v-if="editId === curr.id">
                  <input v-model="editName" type="text" style="width:100%" />
                </template>
                <template v-else>
                  {{ curr.name }}
                </template>
              </td>
              <td>{{ formatDate(curr.created_at) }}</td>
              <td>
                <template v-if="editId === curr.id">
                  <button class="btn btn-primary" @click="save(curr)">Save</button>
                  <button class="btn btn-cancel" @click="cancelEdit">Cancel</button>
                </template>
                <template v-else>
                  <button class="btn" @click="startEdit(curr)">Edit</button>
                  <button class="btn delete" @click="remove(curr)">Delete</button>
                </template>
              </td>
            </tr>
            <tr v-if="curriculums.length === 0">
              <td colspan="3" style="text-align:center;color:#666">No curriculums found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="modal-buttons flex justify-end gap-2 mt-4">
        <button type="button" class="btn btn-cancel" @click="close">Close</button>
      </div>
      <ConfirmModal
        :show="confirmOpen"
        :message="confirmMessage"
        @cancel="confirmOpen = false"
        @confirm="confirmAction && confirmAction()"
      />
    </div>
  </div>
</template>

<script>
import api from '../../../../axios';
import ConfirmModal from '../../../../components/ConfirmModal.vue';
import { useToast } from '../../../../composables/useToast';
import { useLoading } from '../../../../composables/useLoading';

export default {
  name: 'CurriculumListModal',
  components: { ConfirmModal },
  props: { show: Boolean },
  emits: ['update:show', 'changed'],
  data() {
    return {
      curriculums: [],
      editId: null,
      editName: '',
      confirmOpen: false,
      confirmMessage: '',
      confirmAction: null,
    };
  },
  setup() {
    const { success, error } = useToast();
    const { show, hide } = useLoading();
    return { success, error, showLoading: show, hideLoading: hide };
  },
  watch: {
    show: {
      immediate: true,
      handler(val) {
        if (val) this.fetch();
      }
    }
  },
  methods: {
    async fetch() {
      this.showLoading();
      try {
        const res = await api.get('/curriculums');
        const data = res.data && res.data.data !== undefined ? res.data.data : res.data;
        this.curriculums = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error('Failed to load curriculums', e);
        this.curriculums = [];
      } finally {
        this.hideLoading();
      }
    },
    formatDate(d) {
      if (!d) return '';
      try { return new Date(d).toLocaleDateString(); } catch { return d; }
    },
    close() {
      this.$emit('update:show', false);
      this.editId = null;
      this.editName = '';
    },
    startEdit(curr) {
      this.editId = curr.id;
      this.editName = curr.name;
    },
    cancelEdit() {
      this.editId = null;
      this.editName = '';
    },
    async save(curr) {
      if (!this.editName || !curr?.id) return;
      this.showLoading();
      try {
        const payload = { name: this.editName };
        await api.put(`/curriculums/${curr.id}`, payload);
        await this.fetch();
        this.$emit('changed');
        this.success('Curriculum updated');
      } catch (e) {
        console.error('Failed to update curriculum', e);
        this.error('Failed to update curriculum');
      } finally {
        this.hideLoading();
        this.cancelEdit();
      }
    },
    async remove(curr) {
      if (!curr?.id) return;
      this.confirmMessage = 'Delete this curriculum? This cannot be undone.';
      this.confirmOpen = true;
      this.confirmAction = async () => {
        this.confirmOpen = false;
        this.showLoading();
        try {
          await api.delete(`/curriculums/${curr.id}`);
          await this.fetch();
          this.$emit('changed');
          this.success('Curriculum deleted');
        } catch (e) {
          console.error('Failed to delete curriculum', e);
          this.error('Failed to delete curriculum');
        } finally {
          this.hideLoading();
        }
      };
    },
  },
};
</script>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center; z-index: 9999 !important; }
.modal-content { position: relative; background: white; border-radius: 1rem; padding: 1.5rem; width: 90vw; max-width: 1100px; height: 85vh; overflow: hidden; z-index: 10000 !important; box-shadow: 0 20px 45px rgba(2,6,23,0.25), 0 8px 20px rgba(2,6,23,0.15); border: 1px solid #e5e7eb; }
.modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.modal-title { margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a; }
.table-wrap { overflow: auto; max-height: calc(85vh - 120px); /* header + buttons spacing */ }
.styled-table { width: 100%; border-collapse: collapse; }
.styled-table th, .styled-table td { border-bottom: 1px solid #eef2f7; padding: 10px 12px; text-align: left; }
.btn { padding: 6px 10px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; cursor: pointer; }
.btn + .btn { margin-left: 8px; }
.btn-cancel { background: #ccc; border: none; }
.btn-primary { background: #3b82f6; color: #fff; border: none; }
.btn.delete { background: #dc3545; color: #fff; border: none; }

@media (max-width: 768px) {
  .modal-content { width: 95vw; height: 85vh; }
  .table-wrap { max-height: calc(85vh - 130px); }
}
</style>