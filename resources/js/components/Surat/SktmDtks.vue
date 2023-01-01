<template>
  <h4 class="fw-bold py-3 mb-4">
    SURAT KETERANGAN TIDAK MAMPU UNTUK DATA TERPADU KESEJAHTERAAN SOSIAL (DTKS)
  </h4>
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body" v-if="user.type == 'desa'">
          <ul class="nav nav-pills flex-column flex-md-row">
            <li class="nav-item">
              <span v-if="modal != null" class="nav-link active" @click="modal.show()"><i class="bx bx-plus me-1"
                  style=""></i>
                Tambah Surat SKTM
                Untuk DTKS</span>
            </li>
          </ul>
        </div>
        <hr class="my-0" />
        <div class="card-body">
          <custom-table ref="table" :thead="head" :url="url" v-slot="data">
            <td>{{ data.key }}</td>
            <td>{{ data.reference_number }}</td>
            <td>{{ data.residents[0].nik }}</td>
            <td>{{ data.residents[0].name }}</td>
            <td>
              <button type="button" class="btn btn-info ms-3" @click="openNewTab(data.kartu_keluarga)">
                Lihat Kartu Keluarga
              </button>
              <button type="button" class="btn btn-info ms-3" @click="openNewTab(data.surat_pengantar)">
                Lihat Surat Pengantar
              </button>
              <button v-if="data.file_arsip" type="button" class="btn btn-info ms-3"
                @click="openNewTab(data.file_arsip)">
                Lihat File Arsip
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
                  <span class="dropdown-item" @click="(letter = data), $refs.modal_file_arsip.show()"
                    v-if="data.is_verified && data.can_modified"><i class="bx bx-download me-1"></i> Upload Arsip</span>
                  <span class="dropdown-item" @click="letter = copy(data), modal.show()" v-if="data.can_modified"><i
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
  <modal ref="modal" :size="'xl'" @on-hide="reset"
    :title="(letter.id == '' ? 'Tambah' : 'Ubah') + ' Surat SKTM Untuk DTKS'" :close_button="true">
    <form @submit.prevent="saveLetter">
      <div class="mb-3">
        <label class="form-label">Nomor Surat</label>
        <span class="form-control disabled">{{ preview }}</span>
      </div>
      <div class="mb-3">
        <label class="form-label">Dasar Surat Point 1</label>
        <input type="text" class="form-control" placeholder="Dasar Surat Point 1" v-model="letter.dasar_1" />
      </div>
      <div class="mb-3">
        <label class="form-label">Dasar Surat Point 2</label>
        <input type="text" class="form-control" placeholder="Dasar Surat Point 2" v-model="letter.dasar_2" />
      </div>
      <div class="row">
        <div class="col-lg-6 mb-3">
          <label class="form-label">Surat Pengantar</label>
          <default-file-upload v-model="surat_pengantar_file" v-model:preview="letter.surat_pengantar" />
        </div>
        <div class="col-lg-6 mb-3">
          <label class="form-label">Kartu Keluarga</label>
          <default-file-upload v-model="kartu_keluarga_file" v-model:preview="letter.kartu_keluarga" />
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Penggunaan</label>
        <input type="text" class="form-control" placeholder="Penggunaan Surat" v-model="letter.used_as" />
      </div>
      <div class="mb-3">
        <label class="form-label">Data Pemohon</label>
        <search-input ref="search_input_resident" :url="origin + '/backend/resident-search'" v-model="letter.residents"
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
        <div class="table-responsive no-min-height" v-if="letter.residents.length > 0">
          <table class="table">
            <thead>
              <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Alamat</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(resident, key) in letter.residents" :class="key == 0 ? 'table-secondary' : ''">
                <td>{{ resident.nik }}</td>
                <td>{{ resident.name }}</td>
                <td>{{ resident.address }}</td>
                <td>
                  <i class="bx bx-trash text-danger" style="cursor:pointer;"
                    @click="letter.residents.splice(key, 1)"></i>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-danger text-center mt-2" v-else>
          Belum ada data pemohon
        </p>
      </div>
      <div class="mb-3">
        <label class="form-label">Pejabat Penandatangan Desa</label>
        <search-input placeholder="Cari Pejabat ..." ref="search_input_penandatangan_desa" v-model="penandatangan_desa"
          :url="origin + '/backend/officials'" v-if="letter.penandatangan_desa == null"
          @change="(data) => letter.penandatangan_desa = data">
          <template v-slot="{ data }">
            <small class="text-secondary">{{ data.nip }}</small>
            <p class="mb-0">{{ data.name }}</p>
            <hr class="m-0" />
            <p class="mb-0">{{ data.position }}</p>
          </template>
          <template #default_first>
            <p class="text-center text-primary m-0" style="cursor: pointer" @click="
  modal_form_penandatangan_desa.show(),
  ($refs.search_input_penandatangan_desa.focus = false)
