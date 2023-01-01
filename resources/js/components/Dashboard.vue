<template>
  <div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
      <div class="row">
        <div class="col-lg-12 mb-4">
          <div class="card">
            <div class="d-flex align-items-end row">
              <div class="col-sm-7">
                <div class="card-body">
                  <h5 class="card-title text-primary">Selamat Datang {{ user.name }}! 🎉</h5>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row" v-if="analytics">
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i style="font-size:40px;" class="menu-icon tf-icons bx bx-file"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Surat IUMK</span>
              <h3 class="card-title mb-2">{{ analytics.iumk.count }}</h3>
              <h5 class="text-success fw-semibold d-flex align-items-center">
                <i class='bx bx-badge-check'></i>
                <span class="ms-1">{{ analytics.iumk.verified }}</span>
              </h5>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i style="font-size:40px;" class="menu-icon tf-icons bx bx-file"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Surat DAMIU</span>
              <h3 class="card-title mb-2">{{ analytics.damiu.count }}</h3>
              <h5 class="text-success fw-semibold d-flex align-items-center">
                <i class='bx bx-badge-check'></i>
                <span class="ms-1">{{ analytics.damiu.verified }}</span>
              </h5>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i style="font-size:40px;" class="menu-icon tf-icons bx bx-file"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Surat Dispensasi Nikah</span>
              <h3 class="card-title mb-2">{{ analytics.dispensasi_nikah.count }}</h3>
              <h5 class="text-success fw-semibold d-flex align-items-center">
                <i class='bx bx-badge-check'></i>
                <span class="ms-1">{{ analytics.dispensasi_nikah.verified }}</span>
              </h5>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i style="font-size:40px;" class="menu-icon tf-icons bx bx-file"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Surat SKTM Untuk Sekolah/Instansi</span>
              <h3 class="card-title mb-2">{{ analytics.sktm_sekolah.count }}</h3>
              <h5 class="text-success fw-semibold d-flex align-items-center">
                <i class='bx bx-badge-check'></i>
                <span class="ms-1">{{ analytics.sktm_sekolah.verified }}</span>
              </h5>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i style="font-size:40px;" class="menu-icon tf-icons bx bx-file"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Surat SKTM Untuk Data Kesehatan</span>
              <h3 class="card-title mb-2">{{ analytics.sktm_dtks.count }}</h3>
              <h5 class="text-success fw-semibold d-flex align-items-center">
                <i class='bx bx-badge-check'></i>
                <span class="ms-1">{{ analytics.sktm_dtks.verified }}</span>
              </h5>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i style="font-size:40px;" class="menu-icon tf-icons bx bx-file"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Biodata Penduduk WNI</span>
              <h3 class="card-title mb-2">{{ analytics.biodata_penduduk_wni.count }}</h3>
              <h5 class="text-success fw-semibold d-flex align-items-center">
                <i class='bx bx-badge-check'></i>
                <span class="ms-1">{{ analytics.biodata_penduduk_wni.verified }}</span>
              </h5>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i style="font-size:40px;" class="menu-icon tf-icons bx bx-file"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Surat Keterangan Pindah Datang WNI</span>
              <h3 class="card-title mb-2">{{ analytics.skpwni.count }}</h3>
              <h5 class="text-success fw-semibold d-flex align-items-center">
                <i class='bx bx-badge-check'></i>
                <span class="ms-1">{{ analytics.skpwni.verified }}</span>
              </h5>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
<script>
import { mapState } from 'pinia'
import { useUserStore } from '../store/user'
import axios from 'axios'
export default {
  name: "Dashboard",
  data() {
    return {
      analytics: null,
      url: `${window.location.origin}/backend/analytics`,
    }
  },
  computed: {
    ...mapState(useUserStore, ['user']),
  },
  created() {
    axios.get(this.url).then(({ data }) => {
      this.analytics = data;
    });
  }
};
</script>