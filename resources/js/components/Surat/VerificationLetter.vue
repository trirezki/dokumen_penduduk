<template>
  <div>
    <div>
      <h5 class="text-primary">STEP 1</h5>
      <slot name="penandatangan"></slot>
    </div>
    <hr />
    <div v-if="can_verified">
      <div>
        <h5 class="text-primary">STEP 2</h5>
        <button type="button" class="btn rounded-pill btn-info d-flex align-items-center" @click="download">
          <span class="tf-icons bx bx-download"></span>&nbsp; Download Surat
        </button>
        <small>*Catatan : Surat yang belum di stempel</small>
      </div>
      <hr />
      <div class="mt-4">
        <h5 class="text-primary">STEP 3</h5>
        <div class="accordion" id="accordionExample">
          <div class="card accordion-item active">
            <h2 class="accordion-header" id="headingOne">
              <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target=""
                aria-expanded="true" aria-controls="accordionOne">
                Upload Surat Yang Sudah Di Stempel Basah
              </button>
            </h2>
            <div id="accordionOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
              data-bs-parent="#accordionExample">
              <div class="accordion-body p-0">
                <file-upload ref="verified_file" :accepted_file_type="['application/pdf']"></file-upload>
              </div>
            </div>
          </div>
        </div>
      </div>
      <hr />
      <div class="mt-4">
        <h5 class="text-primary">STEP 4</h5>
        <button type="button" class="
          btn
          rounded-pill
          btn-success
          d-flex
          align-items-center
          justify-content-center
          w-100
        " @click="verified">
          <span class="tf-icons bx bx-save"></span>&nbsp; SIMPAN SURAT
          TERVERIFIKASI
        </button>
      </div>
    </div>
  </div>
</template>
<script>
import FileUpload from "../Helpers/FileUpload.vue";
import axios from "axios";
import Swal from "sweetalert2";
export default {
  name: "VerificationLetter",
  components: {
    FileUpload,
  },
  props: {
    letter: Object,
    url: String,
    can_verified: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["loading", "loaded", "success", "error"],
  mounted() {
    if (!this.url) {
      alert("Props url missing in VerificationLetter component");
    }
  },
  methods: {
    async download() {
      if (!this.url) {
        return alert("Props url missing in VerificationLetter component");
      }
      await this.$emit("loading");
      if (this.letter.id != "") {
        await axios({
          url: `${this.url}/download/${this.letter.id}`,
          method: "GET",
          responseType: "blob",
        }).then(async (response) => {
          await this.$emit("loaded");
          const href = URL.createObjectURL(response.data);

          // create "a" HTLM element with href to file & click
          const link = document.createElement("a");
          link.href = href;
          link.setAttribute("download", this.letter.reference_number + ".docx"); //or any other extension
          document.body.appendChild(link);
          link.click();


          document.body.removeChild(link);
          URL.revokeObjectURL(href);
        });
      }
    },
    async verified() {
      if (!this.url) {
        return alert("Props url missing in VerificiationLetter component");
      }
      let letter = this.letter;
      await this.$emit("loading");
      if (letter.id != "") {
        let formdata = new FormData();
        formdata.append("verified_file", this.$refs.verified_file.files);
        await axios
          .post(`${this.url}/verification/${letter.id}`, formdata)
          .then(async (response) => {
            await Swal.fire({
              icon: "success",
              title: "Berhasil",
              text: response.data,
              showConfirmButton: false,
              timer: 1500,
            }).then(async () => {
              await this.$emit("loaded");
              await this.$emit("success");
            })
          })
          .catch(async (e) => {
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Surat gagal diverifikasi",
              showConfirmButton: false,
              timer: 1500,
            }).then(async () => {
              await this.$emit("loaded");
              await this.$emit("error");
            })
          });
      }
    },
  },
};
</script>