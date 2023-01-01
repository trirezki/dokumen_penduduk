<template>
  <form @submit.prevent="saveResident">
    <div class="mb-3">
      <label class="form-label">NIK</label>
      <input
        type="text"
        class="form-control"
        placeholder="xxxxxxxxxxxxxxxx"
        required
        v-model="resident.nik"
      />
    </div>
    <div class="mb-3">
      <label class="form-label">Nama Lengkap</label>
      <input
        type="text"
        class="form-control"
        placeholder="Nama Lengkap"
        required
        v-model="resident.name"
      />
    </div>
    <div class="mb-3">
      <label class="form-label">Jenis Kelamin</label>
      <div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            value="laki-laki"
            v-model="resident.gender"
          />
          <label class="form-check-label">Laki-laki</label>
        </div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            value="perempuan"
            v-model="resident.gender"
          />
          <label class="form-check-label">Perempuan</label>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="mb-3 col-lg-6">
        <label class="form-label">Tempat Lahir</label>
        <input
          class="form-control"
          type="text"
          placeholder="Kotabaru"
          required
          v-model="resident.place_of_birth"
        />
      </div>
      <div class="mb-3 col-lg-6">
        <label class="form-label">Tangal Lahir</label>
        <input
          type="date"
          class="form-control"
          required
          v-model="resident.date_of_birth"
        />
      </div>
    </div>
    <div class="row">
      <div class="mb-3 col-lg-6">
        <label class="form-label">Pekerjaan</label>
        <input
          class="form-control"
          type="text"
          placeholder="ASN, Karyawan Swasta, dan lain-lain"
          required
          v-model="resident.profession"
        />
      </div>
      <div class="mb-3 col-lg-6">
        <label class="form-label">Agama</label>
        <select class="form-control" v-model="resident.religion" required>
          <option>Islam</option>
          <option>Kristen</option>
          <option>Katolik</option>
          <option>Hindu</option>
          <option>Budha</option>
          <option>Konghucu</option>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            value="jejaka"
            required
            name="marital_status"
            v-model="resident.marital_status"
          />
          <label class="form-check-label">Jejaka</label>
        </div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            value="perawan"
            required
            name="marital_status"
            v-model="resident.marital_status"
          />
          <label class="form-check-label">Perawan</label>
        </div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            value="kawin"
            required
            name="marital_status"
            v-model="resident.marital_status"
          />
          <label class="form-check-label">Kawin</label>
        </div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            value="cerai_hidup"
            required
            name="marital_status"
            v-model="resident.marital_status"
          />
          <label class="form-check-label">Cerai Hidup</label>
        </div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            value="cerai_mati"
            required
            name="marital_status"
            v-model="resident.marital_status"
          />
          <label class="form-check-label">Cerai Mati</label>
        </div>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Alamat</label>
      <textarea
        class="form-control"
        placeholder="Jl. Berangas Km.9"
        required
        v-model="resident.address"
      ></textarea>
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
    default_nik: {
      type: String,
      default: "",
    },
    default_name: {
      type: String,
      default: "",
    },
    default_gender: {
      type: String,
      default: "laki-laki",
    },
    default_place_of_birth: {
      type: String,
      default: "",
    },
    default_date_of_birth: {
      type: String,
      default: "",
    },
    default_profession: {
      type: String,
      default: "",
    },
    default_religion: {
      type: String,
      default: "Islam",
    },
    default_marital_status: {
      type: String,
      default: "jejaka",
    },
    default_address: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      url: `${window.location.origin}/backend/residents`,
      resident: {
        id: this.default_id,
        nik: this.default_nik,
        name: this.default_name,
        gender: this.default_gender,
        place_of_birth: this.default_place_of_birth,
        date_of_birth: this.default_date_of_birth,
        profession: this.default_profession,
        religion: this.default_religion,
        marital_status: this.default_marital_status,
        address: this.default_address,
      },
    };
  },
  methods: {
    async saveResident() {
      await this.$emit("on-save");
      await axios
        .post(`${this.url}`, this.resident)
        .then(async (response) => {
          await Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.data.message,
            showConfirmButton: false,
            timer: 1500,
          }).then(async () => {
            await this.$emit("finish", response.data.data);
          })
        })
        .catch(async (e) => {
          await Swal.fire({
            icon: "error",
            title: "Gagal",
            text: e.response.data,
            showConfirmButton: false,
            timer: 1500,
          }).then(async () => {
            await this.$emit("finish");
          })
        });
      await this.reset();
    },
    async reset() {
      this.resident = {
        id: "",
        nik: "",
        name: "",
        gender: "laki-laki",
        place_of_birth: "",
        date_of_birth: "",
        profession: "",
        religion: "Islam",
        marital_status: "jejaka",
        address: "",
      };
    },
  },
};
</script>