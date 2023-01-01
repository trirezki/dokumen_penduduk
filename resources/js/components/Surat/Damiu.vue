<template>
  <h4 class="fw-bold py-3 mb-4">SURAT IZIN DEPOT AIR MINUM ISI ULANG</h4>
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body" v-if="user?.type == 'desa'">
          <ul class="nav nav-pills flex-column flex-md-row">
            <li class="nav-item">
              <span v-if="modal" class="nav-link active" @click="modal.show()"><i class="bx bx-plus me-1" style=""></i>
                Tambah Surat
                DAMIU</span>
            </li>
          </ul>
        </div>
        <hr class="my-0" />
        <div class="card-body">
          <custom-table ref="table" :thead="head" :url="url" v-slot="data">
            <td>{{ data.key }}</td>
            <td>{{ data.reference_number }}</td>
            <td>{{ data.resident.nik }}</td>
            <td>{{ data.resident.name }}</td>
            <td>{{ data.business }}</td>
            <td>
              <button type="button" class="btn btn-info ms-3" @click="openNewTab(data.file_pendukung)">
                Lihat File
              </button>
            </td>
            <td>
              <span class="badge" :class="data.is_verified ? 'bg-success' : 'bg-danger'">
                {{ data.is_verified ? "VERIFIED" : "UNVERIFIED" }}</span>
            </td>
            <td>
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"
                  aria-expanded="false">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu" style="">
                  <span class="dropdown-item" @click="openModalVerification(data)"
                    v-if="!data.is_verified && data.can_verified"><i class="bx bx-check me-1"></i> Verifikasi</span>
                  <span class="dropdown-item" @click="print(data.id)" v-if="data.is_verified"><i
                      class="bx bx-print me-1"></i> Print Surat</span>
                  <span class="dropdown-item" @click="(letter = data), modal.show()" v-if="data.can_modified"><i
                      class="bx bx-edit-alt me-1"></i> Ubah</span>
                  <span class="dropdown-item" @click="deleteLetter(data.id)" v-if="data.can_modified"><i
                      class="bx bx-trash me-1"></i> Hapus</span>
                </div>
              </div>
            </td>
          </custom-table>
        </div>
      </div>
    </div>
  </div>
  <modal ref="modal" :size="'lg'" @on-hide="reset" :title="(letter.id == '' ? 'Tambah' : 'Ubah') + ' Surat DAMIU'"
    :button_close="true">
    <form @submit.prevent="saveLetter">
      <div class="mb-3">
        <label class="form-label">Nomor Surat</label>
        <span class="form-control disabled">{{ preview }}</span>
      </div>
      <div class="mb-3">
        <label class="form-label">Data Pemohon</label>
        <search-input ref="search_input_resident" :url="origin + '/backend/resident-search'" v-model="letter.resident"
          placeholder="Cari ...">
          <template v-slot="{ data }">
            <small class="text-secondary">{{ data.nik }}</small>
            <p class="mb-0">{{ data.name }}</p>
            <hr class="m-0" />
            <p class="mb-0">{{ data.address }}</p>
          </template>
          <template #default_first>
            <p class="text-center text-primary m-0" style="cursor: pointer" @click="
  modal_add_resident.show(),
  ($refs.search_input_resident.focus = false)
