<template>
  <form @submit.prevent="saveOfficial">
    <div class="mb-3">
      <label class="form-label">NIP</label>
      <input
        type="text"
        class="form-control"
        placeholder="xxxxxxxx xxxxxx x xxx"
        v-model="official.nip"
      />
      <div class="form-text">Format : xxxxxxxx xxxxxx x xxx</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Nama Lengkap</label>
      <input
        type="text"
        class="form-control"
        placeholder="Nama Lengkap"
        v-model="official.name"
        required
      />
      <div class="form-text">Sertakan gelar</div>
    </div>
    <div class="row">
      <div class="mb-3 col-lg-6">
        <label class="form-label">Jabatan</label>
        <input
          type="text"
          class="form-control"
          placeholder="Bendahara"
          v-model="official.position"
          required
        />
      </div>
      <div class="mb-3 col-lg-6">
        <label class="form-label">Pangkat</label>
        <input
          type="text"
          class="form-control"
          placeholder="Penata Tingkat x (xxx/x)"
          v-model="official.rank"
        />
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Tanda Tangan</label>
      <input type="file" ref="file_upload" class="form-control" />
    </div>
    <button type="submit" class="btn btn-success">SIMPAN</button>
  </form>
</template>
<script>
import axios from "axios";
import Swal from "sweetalert2";
export default {
  name: "Officials",
  emits: ["on-save", "finish"],
  props: {
    default_id: {
      type: String,
      default: "",
    },
    default_nip: {
      type: String,
      default: "",
    },
    default_name: {
      type: String,
      default: "",
    },
    default_position: {
      type: String,
      default: "",
    },
    default_rank: {
      type: String,
      default: "",
    },
    default_signature: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      url: `${window.location.origin}/backend/officials`,
      official: {
        id: "",
        nip: "",
        name: "",
        position: "",
        rank: "",
        signature: "",
      },
    };
  },
  methods: {
    async saveOfficial() {
      let formdata = new FormData();
      let id = this.official.id;
      formdata.append("id", id);
      formdata.append("nip", this.official.nip);
      formdata.append("name", this.official.name);
      formdata.append("position", this.official.position);
      formdata.append("rank", this.official.rank);
      formdata.append("signature", this.$refs.file_upload.files[0]);
      this.$emit("on-save");
      if (id == "") {
        await axios
          .post(`${this.url}`, formdata)
          .then((response) => {
            Swal.fire({
              icon: "success",
              title: "Berhasil",
              text: response.data.message,
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              this.$emit("finish", response.data.data);
            })
          })
          .catch((e) => {
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: e.response.data,
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              this.$emit("finish");
            })
          });
      } else {
        await axios
          .put(`${this.url}/${this.official.id}`, formdata)
          .then((response) => {
            Swal.fire({
              icon: "success",
              title: "Berhasil",
              text: response.data.message,
              showConfirmButton: false,
              timer: 1500,
            }).then(async () => {
              await this.$emit("finish", response.data.data);
            })
          })
          .catch((e) => {
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: e.response.data,
              showConfirmButton: false,
              timer: 1500,
            }).then(async () => {
              await this.$emit("finish");
            })
          });
      }
      this.reset();
    },
    async reset() {
      this.official = {
        id: "",
        nip: "",
        name: "",
        position: "",
        rank: "",
        signature: "",
      };
      this.$refs.file_upload.value = ""
    },
  },
};
</script>