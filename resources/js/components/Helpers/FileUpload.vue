<template>
  <div
    class="w-100 h-100 d-flex justify-content-center"
    style="background-color: #f5f5f9; min-height: 300px"
    ref="fileform"
    @dragenter="dragenter = true"
    @dragleave="dragenter = false"
  >
    <input type="file" @change="uploadFile" ref="inputfile" class="d-none" :accept="accepted_file_type.join(',')" />
    <div
      class="d-flex flex-column align-items-center justify-content-center w-100"
      v-if="!dragenter && files == null"
    >
      <span class="tf-icons bx bx-upload" style="font-size: 100px"></span>
      <button
        type="button"
        class="btn rounded-pill btn-primary d-flex align-items-center"
        @click="$refs.inputfile.click()"
      >
        <span class="tf-icons bx bx-download"></span>&nbsp; Pilih File
      </button>
      <p class="mt-2">Atau drag file kesini</p>
    </div>
    <div
      class="d-flex flex-column align-items-center justify-content-center w-100"
      v-else-if="dragenter"
    >
      Letakkan file disini
    </div>
    <div
      class="d-flex flex-column align-items-center w-100"
      style="min-height: 300px"
      v-else-if="files != null"
    >
      <button
        type="button"
        class="
          btn
          rounded-pill
          btn-warning
          d-flex
          align-items-center
          mt-3
          mb-2
          w-25
          justify-content-center
        "
        @click="(files = null), (preview = null)"
      >
        Ubah File
      </button>
      <iframe :src="preview" style="width: 100%; height: 100%"> </iframe>
    </div>
  </div>
</template>
<script>
import Swal from "sweetalert2";
export default {
  name: "FileUpload",
  props: {
    accepted_file_type: {
      type: Array,
      default: [],
    },
  },
  data() {
    return {
      dragAndDropCapable: false,
      dragenter: false,
      files: null,
      preview: null,
      uploadPercentage: 0,
    };
  },
  mounted() {
    this.dragAndDropCapable = this.determineDragAndDropCapable();

    if (this.dragAndDropCapable) {
      [
        "drag",
        "dragstart",
        "dragend",
        "dragover",
        "dragenter",
        "dragleave",
        "drop",
      ].forEach(
        function (evt) {
          this.$refs.fileform.addEventListener(
            evt,
            function (e) {
              e.preventDefault();
              e.stopPropagation();
            }.bind(this),
            false
          );
        }.bind(this)
      );

      this.$refs.fileform.addEventListener(
        "drop",
        function (e) {
          this.handleFileUploaded(e.dataTransfer.files[0]);
        }.bind(this)
      );
    }
  },
  methods: {
    determineDragAndDropCapable() {
      var div = document.createElement("div");

      return (
        ("draggable" in div || ("ondragstart" in div && "ondrop" in div)) &&
        "FormData" in window &&
        "FileReader" in window
      );
    },
    uploadFile(event) {
      if (event.target.files.length > 0) {
        this.handleFileUploaded(event.target.files[0]);
      }
    },
    handleFileUploaded(file) {
      this.dragenter = false;
      if (this.accepted_file_type.length < 1 || this.accepted_file_type.some((t) => t == file.type)) {
        this.files = file;
        this.preview = URL.createObjectURL(this.files);
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
  },
};
</script>