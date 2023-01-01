<template>
  <h4 class="fw-bold py-3 mb-4">
    SURAT KETERANGAN IZIN MELAKSANAKAN PERNIKAHAN/ PERKAWINAN
  </h4>
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body" v-if="user?.type == 'desa'">
          <ul class="nav nav-pills flex-column flex-md-row">
            <li class="nav-item">
              <span v-if="modal" class="nav-link active" @click="modal.show()"><i class="bx bx-plus me-1" style=""></i>
                Tambah Surat
                Dispensasi Nikah</span>
            </li>
          </ul>
        </div>
        <hr class="my-0" />
        <div class="card-body">
          <custom-table ref="table" :thead="head" :url="url" v-slot="data">
            <td>{{ data.key }}</td>
            <td>{{ data.reference_number }}</td>
            <td>{{ data.pemohon_laki_laki.name }}</td>
            <td>{{ data.pemohon_perempuan.name }}</td>
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
  <modal ref="modal" :size="'lg'" @on-hide="reset"
    :title="(letter.id == '' ? 'Tambah' : 'Ubah') + ' Surat Dispensasi Nikah'" :button_close="true"
    v-if="user?.type == 'desa'">
    <form @submit.prevent="saveLetter">
      <div class="mb-3">
        <label class="form-label">Nomor Surat</label>
        <span class="form-control disabled">{{ preview }}</span>
      </div>
      <div class="mb-3">
        <label class="form-label">Data Pemohon Laki-laki</label>
        <search-input ref="search_input_pemohon_laki_laki" :url="origin + '/backend/resident-search'"
          :filter="{ gender: 'laki-laki' }" v-model="letter.pemohon_laki_laki" placeholder="Cari ...">
          <template v-slot="{ data }">
            <small class="text-secondary">{{ data.nik }}</small>
            <p class="mb-0">{{ data.name }}</p>
            <hr class="m-0" />
            <p class="mb-0">{{ data.address }}</p>
          </template>
          <template #default_first>
            <p class="text-center text-primary m-0" style="cursor: pointer" @click="
  modal_add_pemohon_laki_laki.show(),
  ($refs.search_input_pemohon_laki_laki.focus = false)
