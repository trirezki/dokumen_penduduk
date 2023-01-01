<template>
    <h4 class="fw-bold py-3 mb-4">
        SURAT KETERANGAN PINDAH DATANG WNI
    </h4>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body" v-if="user.type == 'desa'">
                    <ul class="nav nav-pills flex-column flex-md-row">
                        <li class="nav-item">
                            <span v-if="modal" class="nav-link active" @click="modal.show()"><i class="bx bx-plus me-1"
                                    style=""></i>
                                Tambah SKPWNI</span>
                        </li>
                    </ul>
                </div>
                <hr class="my-0" />
                <div class="card-body">
                    <custom-table ref="table" :thead="head" :url="url" v-slot="data">
                        <td>{{ data.key }}</td>
                        <td>{{ data.reference_number }}</td>
                        <td>{{ data.kepala_keluarga.nik }}</td>
                        <td>{{ data.kepala_keluarga.name }}</td>
                        <td>
                            <button type="button" class="btn btn-info ms-3" @click="openNewTab(data.file_pendukung)">
                                Lihat File
                            </button>
                        </td>
                        <td>
                            <span class="badge" :class="data.is_verified ? 'bg-success' : 'bg-danger'">
                                {{ data.is_verified ? "VERIFIED" : "UNVERIFIED" }}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu" style="">
                                    <span class="dropdown-item" @click="openModalVerification(data)"
                                        v-if="!data.is_verified && data.can_verified"><i class="bx bx-check me-1"></i>
                                        Verifikasi</span>
                                    <span class="dropdown-item" @click="print(data.id)" v-if="data.is_verified"><i
                                            class="bx bx-print me-1"></i> Print Surat</span>
                                    <span class="dropdown-item" @click="edit(data)" v-if="data.can_modified"><i
                                            class="bx bx-edit-alt me-1"></i> Ubah</span>
                                    <span class="dropdown-item" @click="deleteLetter(data.id)"
                                        v-if="data.can_modified"><i class="bx bx-trash me-1"></i> Hapus</span>
                                </div>
                            </div>
                        </td>
                    </custom-table>
                </div>
            </div>
        </div>
    </div>
    <modal class="p-0" ref="modal" :size="'xl'" @on-hide="reset"
        :title="(letter.id == '' ? 'Tambah' : 'Ubah') + ' SKPWNI'" :close_button="true">
        <form @submit.prevent="saveLetter">
            <div class="mb-3">
                <label class="form-label">Nomor Surat</label>
                <span class="form-control disabled">{{ preview }}</span>
            </div>
            <h5><b>DATA DAERAH ASAL</b></h5>
            <div class="mb-3">
                <label class="form-label">Nomor Kartu Keluarga</label>
                <input type="text" class="form-control" placeholder="XXXXXXXXXXXXX"
                    v-model="letter.family_card_number" />
            </div>
            <div class="mb-3">
                <label class="form-label">Data Umum Kepala Keluarga</label>
                <search-input ref="search_input_kepala_keluarga" :url="origin + '/backend/resident-search'"
                    v-model="letter.kepala_keluarga" placeholder="Cari ...">
                    <template v-slot="{ data }">
                        <small class="text-secondary">{{ data.nik }}</small>
                        <p class="mb-0">{{ data.name }}</p>
                        <hr class="m-0" />
                        <p class="mb-0">{{ data.address }}</p>
                    </template>
                    <template #default_first>
                        <p class="text-center text-primary m-0" style="cursor: pointer" @click="
                            modal_add_kepala_keluarga.show(),
                            ($refs.search_input_kepala_keluarga.focus = false)
                        ">
                            + Tambah Data Penduduk
                        </p>
                    </template>
                </search-input>
                <div class="table-responsive no-min-height" v-if="letter.kepala_keluarga != null">
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
                                <td>{{ letter.kepala_keluarga.nik }}</td>
                                <td>{{ letter.kepala_keluarga.name }}</td>
                                <td>{{ letter.kepala_keluarga.address }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-danger text-center mt-2" v-else>
                    Belum ada data kepala keluarga
                </p>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">RT</label>
                    <input type="text" class="form-control" placeholder="XXX" v-model="letter.rt" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">RW</label>
                    <input type="text" class="form-control" placeholder="XXX" v-model="letter.rw" />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Desa/Kelurahan</label>
                    <input type="text" class="form-control" placeholder="Sigam" v-model="letter.village" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Kecamatan</label>
                    <input type="text" class="form-control" placeholder="Pulau Laut Sigam"
                        v-model="letter.sub_district" />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Kab/Kota</label>
                    <input type="text" class="form-control" placeholder="Kotabaru" v-model="letter.district" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Provinsi</label>
                    <input type="text" class="form-control" placeholder="Kalimantan Selatan"
                        v-model="letter.province" />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" class="form-control" placeholder="XXXXX" v-model="letter.zip_code" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Telepon</label>
                    <input type="text" class="form-control" placeholder="628XXXXXXXXXX" v-model="letter.phone" />
                </div>
            </div>
            <hr />
            <h5><b>DATA KEPINDAHAN</b></h5>
            <div class="mb-3">
                <label class="form-label">Alasan Pindah</label>
                <input type="text" class="form-control" placeholder="Pekerjaan, Pendidikan, Keamanan, Dll"
                    v-model="letter.reason_to_move" />
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat Tujuan Pindah</label>
                <textarea class="form-control" v-model="letter.moving_destination"
                    placeholder="Jl. XXXXXXX XXXXX"></textarea>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">RT</label>
                    <input type="text" class="form-control" placeholder="XXX" v-model="letter.moving_destination_rt" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">RW</label>
                    <input type="text" class="form-control" placeholder="XXX" v-model="letter.moving_destination_rw" />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Desa/Kelurahan</label>
                    <input type="text" class="form-control" placeholder="Sigam"
                        v-model="letter.moving_destination_village" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Kecamatan</label>
                    <input type="text" class="form-control" placeholder="Pulau Laut Sigam"
                        v-model="letter.moving_destination_sub_district" />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Kab/Kota</label>
                    <input type="text" class="form-control" placeholder="Kotabaru"
                        v-model="letter.moving_destination_district" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Provinsi</label>
                    <input type="text" class="form-control" placeholder="Kalimantan Selatan"
                        v-model="letter.moving_destination_province" />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" class="form-control" placeholder="XXXXX"
                        v-model="letter.moving_destination_zip_code" />
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Telepon</label>
                    <input type="text" class="form-control" placeholder="628XXXXXXXXXX"
                        v-model="letter.moving_destination_phone" />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Klasifikasi Pindah</label>
                    <select class="form-control" v-model="letter.move_classification">
                        <option>Dalam Satu Desa/Kelurahan</option>
                        <option>Antar Desa/Kelurahan</option>
                        <option>Antar Kecamatan</option>
                        <option>Antar Kabupaten/Kota</option>
                        <option>Antar Provinsi</option>
                    </select>
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Jenis Kepindahan</label>
                    <select class="form-control" v-model="letter.type_of_move">
                        <option>Kep. Keluarga</option>
                        <option>Kep. Kel. Dan Seluruh Anggota Kel.</option>
                        <option>Kep. Kel. Dan Sebagian Angg. Kel.</option>
                        <option>Anggota Keluarga</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Status Nomor KK Bagi Yang Tidak Pindah</label>
                    <select class="form-control" v-model="letter.status_not_move">
                        <option>Numpang KK</option>
                        <option>Membuat KK Baru</option>
                        <option>Tidak Ada Anggota Keluarga Yang Ditinggal</option>
                        <option>Nomor KK Tetap</option>
                    </select>
                </div>
                <div class="mb-3 col-sm-6">
                    <label class="form-label">Status Nomor KK Bagi Yang Pindah</label>
                    <select class="form-control" v-model="letter.status_move">
                        <option>Numpang KK</option>
                        <option>Membuat KK Baru </option>
                        <option>Nama Kepala Keluarga Dan Nomor KK Tetap</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Rencana Tanggal Pindah</label>
                <input type="date" class="form-control" v-model="letter.moving_date_plan" />
            </div>
            <div class="mb-3">
                <label class="form-label">Keluarga Yang Pindah</label>
                <search-input ref="search_input_data_keluarga" :url="origin + '/backend/resident-search'"
                    v-model="letter.data_keluarga" placeholder="Cari ...">
                    <template v-slot="{ data }">
                        <small class="text-secondary">{{ data.nik }}</small>
                        <p class="mb-0">{{ data.name }}</p>
                        <hr class="m-0" />
                        <p class="mb-0">{{ data.address }}</p>
                    </template>
                    <template #default_first>
                        <p class="text-center text-primary m-0" style="cursor: pointer" @click="
                            modal_add_data_keluarga.show(),
                            ($refs.search_input_data_keluarga.focus = false)
                        ">
                            + Tambah Data Penduduk
                        </p>
                    </template>
                </search-input>
                <div class="table-responsive no-min-height" v-if="letter.data_keluarga.length > 0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>NIK</th>
                                <th>Nama</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(data, index) in letter.data_keluarga">
                                <tr>
                                    <td style="width: 0;">
                                        <button type="button" class="btn btn-xs rounded-pill btn-icon"
                                            :class="data.active ? 'btn-danger' : 'btn-success'"
                                            @click="data.active = !data.active">
                                            <span v-if="!data.active" class="tf-icons bx bx-plus"></span>
                                            <span v-if="data.active" class="tf-icons bx bx-minus"></span>
                                        </button>
                                    </td>
                                    <td>{{ data.nik }}</td>
                                    <td>{{ data.name }}</td>
                                    <td>
                                        <i class="bx bx-trash text-danger" style="cursor:pointer;"
                                            @click="letter.data_keluarga.splice(index, 1)"></i>
                                    </td>
                                </tr>
                                <tr v-show="data.active" style="background-color: #f5f0f0;">
                                    <td colspan="9">
                                        <div class="mb-3">
                                            <label class="form-label">SHDK</label>
                                            <select class="form-control" v-model="data.shdk">
                                                <option>Suami</option>
                                                <option>Isteri</option>
                                                <option>Anak</option>
                                                <option>Menantu</option>
                                                <option>Cucu</option>
                                                <option>Orang Tua</option>
                                                <option>Mertua</option>
                                                <option>Famili Lain</option>
                                                <option>Pembantu</option>
                                                <option>Lainnya</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            </template>
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
            <div class="mb-3">
                <label class="form-label">Pejabat Penandatangan Desa</label>
                <search-input placeholder="Cari Pejabat ..." ref="search_input_penandatangan_desa"
                    v-model="penandatangan_desa" :url="origin + '/backend/officials'"
                    v-if="letter.penandatangan_desa == null" @change="(data) => letter.penandatangan_desa = data">
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
                    <span @click="letter.penandatangan_desa = null" class="tf-icons bx bx-pencil"
                        style="cursor: pointer"></span>
                </div>
            </div>
            <button type="submit" class="btn btn-success">SIMPAN</button>
        </form>
    </modal>
    <modal ref="modal_verification" :size="'lg'" @on-hide="reset" :title="'Verifikasi SKPWNI'" :close_button="true">
        <verification-letter :url="url" :letter="letter" @loading="spinner.show()" @loaded="spinner.hide()"
            @success="reset(), $refs.table.refresh()" :can_verified="letter.penandatangan_kecamatan != null">
            <template #penandatangan>
                <form method="POST" @submit.prevent="savePenandatanganKecamatan">
                    <div class="mb-3" v-if="letter.penandatangan_kecamatan != null">
                        <label class="form-label">Pejabat Penandatangan Yang Tersimpan</label>
                        <div class="form-control d-flex align-items-center justify-content-between">
                            <span>{{ letter.penandatangan_kecamatan.nip }} - {{ letter.penandatangan_kecamatan.name
                            }}</span>
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
                            <span @click="resetPenandatanganKecamatan" class="tf-icons bx bx-pencil"
                                style="cursor: pointer"></span>
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
    <modal ref="spinner" :size="'sm'" :backdrop="'static'" :keyboard="false" :header="false">
        <div class="d-flex justify-content-between align-items-center flex-column">
            <div class="spinner-border spinner-border-lg text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span>Loading ...</span>
        </div>
    </modal>

    <modal ref="modal_add_data_keluarga" :title="'Tambah Data Penduduk'" :close_button="true">
        <resident-form @on-save="modal_add_data_keluarga.hide(), spinner.show()" @finish="savedDataKeluarga">
        </resident-form>
    </modal>

    <modal ref="modal_add_kepala_keluarga" :title="'Tambah Data Penduduk'" :close_button="true">
        <resident-form @on-save="modal_add_kepala_keluarga.hide(), spinner.show()" @finish="savedKepalaKeluarga">
        </resident-form>
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
    name: "Skpwni",
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
            url: `${origin}/backend/skpwni-letters`,
            modal: null,
            modal_add_kepala_keluarga: null,
            modal_add_data_keluarga: null,
            modal_form_penandatangan_kecamatan: null,
            modal_form_penandatangan_desa: null,
            modal_verification: null,
            format_reference_number: {
                prefix: "",
                suffix: "",
            },
            penandatangan_desa: null,
            penandatangan_kecamatan: null,
            file_pendukung: null,
            letter: {
                id: "",
                kepala_keluarga: null,
                data_keluarga: [],
                penandatangan_kecamatan: null,
                penandatangan_desa: null,
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
        this.modal_add_data_keluarga = this.$refs.modal_add_data_keluarga;
        this.modal_add_kepala_keluarga = this.$refs.modal_add_kepala_keluarga;
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
        resetOfficial() {
            this.letter.official = null;
        },
        async saveLetter() {
            let response = "";
            let payload = new FormData();
            let letter_id = this.letter.id;
            // this.modal.hide();
            try {
                this.spinner.show();
                if (!this.letter.kepala_keluarga) {
                    throw "kepala_keluarga_is_null";
                }
                if (!this.letter.penandatangan_desa) {
                    throw "penandatangan_desa_is_null";
                }
                let except = ["id", "prefix", "suffix", "prefix_desa", "suffix_desa", "verified_file", "kepala_keluarga_id", "penandatangan_kecamatan_id", "penandatangan_desa_id", "user_id", "created_at", "updated_at", "file_pendukung", "is_verified", "can_verified", "can_modified", "reference_number", "kepala_keluarga", "data_keluarga", "penandatangan_kecamatan", "penandatangan_desa", "user", "key"
                ]
                for (const property in this.letter) {
                    if (!except.includes(property)) {
                       payload.append(property, this.letter[property])
                    }
                }
                payload.append('kepala_keluarga', JSON.stringify(this.letter.kepala_keluarga));
                payload.append('data_keluarga', JSON.stringify(this.letter.data_keluarga));
                payload.append('file_pendukung', this.file_pendukung ?? "");
                payload.append('penandatangan_desa_id', this.penandatangan_desa != null ? this.penandatangan_desa.id : "");
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
                    case 'kepala_keluarga_is_null':
                        await Swal.fire({
                            icon: "error",
                            title: "Gagal",
                            text: "Kepala keluarga wajib diisi !",
                            showConfirmButton: false,
                            timer: 1500,
                        });
                        break;
                    case 'file_pendukung_is_null':
                        await Swal.fire({
                            icon: "error",
                            title: "Gagal",
                            text: "File pendukung wajib dilampirkan !",
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
                    default:
                        console.log(err)
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
        edit(data) {
            this.letter = data;
            this.modal.show()
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
                    }).then(() => {
                        this.spinner.hide();
                    })
                    this.$refs.table.refresh();
                })
                .catch((e) => {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal",
                        text: "Surat gagal dihapus",
                        showConfirmButton: false,
                        timer: 1500,
                    }).then(() => {
                        this.spinner.hide();
                    })
                });
        },
        async reset() {
            this.letter = {
                id: "",
                kepala_keluarga: null,
                data_keluarga: [],
                file_pendukung: null,
                penandatangan_kecamatan: null,
                penandatangan_desa: null,
                is_verified: false,
            };
            this.file_pendukung = null;
            this.penandatangan_desa = null;
            this.penandatangan_kecamatan = null;
        },
        savedKepalaKeluarga(data) {
            this.spinner.hide();
            if (data) {
                this.letter.kepala_keluarga = data;
            }
        },
        savedDataKeluarga(data) {
            this.spinner.hide();
            if (data) {
                this.letter.data_keluarga.push(data);
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

.v-enter-active,
.v-leave-active {
    transition: opacity 0.5s ease;
}

.v-enter-from,
.v-leave-to {
    opacity: 0;
}
</style>