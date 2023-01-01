<template>
  <h4 class="fw-bold py-3 mb-4" style="text-transform: capitalize">
    Format Nomor Surat
  </h4>
  <div class="row">
    <div class="col-md-12">
      <div class="card mb-4">
        <div class="card-body">
          <h5>Cara penulisan Format No Surat</h5>
          <ul>
            <li>
              Gunakan <code>{tgl}</code> untuk mendapatkan <b>tanggal</b> (
              contoh : {{ String(new Date().getDate()).padStart(2, "0") }} )
            </li>
            <li>
              Gunakan <code>{bln}</code> untuk mendapatkan <b>bulan</b> ( contoh
              : {{ String(new Date().getMonth()).padStart(2, "0") }} )
            </li>
            <li>
              Gunakan <code>{thn}</code> untuk mendapatkan <b>tahun</b> ( contoh
              : {{ String(new Date().getFullYear()) }} )
            </li>
          </ul>
          <p class="text-danger">
            Catatan : Tanggal, bulan, dan tahun diambil secara dinamis sesuai
            tanggal surat dibuat
          </p>
        </div>
      </div>
      <div class="card mb-4">
        <form id="formAccountSettings" method="POST" @submit.prevent="save">
          <div class="card-body">
            <div>
              <div class="mb-3">
                <label for="jenis_surat" class="form-label">Jenis Surat</label>
                <select class="form-control" v-model="reference_number.type" @change="getReferenceNumber">
                  <option v-if="user.type == 'kecamatan'" value="IUMK">SURAT IZIN USAHA MIKRO DAN KECIL</option>
                  <option v-if="user.type == 'kecamatan'" value="DAMIU">
                    SURAT IZIN USAHA DEPOT AIR MINUM ISI ULANG
                  </option>
                  <option v-if="user.type == 'kecamatan'" value="DISPENSASI_NIKAH">
                    SURAT KETERANGAN IZIN MELAKSANAKAN PERNIKAHAN/ PERKAWINAN
                  </option>
                  <option value="SKTM_SEKOLAH">
                    SURAT KETERANGAN TIDAK MAMPU UNTUK SEKOLAH/INSTANSI
                  </option>
                  <option value="SKTM_DTKS">
                    SURAT KETERANGAN TIDAK MAMPU UNTUK DATA TERPADU KESEJAHTERAAN SOSIAL (DTKS)
                  </option>
                  <option v-if="user.type == 'kecamatan'" value="BIODATA_PENDUDUK_WNI">
                    BIODATA PENDUDUK WNI
                  </option>
                  <option value="SKPWNI">
                    SURAT KETERANGAN PINDAH DATANG WNI
                  </option>
                </select>
              </div>
            </div>
            <hr class="mb-4" />
            <div class="row" v-if="reference_number.type != '' && !loading">
              <div class="mb-3 col-md-6">
                <label for="province" class="form-label">Prefix No Surat</label>
                <input class="form-control" type="text" id="province" name="province" placeholder="" required
                  v-model="reference_number.prefix" />
              </div>
              <div class="mb-3 col-md-6">
                <label for="district" class="form-label">Suffix No Surat</label>
                <input type="text" class="form-control" id="district" name="district" placeholder="" required
                  v-model="reference_number.suffix" />
              </div>
              <div class="mb-3 col-lg-12">
                <h4>
                  Preview No Surat :
                  <span class="text-primary">{{ preview }}</span>
                </h4>
                <small class="text-danger">Catatan : XXX adalah kode unik yang akan secara otomatis
                  digenerate oleh sistem.</small>
              </div>
            </div>
            <div class="mt-2" v-if="reference_number.type != '' && !loading">
              <button type="submit" class="btn btn-success me-2">
                Simpan Format
              </button>
              <button type="reset" class="btn btn-outline-secondary" @click="reset">
                Cancel
              </button>
            </div>
            <div class="mt-2 text-center" v-if="loading"> Loading ... </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script>
import axios from "axios";
import Swal from "sweetalert2";
import { mapState } from 'pinia'
import { useUserStore } from '../store/user'
export default {
  name: "CreateOrUpdateReferenceNumber",
  data() {
    return {
      loading: false,
      reference_number: {
        type: "",
        prefix: "",
        suffix: "",
      },
      url: window.location.origin,
    };
  },
  computed: {
    preview() {
      let pr = `${this.reference_number.prefix}XXX${this.reference_number.suffix}`;

      pr = pr.replaceAll("{tgl}", String(new Date().getDate()).padStart(2, "0"));
      pr = pr.replaceAll("{bln}", String(new Date().getMonth()).padStart(2, "0"));
      pr = pr.replaceAll("{thn}", new Date().getFullYear());

      return pr;
    },
    ...mapState(useUserStore, ['user']),
  },
  mounted() { },
  methods: {
    getReferenceNumber() {
      this.loading = true;
      axios
        .get(
          `${this.url}/backend/reference-number?type=${this.reference_number.type}`
        )
        .then((response) => {
          if (!this.isObjectEmpty(response.data)) {
            Object.assign(this.reference_number, response.data);
          }
          this.loading = false;
        });
    },
    save() {
      axios
        .post(`${this.url}/backend/reference-number`, this.reference_number)
        .then((response) => {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.data,
            showConfirmButton: false,
            timer: 1500,
          });
        })
        .catch((error) => {
          console.log(error.response.data);
        });
    },
    reset() {
      this.reference_number = {
        type: "",
        prefix: "",
        suffix: "",
      };
    },
    isObjectEmpty(value) {
      return (
        Object.prototype.toString.call(value) === "[object Object]" &&
        JSON.stringify(value) === "{}"
      );
    },
  },
};
</script>