">
              + Tambah Data Penduduk
            </p>
          </template>
        </search-input>
        <div class="table-responsive no-min-height" v-if="letter.pemohon_laki_laki != null">
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
                <td>{{ letter.pemohon_laki_laki.nik }}</td>
                <td>{{ letter.pemohon_laki_laki.name }}</td>
                <td>{{ letter.pemohon_laki_laki.address }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-danger text-center mt-2" v-else>
          Belum ada data pemohon
        </p>
      </div>
      <div class="mb-3">
        <label class="form-label">Data Pemohon Perempuan</label>
        <search-input ref="search_input_pemohon_perempuan" :url="origin + '/backend/resident-search'"
          :filter="{ gender: 'perempuan' }" v-model="letter.pemohon_perempuan" placeholder="Cari ...">
          <template v-slot="{ data }">
            <small class="text-secondary">{{ data.nik }}</small>
            <p class="mb-0">{{ data.name }}</p>
            <hr class="m-0" />
            <p class="mb-0">{{ data.address }}</p>
          </template>
          <template #default_first>
            <p class="text-center text-primary m-0" style="cursor: pointer" @click="
  modal_add_pemohon_perempuan.show(),
  ($refs.search_input_pemohon_perempuan.focus = false)
">
              + Tambah Data Penduduk
            </p>
          </template>
        </search-input>
        <div class="table-responsive no-min-height" v-if="letter.pemohon_perempuan != null">
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
                <td>{{ letter.pemohon_perempuan.nik }}</td>
                <td>{{ letter.pemohon_perempuan.name }}</td>
                <td>{{ letter.pemohon_perempuan.address }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-danger text-center mt-2" v-else>
          Belum ada data pemohon
        </p>
      </div>
      <div class="mb-3">
        <label class="form-label">File Pendukung</label>
        <default-file-upload v-model="file_pendukung" v-model:preview="letter.file_pendukung"
          :accepted_file_type="['zip', 'rar', 'jpg', 'jpeg', 'png', 'pdf']" />
      </div>
      <button type="submit" class="btn btn-success">SIMPAN</button>
    </form>
  </modal>
  <modal ref="modal_verification" :size="'lg'" @on-hide="reset" :title="'Verifikasi Surat Dispensasi Nikah'"
    :close_button="true">
    <verification-letter :url="url" :letter="letter" @loading="spinner.show()" @loaded="spinner.hide()"
      @success="reset(), $refs.table.refresh()" :can_verified="letter.official != null">
      <template #penandatangan>
        <form method="POST" @submit.prevent="savePenandatangan">
          <div class="mb-3">
            <label class="form-label">Pejabat Penandatangan</label>
            <search-input placeholder="Cari Pejabat ..." ref="search_input_official" v-model="penandatangan"
              :url="origin + '/backend/officials'" v-if="penandatangan == null" @change="ubah_penandatangan = true">
              <template v-slot="{ data }">
                <small class="text-secondary">{{ data.nip }}</small>
                <p class="mb-0">{{ data.name }}</p>
                <hr class="m-0" />
                <p class="mb-0">{{ data.position }}</p>
              </template>
              <template #default_first>
                <p class="text-center text-primary m-0" style="cursor: pointer" @click="
  modal_form_official.show(),
  ($refs.search_input_official.focus = false)
">
                  + Tambah Data Pejabat
                </p>
              </template>
            </search-input>
            <div v-else class="form-control d-flex align-items-center justify-content-between">
              <span>{{ penandatangan.nip }} - {{ penandatangan.name }}</span>
              <span @click="resetOfficial" class="tf-icons bx bx-pencil" style="cursor: pointer"></span>
            </div>
          </div>
          <div class="mt-2" v-if="ubah_penandatangan">
            <button type="submit" class="btn btn-primary me-2">
              Ubah Penandatangan
            </button>
          </div>
        </form>
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
  <modal ref="modal_add_pemohon_laki_laki" :title="'Tambah Data Penduduk'" :button_close="true">
    <resident-form @on-save="modal_add_pemohon_laki_laki.hide(), spinner.show()" @finish="savedPemohonLakiLaki">
    </resident-form>
  </modal>
  <modal ref="modal_add_pemohon_perempuan" :title="'Tambah Data Penduduk'" :button_close="true">
    <resident-form :default_gender="'perempuan'" :default_marital_status="'perawan'"
      @on-save="modal_add_pemohon_perempuan.hide(), spinner.show()" @finish="savedPemohonPerempuan"></resident-form>
  </modal>
  <modal ref="modal_form_official" :title="'Tambah Penandatangan'" :button_close="true">
    <official-form @on-save="modal_form_official.hide(), spinner.show()" @finish="savedOfficial"></official-form>
  </modal>
</template>
<script>
import CustomTable from "../Helpers/CustomTable.vue";
import Modal from "../Helpers/Modal.vue";
import SearchInput from "../Helpers/SearchInput.vue";
import DefaultFileUpload from "../Helpers/DefaultFileUpload.vue";
import FileUpload from "../Helpers/FileUpload.vue";
import ResidentForm from "../ResidentForm.vue";
import OfficialForm from "../OfficialForm.vue";
import VerificationLetter from "./VerificationLetter.vue";
import axios from "axios";
import Swal from "sweetalert2";
import { mapState } from 'pinia'
import { useUserStore } from '../../store/user'

export default {
  name: "DispensasiNikah",
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
        "Pemohon Laki-laki",
        "Pemohon Perempuan",
        "File Pendukung",
        "Status",
      ],
      url: `${origin}/backend/dispensasi-nikah-letters`,
      modal: null,
      modal_add_pemohon_laki_laki: null,
      modal_add_pemohon_perempuan: null,
      modal_form_official: null,
      modal_verification: null,
      format_reference_number: {
        prefix: "",
        suffix: "",
      },
      ubah_penandatangan: false,
      penandatangan: null,
      file_pendukung: null,
      letter: {
        id: "",
        reference_number: "",
        prefix: "",
        suffix: "",
        pemohon_laki_laki: null,
        pemohon_perempuan: null,
        official: null,
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
    this.modal_add_pemohon_laki_laki = this.$refs.modal_add_pemohon_laki_laki;
    this.modal_add_pemohon_perempuan = this.$refs.modal_add_pemohon_perempuan;
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
    resetOfficial() {
      this.penandatangan = null;
    },
    async saveLetter() {
      let response = "";
      let payload = new FormData();
      let letter_id = this.letter.id;
      try {
        this.spinner.show();
        payload.append('pemohon_laki_laki_id', this.letter.pemohon_laki_laki.id);
        payload.append('pemohon_perempuan_id', this.letter.pemohon_perempuan.id);
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
        }).then(async () => {
          await this.spinner.hide();
          await this.modal.hide();
          await this.$refs.table.refresh();
        })
      } catch (err) {
        switch (err) {
          case 'file_pendukung_is_null':
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "File pendukung wajib dilampirkan !",
              showConfirmButton: false,
              timer: 1500,
            }).then(async () => {
              await this.spinner.hide();
            })
            break;
          default:
            await Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Gagal menyimpan surat !",
              showConfirmButton: false,
              timer: 1500,
            }).then(async () => {
              await this.spinner.hide();
            })
            break;
        }
      }
    },
    deleteLetter(id) {
      this.spinner.show();
      axios.delete(`${this.url}/${id}`).then(({ data }) => {
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: data,
          showConfirmButton: false,
          timer: 1500,
        }).then(() => {
          this.spinner.hide();
        });
        this.$refs.table.refresh();
      }).catch((e) => {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: "Surat gagal dihapus",
          showConfirmButton: false,
          timer: 1500,
        }).then(() => {
          this.spinner.hide();
        });
      });
    },
    async reset() {
      this.letter = {
        id: "",
        reference_number: "",
        prefix: "",
        suffix: "",
        pemohon_laki_laki: null,
        pemohon_perempuan: null,
        official: null,
        file_pendukung: null,
        is_verified: false,
      };
      this.file_pendukung = null;
    },
    savedPemohonLakiLaki(data) {
      this.spinner.hide();
      if (data) {
        this.letter.pemohon_laki_laki = data;
      }
    },
    savedPemohonPerempuan(data) {
      this.spinner.hide();
      if (data) {
        this.letter.pemohon_perempuan = data;
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
      this.penandatangan = this.letter.official;
      this.modal_verification.show();
    },
    savePenandatangan() {
      if (this.letter.id != '' && this.penandatangan != null) {
        this.spinner.show();
        let payload = {
          official_id: this.penandatangan.id
        };
        axios.post(`${this.url}/penandatangan/${this.letter.id}`, payload).then(response => {
          this.ubah_penandatangan = false;
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