">
              + Tambah Data Penduduk
            </p>
          </template>
        </search-input>
        <div class="table-responsive no-min-height" v-if="letter.resident != null">
          <table class="table">
            <thead>
              <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Alamat</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{{ letter.resident.nik }}</td>
                <td>{{ letter.resident.name }}</td>
                <td>{{ letter.resident.address }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-danger text-center mt-2" v-else>
          Belum ada data pemohon
        </p>
      </div>
      <div class="mb-3">
        <label class="form-label">Nama DAMIU</label>
        <input type="text" class="form-control" placeholder="PT. XXX XXX XXX" v-model="letter.damiu_name" />
      </div>
      <div class="mb-3">
        <label class="form-label">Alamat DAMIU</label>
        <textarea class="form-control" placeholder="JL. XXX XXX XXX" v-model="letter.damiu_address"></textarea>
      </div>
      <div class="row">
        <div class="mb-3 col-lg-6">
          <label class="form-label">Jenis Usaha</label>
          <input type="text" class="form-control" placeholder="Toko Bangunan" v-model="letter.business" />
        </div>
        <div class="mb-3 col-lg-6">
          <label class="form-label">Masa Berlaku</label>
          <div class="input-group mb-3">
            <input type="number" class="form-control" placeholder="Masa Berlaku" v-model="letter.validity_period">
            <span class="input-group-text" id="basic-addon2">Tahun</span>
          </div>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">File Pendukung</label>
        <default-file-upload v-model="file_pendukung" v-model:preview="letter.file_pendukung"
          :accepted_file_type="['zip', 'rar', 'jpg', 'jpeg', 'png', 'pdf']" />
      </div>
      <div class="mb-3 d-flex justify-content-end">
        <button type="submit" class="btn btn-success px-5">SIMPAN</button>
      </div>
    </form>
  </modal>
  <modal ref="modal_verification" :size="'lg'" @on-hide="reset" :title="'Verifikasi Surat DAMIU'" :close_button="true">
    <verification-letter :url="url" :letter="letter" @loading="spinner.show()" @loaded="spinner.hide()"
      @success="reset(), $refs.table.refresh()" :can_verified="letter.official != null">
      <template #penandatangan>
        <div class="mb-3" v-if="letter.official != null">
          <label class="form-label">Pejabat Penandatangan Yang Tersimpan</label>
          <div class="form-control d-flex align-items-center justify-content-between">
            <span>{{ letter.official.nip }} - {{ letter.official.name }}</span>
          </div>
        </div>
        <form method="POST" @submit.prevent="savePenandatangan" v-if="user.head_of_institution != null">
          <div class="mb-3">
            <label class="form-label">Camat</label>
            <div class="form-control d-flex align-items-center justify-content-between">
              <span>{{ user.head_of_institution.nip }} - {{ user.head_of_institution.name }}</span>
            </div>
          </div>
          <div class="mt-2">
            <button type="submit" class="btn btn-primary me-2">
              Ubah Penandatangan
            </button>
          </div>
        </form>
        <div v-else class="text-center">
          Instansi ini belum memilih {{ user.type == 'kecamatan' ? 'Camat' : 'Kepala Desa' }} di halaman <router-link
            :to="{ name: 'Institution' }">Profile Instansi</router-link>
        </div>
      </template>
    </verification-letter>
  </modal>
  <modal ref="spinner" :size="'sm'" :backdrop="'static'" :keyboard="false" :header="false">
    <div class="d-flex justify-content-between align-items-center flex-column">
      <div class="spinner-border spinner-border-lg text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <span>Loading ...</span>
    </div>
  </modal>
  <modal ref="modal_add_resident" :title="'Tambah Data Penduduk'" :button_close="true">
    <resident-form @on-save="modal_add_resident.hide(), spinner.show()" @finish="savedResident"></resident-form>
  </modal>
  <modal ref="modal_form_official" :title="'Tambah Penandatangan'" :button_close="true">
    <official-form @on-save="modal_form_official.hide(), spinner.show()" @finish="savedOfficial"></official-form>
  </modal>
</template>
<script>
import CustomTable from "../Helpers/CustomTable.vue";
import Modal from "../Helpers/Modal.vue";
import DefaultFileUpload from "../Helpers/DefaultFileUpload.vue";
import SearchInput from "../Helpers/SearchInput.vue";
import FileUpload from "../Helpers/FileUpload.vue";
import ResidentForm from "../ResidentForm.vue";
import OfficialForm from "../OfficialForm.vue";
import VerificationLetter from "./VerificationLetter.vue";
import axios from "axios";
import Swal from "sweetalert2";
import { mapState } from 'pinia'
import { useUserStore } from '../../store/user'

export default {
  name: "Damiu",
  components: {
    CustomTable,
    Modal,
    DefaultFileUpload,
    SearchInput,
    FileUpload,
    ResidentForm,
    OfficialForm,
    VerificationLetter,
  },
  data() {
    return {
      origin: window.location.origin,
      head: [
        "S.N",
        "Nomor Surat",
        "NIK Pemohon",
        "Nama Pemohon",
        "Jenis Usaha",
        "File Pendukung",
        "Status",
      ],
      url: `${origin}/backend/damiu-letters`,
      modal: null,
      modal_add_resident: null,
      modal_form_official: null,
      modal_verification: null,
      format_reference_number: {
        prefix: "",
        suffix: "",
      },
      file_pendukung: null,
      letter: {
        id: "",
        reference_number: "",
        prefix: "",
        suffix: "",
        damiu_name: "",
        damiu_address: "",
        business: "",
        resident: {
          id: "",
          nik: "",
          name: "",
          address: "",
        },
        official: null,
        validity_period: "",
        file_pendukung: null,
        is_verified: false,
      },
      spinner: null,
    };
  },
  computed: {
    ...mapState(useUserStore, ['user']),
    preview() {
      if (this.letter.id != "") {
        return this.letter.reference_number;
      }

      let pr = `${this.format_reference_number.prefix}XXX${this.format_reference_number.suffix}`;
      pr = pr.replaceAll(
        "{tgl}",
        String(new Date().getDate()).padStart(2, "0")
      );
      pr = pr.replaceAll(
        "{bln}",
        String(new Date().getMonth()).padStart(2, "0")
      );
      pr = pr.replaceAll("{thn}", new Date().getFullYear());

      return pr;
    },
  },
  mounted() {
    this.modal = this.$refs.modal;
    this.modal_add_resident = this.$refs.modal_add_resident;
    this.modal_form_official = this.$refs.modal_form_official;
    this.modal_verification = this.$refs.modal_verification;
    this.spinner = this.$refs.spinner;

    axios.get(`${this.url}/reference-number`).then((response) => {
      this.format_reference_number = response.data;
    });
  },
  methods: {
    autoFillResident(data) {
      this.letter.resident = data;
    },
    selectedOfficial(data) {
      this.letter.official = data;
      // this.letter.resident = data;
    },
    resetOfficial() {
      this.letter.official = null;
    },
    async saveLetter() {
      let response = "";
      let payload = new FormData();
      let letter_id = this.letter.id;
      try {
        this.spinner.show();
        payload.append('damiu_name', this.letter.damiu_name);
        payload.append('damiu_address', this.letter.damiu_address);
        payload.append('business', this.letter.business);
        payload.append('resident_id', this.letter.resident.id);
        payload.append('validity_period', this.letter.validity_period);
        payload.append('file_pendukung', this.file_pendukung ?? "");
        if (letter_id == "") {
          if (!this.file_pendukung) {
            throw "file_pendukung_is_null";
          }
          const { data } = await axios.post(`${this.url}`, payload);
          response = data;
        } else {
          const { data } = await axios.post(`${this.url}/${letter_id}`, payload);
          response = data;
        }
        await Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response,
          showConfirmButton: false,
          timer: 1500,
        });
        this.modal.hide();
        this.$refs.table.refresh();
      } catch (err) {
        switch (err) {
          case 'file_pendukung_is_null':
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "File pendukung wajib dilampirkan !",
              showConfirmButton: false,
              timer: 1500,
            });
            break;
          default:
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Gagal menyimpan surat !",
              showConfirmButton: false,
              timer: 1500,
            });
            break;
        }
      } finally {
        this.spinner.hide();
      }
    },
    deleteLetter(id) {
      this.spinner.show();
      axios
        .delete(`${this.url}/${id}`)
        .then(({ data }) => {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: data,
            showConfirmButton: false,
            timer: 1500,
          }).then(async () => {
            await this.spinner.hide();
            await this.$refs.table.refresh();
          })
        })
        .catch((e) => {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: "Surat gagal dihapus",
            showConfirmButton: false,
            timer: 1500,
          }).then(async () => {
            await this.spinner.hide();
          })
        });
    },
    async reset() {
      this.letter = {
        id: "",
        reference_number: "",
        prefix: "",
        suffix: "",
        damiu_name: "",
        damiu_address: "",
        business: "",
        resident: {
          id: "",
          nik: "",
          name: "",
          address: "",
        },
        official: null,
        validity_period: "",
        file_pendukung: null,
        is_verified: false,
      };
      this.file_pendukung = null;
    },
    savedResident(data) {
      this.spinner.hide();
      if (data) {
        this.letter.resident = data;
      }
    },
    savedOfficial(data) {
      this.spinner.hide();
      if (data) {
        this.letter.official = data;
      }
    },
    openModalVerification(letter) {
      this.letter = letter;
      this.modal_verification.show();
    },
    savePenandatangan() {
      if (this.letter.id != '') {
        this.spinner.show();
        axios.post(`${this.url}/penandatangan/${this.letter.id}`).then(response => {
          this.letter.official = response.data;
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: "Berhasil menyimpan penandatangan",
            showConfirmButton: false,
            timer: 1500,
          }).then(() => {
            this.spinner.hide();
          })
        })
      }
    },
    async print(id) {
      // await this.spinner.show();
      await axios({
        url: `${this.url}/download/${id}`,
        method: "GET",
        responseType: "blob",
      }).then((response) => {
        const href = URL.createObjectURL(response.data);
        let iframe = document.createElement("iframe"); //load content in an iframe to print later
        document.body.appendChild(iframe);

        iframe.style.display = "none";
        iframe.src = href;
        this.spinner.hide();
        iframe.onload = function () {
          setTimeout(function () {
            iframe.focus();
            iframe.contentWindow.print();
          }, 1);
        };
      });
      // await this.spinner.hide();
    },
    openNewTab(link) {
      window.open(link);
    },
  },
};
</script>
<style scoped>
.disabled {
  background-color: #eceef1;
  opacity: 1;
}
</style>