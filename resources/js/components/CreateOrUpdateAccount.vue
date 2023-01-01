<template>
  <div v-if="!loading">
    <h4 class="fw-bold py-3 mb-4" style="text-transform: capitalize">
      {{ is_form_update ? "Update" : "Tambah" }} Akun Desa
    </h4>
    <div class="row">
      <div class="col-md-12">
        <div class="card mb-4">
          <!-- Account -->
          <div class="card-body">
            <div class="d-flex align-items-start align-items-sm-center gap-4">
              <img :src="
  previewImage
    ? previewImage
    : '/images/avatars/1.png'
" alt="user-avatar" class="d-block rounded img-fluid" width="100" id="uploadedLogo" />
              <div class="button-wrapper">
                <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                  <span class="d-none d-sm-block" v-if="!previewImage">Upload Logo</span>
                  <span class="d-none d-sm-block" v-else>Ubah Logo</span>
                  <i class="bx bx-upload d-block d-sm-none"></i>
                  <input type="file" id="upload" class="account-file-input" hidden accept="image/png, image/jpeg"
                    @change="onEventFilePicked" />
                </label>
                <button type="button" class="btn btn-outline-secondary account-image-reset mb-4" @click="resetImage">
                  <i class="bx bx-reset d-block d-sm-none"></i>
                  <span class="d-none d-sm-block">Reset</span>
                </button>

                <p class="text-muted mb-0">
                  JPG, GIF dan PNG diperbolehkan. Ukuran maksimal 2MB
                </p>
              </div>
            </div>
          </div>
          <hr class="my-0" />
          <form id="formAccountSettings" method="POST" @submit.prevent="save">
            <div class="card-body">
              <div>
                <h4>Autentikasi</h4>
                <div class="row">
                  <div class="mb-3 col-md-6">
                    <label for="address" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
                      v-model="this.institution.email" />
                  </div>
                  <div class="mb-3 col-md-6">
                    <label for="state" class="form-label">Kata Sandi</label>
                    <input type="password" :required="!is_form_update" id="password" class="form-control"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" v-model="this.institution.password" />
                  </div>
                </div>
              </div>
              <hr class="mb-4" />
              <h4>Data Instansi</h4>
              <div class="row">
                <div class="mb-3 col-md-6">
                  <label for="name" class="form-label">Nama Institusi</label>
                  <input class="form-control" type="text" id="name" name="name" autofocus
                    placeholder="CAMAT PULAULAUT SIGAM" required v-model="institution.name" />
                </div>
                <div class="mb-3 col-md-6">
                  <label for="whatsapp_number" class="form-label">Nomor Whatsapp</label>
                  <input class="form-control" type="tel" autofocus placeholder="628XXXXXXXXXX" required
                    v-model="institution.whatsapp_number" />
                </div>
                <div class="mb-3 col-md-6">
                  <label for="province" class="form-label">Provinsi</label>
                  <input class="form-control" type="text" id="province" name="province" placeholder="Kalimantan Selatan"
                    required v-model="institution.province" />
                </div>
                <div class="mb-3 col-md-6">
                  <label for="district" class="form-label">Kabupaten</label>
                  <input type="text" class="form-control" id="district" name="district" placeholder="Kotabaru" required
                    v-model="institution.district" />
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="sub_district">Kecamatan</label>
                  <input type="text" class="form-control" id="sub_district" name="sub_district"
                    placeholder="Pulau Laut Sigam" required v-model="institution.sub_district" />
                </div>
                <div class="mb-3 col-md-6">
                  <label for="address" class="form-label">Desa/Kelurahan</label>
                  <div class="input-group mb-3">
                    <select class="form-select" v-model="institution.type_village">
                      <option value="Desa">Desa</option>
                      <option value="Kelurahan">Kelurahan</option>
                    </select>
                    <input type="text" class="form-control" style="flex-grow:3;" id="village" name="village"
                      placeholder="Sigam" required v-model="institution.village" />
                  </div>
                </div>
                <div class="mb-3 col-md-6">
                  <label for="state" class="form-label">Jalan</label>
                  <input class="form-control" type="text" id="street" name="street" placeholder="Jl. Berangas Km.4"
                    required v-model="institution.street" />
                </div>
                <div class="mb-3 col-md-6">
                  <label for="zip_code" class="form-label">Kode Pos</label>
                  <input type="text" class="form-control" id="zip_code" name="zip_code" placeholder="72112"
                    maxlength="6" required v-model="institution.zip_code" />
                </div>
              </div>
              <div class="mt-2">
                <button type="submit" class="btn btn-primary me-2">
                  {{ is_form_update ? "Update" : "Tambah" }} Akun
                </button>
                <button type="button" @click="$router.push({ name: 'Account' })" class="btn btn-outline-secondary">
                  Cancel
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import axios from "axios";
import Swal from "sweetalert2";
export default {
  name: "CreateOrUpdateAccount",
  props: {
    is_form_update: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      loading: false,
      previewImage: "",
      logo_before: "",
      institution: {},
      url: window.location.origin,
    };
  },
  mounted() {
    if (this.is_form_update) {
      this.loading = true;
      axios.get(`${this.url}/backend/account/village/${this.$route.params.id}`).then((response) => {
        this.previewImage = response.data.logo;
        this.logo_before = response.data.logo;
        this.institution = response.data;
        this.institution.logo = null;
        this.loading = false;
      }).catch((e) => {
        this.$router.push({ name: 'Account' });
      })
    }
  },
  methods: {
    onEventFilePicked(event) {
      if (event.target.files.length > 0) {
        const file = event.target.files[0];
        if (file.size / 1024 <= 2048) {
          this.previewImage = URL.createObjectURL(file);
          this.institution.logo = file;
        } else {
          Swal.fire({
            icon: "error",
            title: "Gagal!",
            text: "Ukuran logo melebihi 2MB",
            showConfirmButton: false,
            timer: 1500,
          });
        }
      }
    },
    save() {
      let formData = new FormData();
      formData.append("email", this.institution.email);
      formData.append("password", this.institution.password);
      if (this.institution.logo) {
        formData.append("logo", this.institution.logo);
      }
      formData.append("name", this.institution.name);
      formData.append("province", this.institution.province);
      formData.append("district", this.institution.district);
      formData.append("sub_district", this.institution.sub_district);
      formData.append("type_village", this.institution.type_village);
      formData.append("village", this.institution.village);
      formData.append("street", this.institution.street);
      formData.append("zip_code", this.institution.zip_code);
      formData.append("whatsapp_number", this.institution.whatsapp_number);
      if (!this.is_form_update) {
        axios
          .post(`${this.url}/backend/account/village`, formData)
          .then((response) => {
            Swal.fire({
              icon: "success",
              title: "Berhasil",
              text: response.data,
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              this.$router.push({ name: "Account" });
            })
          })
          .catch((error) => {
            console.log(error.response.data);
          });
      } else {
        axios
          .post(`${this.url}/backend/account/village/${this.institution.id}`, formData)
          .then((response) => {
            Swal.fire({
              icon: "success",
              title: "Berhasil",
              text: response.data,
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              this.$router.push({ name: "Account" });
            })
          })
          .catch((error) => {
            console.log(error.response.data);
          });
      }
    },
    resetImage() {
      this.institution.logo = null;
      this.previewImage = this.logo_before;
    },
    dataURLtoFile(img) {
      var canvas = document.createElement("canvas");
      canvas.width = img.naturalWidth;
      canvas.height = img.naturalHeight;
      var ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      var dataurl = canvas.toDataURL("image/png");
      var blobBin = atob(dataurl.split(",")[1]);
      var array = [];
      for (var i = 0; i < blobBin.length; i++) {
        array.push(blobBin.charCodeAt(i));
      }
      return new Blob([new Uint8Array(array)], { type: "image/png" });
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
