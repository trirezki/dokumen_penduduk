<template>
  <div>
    <input type="file" @change="uploadFile" ref="inputfile" class="d-none" :accept="accepted_file_type.join(',')" />
    <button type="button" class="btn btn-primary" @click="$refs.inputfile.click()">
      Upload File
    </button>
    <button type="button" class="btn btn-info ms-3" @click="openNewTab(preview_, is_download)"
      v-if="value != null || (preview_ != '' && preview_ != null)">
      Lihat File
    </button>
  </div>
</template>
<script>
import Swal from "sweetalert2";
export default {
  name: "FileUpload",
  emits: ["update:modelValue", "update:preview"],
  props: {
    modelValue: Object | String,
    preview: String,
    accepted_file_type: {
      type: Array,
      default: [],
    },
  },
  data() {
    return {
      is_download: false,
    }
  },
  computed: {
    value: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
    preview_: {
      get() {
        return this.preview;
      },
      set(preview) {
        this.$emit("update:preview", preview);
      },
    },
  },
  methods: {
    getExt(name) {
      const lastDot = name.lastIndexOf('.');
      // const fileName = name.substring(0, lastDot);
      const ext = name.substring(lastDot + 1);
      return ext;
    },
    uploadFile(event) {
      if (event.target.files.length > 0) {
        this.handleFileUploaded((event.srcElement || event.target).files[0]);
      }
    },
    handleFileUploaded(file) {
      if (
        this.accepted_file_type.length < 1 ||
        this.accepted_file_type.some((t) => t == this.getExt(file.name))
      ) {
        if (this.getExt(file.name) == "rar" || this.getExt(file.name) == "zip") {
          this.is_download = true;
        }
        this.value = file;
        this.$refs.inputfile.value = null;
        this.preview_ = URL.createObjectURL(file);
        // this.convertToBase64(file);
      } else {
        Swal.fire({
          icon: "error",
          title: "Upload File",
          text: "Format file tidak sesuai!",
          showConfirmButton: false,
          timer: 1500,
        });
      }
    },
    openNewTab(link, is_download) {
      if (is_download) {
        let a = document.createElement('a');
        a.setAttribute('href', link);
        a.setAttribute('download', this.value.name);
        a.click();
      } else {
        window.open(link);
      }
    },
  },
};
</script>