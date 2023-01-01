<template>
  <h4 class="fw-bold py-3 mb-4">Pejabat</h4>
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body">
          <ul class="nav nav-pills flex-column flex-md-row">
            <li class="nav-item">
              <span v-if="modal" class="nav-link active" @click="modal.show()"
                ><i class="bx bx-plus me-1" style=""></i> Tambah Pejabat</span
              >
            </li>
          </ul>
        </div>
        <hr class="my-0" />
        <div class="card-body">
          <custom-table
            ref="table"
            :thead="head"
            :url="url"
            v-slot="{ key, id, nip, name, position, rank, can_modified }"
          >
            <td>{{ key }}</td>
            <td>{{ nip }}</td>
            <td>{{ name }}</td>
            <td>{{ position }}</td>
            <td>{{ rank }}</td>
            <td v-if="can_modified">
              <div class="dropdown">
                <button
                  type="button"
                  class="btn p-0 dropdown-toggle hide-arrow"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu" style="">
                  <span
                    class="dropdown-item"
                    @click="
                      (official = { id, nip, name, position, rank }),
                        modal.show()
                    "
                    ><i class="bx bx-edit-alt me-1"></i> Ubah</span
                  >
                  <span class="dropdown-item" @click="deleteOfficial(id)"
                    ><i class="bx bx-trash me-1"></i> Hapus</span
                  >
                </div>
              </div>
            </td>
            <td v-else>
              <small class="text-warning"
                >Hanya bisa diubah dengan akun kecamatan</small
              >
            </td>
          </custom-table>
        </div>
      </div>
    </div>
  </div>
  <modal ref="modal" @on-hide="reset" :title="(official.id == '' ? 'Tambah' : 'Ubah') + 'Pejabat'" :button_close="true">
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
  </modal>
  <modal ref="spinner" :size="'sm'" :backdrop="'static'" :keyboard="false" :header="false">
    <div class="d-flex justify-content-between align-items-center flex-column">
      <div class="spinner-border spinner-border-lg text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <span>Loading ...</span>
    </div>
  </modal>
</template>
<script>
import CustomTable from "./Helpers/CustomTable.vue";
import Modal from "./Helpers/Modal.vue";
import FileUpload from "./Helpers/FileUpload.vue";
import axios from "axios";
import Swal from "sweetalert2";
export default {
  name: "Officials",
  components: {
    CustomTable,
    Modal,
    FileUpload,
  },
  data() {
    return {
      head: ["S.N", "NIP", "Nama", "Jabatan", "Pangkat"],
      url: `${window.location.origin}/backend/officials`,
      modal: null,
      official: {
        id: "",
        nip: "",
        name: "",
        position: "",
        rank: "",
        signature: "",
      },
      spinner: null,
    };
  },
  mounted() {
    this.modal = this.$refs.modal;
    this.spinner = this.$refs.spinner;
  },
  methods: {
    async saveOfficial() {
      let response = "";
      let formdata = new FormData();
      let id = this.official.id;
      formdata.append("id", id);
      formdata.append("nip", this.official.nip);
      formdata.append("name", this.official.name);
      formdata.append("position", this.official.position);
      formdata.append("rank", this.official.rank);
      formdata.append("signature", this.$refs.file_upload.files[0]);
      this.modal.hide();
      this.spinner.show();
      if (id == "") {
        const { data } = await axios.post(`${this.url}`, formdata);
        response = data;
      } else {
        const { data } = await axios.put(
          `${this.url}/${this.official.id}`,
          formdata
        );
        response = data;
      }
      this.spinner.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response,
        showConfirmButton: false,
        timer: 1500,
      });
      this.$refs.table.refresh();
    },
    deleteOfficial(id) {
      this.spinner.show();
      axios
        .delete(`${this.url}/${id}`)
        .then(({ message }) => {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: message,
            showConfirmButton: false,
            timer: 1500,
          }).then(() => {
            this.spinner.hide();
            this.$refs.table.refresh();
          })
        })
        .catch((e) => {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: "Pejabat gagal dihapus",
            showConfirmButton: false,
            timer: 1500,
          }).then(() => {
            this.spinner.hide();
          })
        });
    },
    async reset() {
      this.official.id = "";
      this.official.nip = "";
      this.official.name = "";
      this.official.position = "";
      this.official.rank = "";
    },
  },
};
</script>