">
              + Tambah Data Pejabat
            </p>
          </template>
        </search-input>
        <div v-else class="form-control d-flex align-items-center justify-content-between">
          <span>{{ letter.penandatangan_desa.nip }} - {{ letter.penandatangan_desa.name }}</span>
          <span @click="letter.penandatangan_desa = null" class="tf-icons bx bx-pencil" style="cursor: pointer"></span>
        </div>
      </div>
      <button type="submit" class="btn btn-success">SIMPAN</button>
    </form>
  </modal>
  <modal ref="modal_verification" :size="'lg'" @on-hide="reset" :title="'Verifikasi Surat SKTM Untuk DTKS'"
    :close_button="true">
    <verification-letter :url="url" :letter="letter" @loading="spinner.show()" @loaded="spinner.hide()"
      @success="reset(), $refs.table.refresh()" :can_verified="letter.penandatangan_kecamatan != null">
      <template #penandatangan>
        <form method="POST" @submit.prevent="savePenandatanganKecamatan">
          <div class="mb-3" v-if="letter.penandatangan_kecamatan != null">
            <label class="form-label">Pejabat Penandatangan Yang Tersimpan</label>
            <div class="form-control d-flex align-items-center justify-content-between">
              <span>{{ letter.penandatangan_kecamatan.nip }} - {{ letter.penandatangan_kecamatan.name }}</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Pejabat Penandatangan</label>
            <search-input placeholder="Cari Pejabat ..." ref="search_input_penandatangan_kecamatan"
              v-model="penandatangan_kecamatan" :url="origin + '/backend/officials'"
              v-if="penandatangan_kecamatan == null">
              <template v-slot="{ data }">
                <small class="text-secondary">{{ data.nip }}</small>
                <p class="mb-0">{{ data.name }}</p>
                <hr class="m-0" />
                <p class="mb-0">{{ data.position }}</p>
              </template>
              <template #default_first>
                <p class="text-center text-primary m-0" style="cursor: pointer" @click="
  modal_form_penandatangan_kecamatan.show(),
  ($refs.search_input_penandatangan_kecamatan.focus = false)
">
                  + Tambah Data Pejabat
                </p>
              </template>
            </search-input>
            <div v-else class="form-control d-flex align-items-center justify-content-between">
              <span>{{ penandatangan_kecamatan.nip }} - {{ penandatangan_kecamatan.name }}</span>
              <span @click="resetPenandatanganKecamatan" class="tf-icons bx bx-pencil" style="cursor: pointer"></span>
            </div>
          </div>
          <div class="mt-2" v-if="penandatangan_kecamatan != null">
            <button type="submit" class="btn btn-primary me-2">
              Ubah Penandatangan
            </button>
          </div>
        </form>
      </template>
    </verification-letter>
  </modal>
  <modal ref="modal_file_arsip" :size="'sm'" @on-hide="reset" :title="'File Arsip'" :close_button="true">
    <form method="POST" @submit.prevent="uploadFileArsip">
      <div class="row">
        <div class="col-lg-12 mb-3">
          <default-file-upload v-model="file_arsip" v-model:preview="letter.file_arsip"
            :accepted_file_type="['zip', 'rar', 'jpg', 'jpeg', 'png', 'pdf']" />
        </div>
        <button :disabled="!file_arsip" type="submit" class="btn btn-success">SIMPAN</button>
      </div>
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

  <modal ref="modal_add_resident" :title="'Tambah Data Penduduk'" :close_button="true">
    <resident-form @on-save="modal_add_resident.hide(), spinner.show()" @finish="savedResident"></resident-form>
  </modal>

  <modal ref="modal_form_penandatangan_kecamatan" :title="'Tambah Penandatangan Kecamatan'" :close_button="true">
    <official-form @on-save="modal_form_penandatangan_kecamatan.hide(), spinner.show()"
      @finish="savedPenandatanganKecamatan"></official-form>
  </modal>

  <modal ref="modal_form_penandatangan_desa" :title="'Tambah Penandatangan desa'" :close_button="true">
    <official-form @on-save="modal_form_penandatangan_desa.hide(), spinner.show()" @finish="savedPenandatanganDesa">
    </official-form>
  </modal>
</template>
<script>
import CustomTable from "../Helpers/CustomTable.vue";
import Modal from "../Helpers/Modal.vue";
import DefaultFileUpload from "../Helpers/DefaultFileUpload.vue";
import SearchInput from "../Helpers/SearchInput.vue";
import ResidentForm from "../ResidentForm.vue";
import OfficialForm from "../OfficialForm.vue";
import VerificationLetter from "./VerificationLetter.vue";
import axios from "axios";
import Swal from "sweetalert2";
import { mapState } from 'pinia';
import { useUserStore } from '../../store/user';

