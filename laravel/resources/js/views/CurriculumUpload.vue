<template>
  <div style="padding: 2rem;">
    <h2>Upload Curriculum (Test)</h2>

    <input type="file" @change="handleFileUpload" />
    
    <div v-if="subjects.length">
      <h3>Extracted Subjects:</h3>
      <ul>
        <li v-for="subject in subjects" :key="subject">{{ subject }}</li>
      </ul>
    </div>

    <button @click="insertSubjects" :disabled="!subjects.length">Insert Subjects to DB</button>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      subjects: [],
      file: null,
    };
  },
  methods: {
    handleFileUpload(event) {
      this.file = event.target.files[0];
      if (!this.file) return;

      // For demo, we'll just create dummy subjects
      // In real test, read CSV or Excel using FileReader or SheetJS
      this.subjects = ["Math", "Science", "History"];
      console.log("File uploaded:", this.file.name);
    },
    insertSubjects() {
      if (!this.subjects.length) return;

      axios.post('/api/subjects', { subjects: this.subjects })
        .then(res => {
          alert("Subjects inserted successfully!");
          console.log(res.data);
          this.subjects = [];
          this.file = null;
        })
        .catch(err => {
          console.error("Failed to insert subjects:", err.response?.data || err);
        });
    }
  }
};
</script>
