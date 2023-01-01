<template>
  <div class="row">
    <div class="col-lg-12">
      <ul class="nav nav-pills flex-column flex-md-row mb-3">
        <li class="nav-item">
          <router-link :to="{ name: 'CreateAccountKecamatan'}" class="nav-link active"
            ><i class="bx bx-plus me-1" style=""></i> Tambah Akun Kecamatan</router-link
          >
        </li>
      </ul>
      <div class="card">
        <div class="card-body">
          <custom-table :thead="head" :url="url" v-slot="{ key, id, name, email, whatsapp_number, street }">
            <td>{{ key }}</td>
            <td>{{ name }}</td>
            <td>{{ email }}</td>
            <td>{{ whatsapp_number }}</td>
            <td>{{ street }}</td>
            <td>
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
                    @click="$router.push({ name:'UpdateAccountKecamatan', params:{ id:id } })"
                    ><i class="bx bx-edit-alt me-1"></i> Ubah</span
                  >
                  <span class="dropdown-item" @click="deleteAccount(id)"
                    ><i class="bx bx-trash me-1"></i> Hapus</span
                  >
                </div>
              </div>
            </td>
          </custom-table>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import CustomTable from './Helpers/CustomTable.vue'
export default {
  name: "AccountKecamatan",
  components: {
    CustomTable,
  },
  data() {
    return {
      head: [
        'S.N',
        'nama',
        'email',
        'nomor whatsapp',
        'alamat',
        'action'
      ],
      url: `${window.location.origin}/backend/account/sub_district`
    };
  },
};
</script>