export default {
  name: "SktmDtks",
  components: {
    CustomTable,
    Modal,
    DefaultFileUpload,
    SearchInput,
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
        "File Pendukung",
        "Status",
      ],
      url: `${origin}/backend/sktm-dtks-letters`,
      modal: null,
      modal_add_resident: null,
      modal_form_penandatangan_kecamatan: null,
      modal_form_penandatangan_desa: null,
      modal_verification: null,
      format_reference_number: {
        prefix: "",
        suffix: "",
      },
      surat_pengantar_file: null,
      kartu_keluarga_file: null,
      penandatangan_desa: null,
      penandatangan_kecamatan: null,
      letter: {
        id: "",
        reference_number: "",
        prefix: "",
        suffix: "",
        dasar_1: "",
        dasar_2: "",
        surat_pengantar: null,
        kartu_keluarga: null,
        residents: [],
        used_as: "",
        penandatangan_kecamatan: null,
        penandatangan_desa: null,
        is_verified: false,
        file_arsip: null,
      },
      file_arsip: null,
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
    this.modal_form_penandatangan_kecamatan = this.$refs.modal_form_penandatangan_kecamatan;
    this.modal_form_penandatangan_desa = this.$refs.modal_form_penandatangan_desa;
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
    resetPenandatanganKecamatan() {
      this.penandatangan_kecamatan = null;
    },
    async saveLetter() {
      let response = "";
      let letter_id = this.letter.id;
      let letter = new FormData();
      let residents = this.letter.residents.map(p => p.id);
      try {
        this.spinner.show();
        letter.append("dasar_1", this.letter.dasar_1);
        letter.append("dasar_2", this.letter.dasar_2);
        letter.append("surat_pengantar", this.surat_pengantar_file ?? "");
        letter.append("kartu_keluarga", this.kartu_keluarga_file ?? "");
        letter.append('used_as', this.letter.used_as);
        if (letter_id == "") {
          if (!this.surat_pengantar_file || !this.kartu_keluarga_file) {
            throw "file_is_null";
          }
        }
        if (residents.length < 1) {
          throw "resident_is_null";
        }
        letter.append("residents", JSON.stringify(residents));
        if (!this.letter.penandatangan_desa) {
          throw "penandatangan_desa_is_null";
        }
        letter.append('penandatangan_desa_id', this.penandatangan_desa != null ? this.penandatangan_desa.id : "");
        if (this.letter.id == "") {
          const { data } = await axios.post(`${this.url}`, letter);
          response = data;
        } else {
          const { data } = await axios.post(`${this.url}/${this.letter.id}`, letter);
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
          case 'resident_is_null':
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Pemohon wajib diisi !",
              showConfirmButton: false,
              timer: 1500,
            });
            break;
          case 'penandatangan_desa_is_null':
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Penandatangan Desa wajib diisi !",
              showConfirmButton: false,
              timer: 1500,
            });
            break;
          case 'file_is_null':
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Surat pengantar dan kartu keluarga wajib dilampirkan !",
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
            this.$refs.table.refresh();
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
        dasar_1: "",
        dasar_2: "",
        surat_pengantar: null,
        kartu_keluarga: null,
        residents: [],
        used_as: "",
        penandatangan_kecamatan: null,
        penandatangan_desa: null,
        is_verified: false,
        file_arsip: null,
      };
      this.surat_pengantar_file = null;
      this.kartu_keluarga_file = null;
      this.file_arsip = null;
      this.penandatangan_desa = null;
      this.penandatangan_kecamatan = null;
    },
    async savedResident(data) {
      await this.spinner.hide();
      if (data) {
        this.letter.residents.push(data);
      }
    },
    savedPenandatanganKecamatan(data) {
      this.spinner.hide();
      if (data) {
        this.penandatangan_kecamatan = data;
      }
    },
    savedPenandatanganDesa(data) {
      this.spinner.hide();
      if (data) {
        this.letter.penandatangan_desa = data;
        this.penandatangan_desa = data;
      }
    },
    openModalVerification(letter) {
      this.letter = letter;
      this.modal_verification.show();
    },
    savePenandatanganKecamatan() {
      if (this.letter.id != '' && this.penandatangan_kecamatan != null) {
        this.spinner.show();
        let payload = {
          penandatangan_kecamatan_id: this.penandatangan_kecamatan.id
        };
        axios.post(`${this.url}/penandatangan/${this.letter.id}`, payload).then(response => {
          this.letter.penandatangan_kecamatan = response.data;
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
    async uploadFileArsip() {
      let response = "";
      let letter = new FormData();
      let letter_id = this.letter.id;
      try {
        this.spinner.show();
        letter.append('file_arsip', this.file_arsip ?? "");
        if (!this.file_arsip) {
          throw "file_is_null";
        }
        const { data } = await axios.post(`${this.url}/upload-file-arsip/${this.letter.id}`, letter);
        response = data;
        await Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response,
          showConfirmButton: false,
          timer: 1500,
        });
        this.$refs.modal_file_arsip.hide();
        this.$refs.table.refresh();
      } catch (err) {
        switch (err) {
          case 'file_is_null':
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "File arsip wajib dilampirkan !",
              showConfirmButton: false,
              timer: 1500,
            });
            break;
          default:
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Gagal upload file arsip !",
              showConfirmButton: false,
              timer: 1500,
            });
            break;
        }
      } finally {
        this.spinner.hide();
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
        // this.spinner.hide();
        iframe.onload = function () {
          setTimeout(function () {
            iframe.focus();
            iframe.contentWindow.print();
          }, 1);
        };
      });
      // await this.spinner.hide();
    },
    copy(items) {
      return JSON.parse(JSON.stringify(items));
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