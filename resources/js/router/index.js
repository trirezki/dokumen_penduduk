import { createRouter, createWebHistory } from "vue-router";

import Dashboard from "../components/Dashboard.vue";
import Institution from "../components/Institution.vue";
import Account from "../components/Account.vue";
import CreateOrUpdateAccount from "../components/CreateOrUpdateAccount.vue";
import AccountKecamatan from "../components/AccountKecamatan.vue";
import CreateOrUpdateAccountKecamatan from "../components/CreateOrUpdateAccountKecamatan.vue";
import Officials from "../components/Officials.vue";
import ReferenceNumbers from "../components/ReferenceNumbers.vue";
import Iumk from "../components/Surat/Iumk.vue";
import Damiu from "../components/Surat/Damiu.vue";
import DispensasiNikah from "../components/Surat/DispensasiNikah.vue";
import SktmSekolah from "../components/Surat/SktmSekolah.vue";
import SktmDtks from "../components/Surat/SktmDtks.vue";
import BiodataPendudukWni from "../components/Surat/BiodataPendudukWni.vue";
import Skpwni from "../components/Surat/Skpwni.vue";
import PathNotFound from '../components/PathNotFound.vue';

const routes = [
  {
    path: "",
    name: "Dashboard",
    component: Dashboard,
  },
  {
    path: "/instansi",
    name: "Institution",
    component: Institution,
  },
  {
    path: "/akun/desa",
    name: "Account",
    component: Account,
  },
  {
    path: "/akun/desa/tambah",
    name: "CreateAccount",
    component: CreateOrUpdateAccount,
  },
  {
    path: "/akun/desa/:id",
    name: "UpdateAccount",
    component: CreateOrUpdateAccount,
    props: {
      is_form_update: true,
    }
  },
  {
    path: "/akun/kecamatan",
    name: "AccountKecamatan",
    component: AccountKecamatan,
  },
  {
    path: "/akun/kecamatan/tambah",
    name: "CreateAccountKecamatan",
    component: CreateOrUpdateAccountKecamatan,
  },
  {
    path: "/akun/kecamatan/:id",
    name: "UpdateAccountKecamatan",
    component: CreateOrUpdateAccountKecamatan,
    props: {
      is_form_update: true,
    }
  },
  {
    path: "/pejabat",
    name: "Officials",
    component: Officials
  },
  {
    path: "/nomor-surat",
    name: "ReferenceNumbers",
    component: ReferenceNumbers,
  },
  {
    path: "/iumk",
    name: "Iumk",
    component: Iumk
  },
  {
    path: "/damiu",
    name: "Damiu",
    component: Damiu
  },
  {
    path: "/dispensasi-nikah",
    name: "DispensasiNikah",
    component: DispensasiNikah
  },
  {
    path: "/sktm-sekolah",
    name: "SktmSekolah",
    component: SktmSekolah
  },
  {
    path: "/sktm-dtks",
    name: "SktmDtks",
    component: SktmDtks
  },
  {
    path: "/biodata-penduduk-wni",
    name: "BiodataPendudukWni",
    component: BiodataPendudukWni
  },
  {
    path: "/skpwni",
    name: "Skpwni",
    component: Skpwni
  },
  { path: '/:pathMatch(.*)*', name: 'NotFound', component: PathNotFound },
];

const router = createRouter({
  history: createWebHistory(),
  linkActiveClass: 'active',
  routes,
});

export default router;
