<template>
    <h4 class="fw-bold py-3 mb-4">
        BIODATA PENDUDUK WNI
    </h4>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body" v-if="user.type == 'desa'">
                    <ul class="nav nav-pills flex-column flex-md-row">
                        <li class="nav-item">
                            <span v-if="modal" class="nav-link active" @click="modal.show()"><i class="bx bx-plus me-1"
                                    style=""></i>
                                Tambah Biodata Penduduk WNI</span>
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
                                {{ data.is_verified ? "VERIFIED" : "UNVERIFIED" }}</span>
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
        :title="(letter.id == '' ? 'Tambah' : 'Ubah') + ' Biodata Penduduk WNI'" :close_button="true">
        <form @submit.prevent="saveLetter">
            <div class="row mb-3">
                <p>Catatan : <span class="text-danger">*)</span> Hanya diisi oleh salah satu pasangan keluarga tersebut
                    ( Suami / Istri )</p>
            </div>
            <h5><b>DATA KEPALA KELUARGA</b></h5>
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
                                <th></th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Jenis Kelamin</th>
                                <th>Agama</th>
                                <th>Pekerjaan</th>
                                <th>Status Perkawinan</th>
                                <th>Alamat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width: 0;">
                                    <button type="button" class="btn btn-xs rounded-pill btn-icon"
                                        :class="letter.kepala_keluarga.active ? 'btn-danger' : 'btn-success'"
                                        @click="letter.kepala_keluarga.active = !letter.kepala_keluarga.active">
                                        <span v-if="!letter.kepala_keluarga.active" class="tf-icons bx bx-plus"></span>
                                        <span v-if="letter.kepala_keluarga.active" class="tf-icons bx bx-minus"></span>
                                    </button>
                                </td>
                                <td>{{ letter.kepala_keluarga.nik }}</td>
                                <td>{{ letter.kepala_keluarga.name }}</td>
                                <td>{{ letter.kepala_keluarga.gender }}</td>
                                <td>{{ letter.kepala_keluarga.religion }}</td>
                                <td>{{ letter.kepala_keluarga.profession }}</td>
                                <td
                                    v-if="letter.kepala_keluarga.marital_status == 'jejaka' || letter.kepala_keluarga.marital_status == 'perawan'">
                                    Belum Kawin</td>
                                <td v-else-if="letter.kepala_keluarga.marital_status == 'kawin'">Kawin</td>
                                <td v-else-if="letter.kepala_keluarga.marital_status == 'cerai_hidup'">Cerai Hidup</td>
                                <td v-else>Cerai Mati</td>
                                <td>{{ letter.kepala_keluarga.address }}</td>
                            </tr>
                            <Transition>
                                <tr v-show="letter.kepala_keluarga.active" style="background-color: #f5f0f0;">
                                    <td colspan="8">
                                        <div class="row">
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">RT</label>
                                                <input type="text" class="form-control" placeholder="RT"
                                                    v-model="letter.rt" />
                                            </div>
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">RW</label>
                                                <input type="text" class="form-control" placeholder="RW"
                                                    v-model="letter.rw" />
                                            </div>
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">Kode Pos</label>
                                                <input type="text" class="form-control" placeholder="Kode Pos"
                                                    v-model="letter.zip_code" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">Telepon</label>
                                                <input type="tel" class="form-control" placeholder="Telepon"
                                                    v-model="letter.phone" />
                                            </div>
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">Kode - Nama Provinsi</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Kode - Nama Provinsi" v-model="letter.province" />
                                            </div>
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">Kode - Nama Kabupaten / Kota</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Kode - Nama Kabupaten / Kota"
                                                    v-model="letter.district" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">Kode - Nama Kecamatan</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Kode - Nama Kecamatan" v-model="letter.sub_district" />
                                            </div>
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">Kode - Nama Kelurahan / Desa</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Kode - Nama Kelurahan / Desa"
                                                    v-model="letter.village" />
                                            </div>
                                            <div class="mb-3 col-sm-4">
                                                <label class="form-label">Nama Dusun / Dukuh / Kampung</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Nama Dusun / Dukuh / Kampung" v-model="letter.dusun" />
                                            </div>
                                        </div>
                                        <hr />
                                        <div class="row">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nik Ibu</label>
                                                <input type="text" class="form-control" placeholder="Nik Ibu"
                                                    v-model="letter.kepala_keluarga.mother_nik" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nama Ibu</label>
                                                <input type="text" class="form-control" placeholder="Nama Ibu"
                                                    v-model="letter.kepala_keluarga.mother_name" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nik Ayah</label>
                                                <input type="text" class="form-control" placeholder="Nik Ayah"
                                                    v-model="letter.kepala_keluarga.father_nik" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nama Ayah</label>
                                                <input type="text" class="form-control" placeholder="Nama Ayah"
                                                    v-model="letter.kepala_keluarga.father_name" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Golongan Darah</label>
                                                <select class="form-control" v-model="letter.kepala_keluarga.blood_type"
                                                    placeholder="Golongan Darah">
                                                    <option>A</option>
                                                    <option>B</option>
                                                    <option>AB</option>
                                                    <option>O</option>
                                                    <option>A+</option>
                                                    <option>A-</option>
                                                    <option>B+</option>
                                                    <option>B-</option>
                                                    <option>AB+</option>
                                                    <option>AB-</option>
                                                    <option>O+</option>
                                                    <option>O-</option>
                                                    <option>Tidak Tahu</option>
                                                </select>
                                            </div>
                                            <!-- <div class="col-sm-4 mb-3">
                                                <label class="form-label">Status Hub Dlm Keluarga</label>
                                                <input type="text" class="form-control" placeholder="Status Hub Dlm Keluarga"
                                                    v-model="letter.kepala_keluarga.family_status" />
                                            </div> -->
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Pendidikan Terakhir</label>
                                                <select class="form-control"
                                                    v-model="letter.kepala_keluarga.last_study">
                                                    <option>Tidak/Belum Sekolah</option>
                                                    <option>Tidak Tamat SD/Sederajat</option>
                                                    <option>Tamat SD/Sederajat</option>
                                                    <option>SLTP/Sederajat</option>
                                                    <option>SLTA/Sederjat</option>
                                                    <option>Diploma I/II</option>
                                                    <option>Akademi/Diploma III/S. Muda</option>
                                                    <option>Diploma IV/Strata I</option>
                                                    <option>Strata II </option>
                                                    <option>Strata III </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="letter.kepala_keluarga.have_physical_mental_disorders">
                                                <label class="form-check-label" for="defaultCheck3"> Punya Kelainan
                                                    Fisik & Mental ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="letter.kepala_keluarga.have_physical_mental_disorders">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Penyandang Cacat</label>
                                                <select class="form-control"
                                                    v-model="letter.kepala_keluarga.disabilities">
                                                    <option>Cacat Fisik</option>
                                                    <option>Cacat Netra/Buta</option>
                                                    <option>Cacat Rungu/Wicara</option>
                                                    <option>Cacat Mental/Jiwa</option>
                                                    <option>Cacat Fisik dan Mental</option>
                                                    <option>Cacat lainnya</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="letter.kepala_keluarga.have_paspor">
                                                <label class="form-check-label" for="defaultCheck3"> Punya Paspor ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="letter.kepala_keluarga.have_paspor">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nomor Paspor</label>
                                                <input type="text" class="form-control" placeholder="Nomor Paspor"
                                                    v-model="letter.kepala_keluarga.paspor_number" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Tgl. Berakhir Paspor</label>
                                                <input type="date" class="form-control"
                                                    v-model="letter.kepala_keluarga.paspor_due_date" />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="letter.kepala_keluarga.have_akta">
                                                <label class="form-check-label" for="defaultCheck3"> Punya Akta Lahir/
                                                    Surat Lahir ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="letter.kepala_keluarga.have_akta">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nomor Akta Kelahiran Surat Kenal Lahir</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Nomor Akta Kelahiran Surat Kenal Lahir"
                                                    v-model="letter.kepala_keluarga.birth_certificate_number" />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="letter.kepala_keluarga.have_akta_nikah">
                                                <label class="form-check-label" for="defaultCheck3"><span
                                                        class="text-danger">*)</span> Punya Akta Perkawinan/ Buku Nikah
                                                    ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="letter.kepala_keluarga.have_akta_nikah">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Nomor Akta
                                                    Perkawinan/ Buku Nikah</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Nomor Akta Perkawinan/ Buku Nikah"
                                                    v-model="letter.kepala_keluarga.marriage_certificate_number" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Tanggal
                                                    Perkawinan</label>
                                                <input type="date" class="form-control"
                                                    v-model="letter.kepala_keluarga.marriage_date" />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="letter.kepala_keluarga.have_akta_cerai">
                                                <label class="form-check-label" for="defaultCheck3"><span
                                                        class="text-danger">*)</span> Punya Akta Cerai/ Surat Cerai ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="letter.kepala_keluarga.have_akta_cerai">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Nomor Akta
                                                    Perceraian/ Buku Cerai</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Nomor Akta Perceraian/ Buku Cerai"
                                                    v-model="letter.kepala_keluarga.divorce_certificate_number" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Tanggal
                                                    Perceraian</label>
                                                <input type="date" class="form-control"
                                                    v-model="letter.kepala_keluarga.divorce_date" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </Transition>
                        </tbody>
                    </table>
                </div>
                <p class="text-danger text-center mt-2" v-else>
                    Belum ada data kepala keluarga
                </p>
            </div>
            <hr />
            <h5><b>DATA KELUARGA</b></h5>
            <div class="mb-3">
                <label class="form-label">Data Umum Keluarga</label>
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
                                <th>Jenis Kelamin</th>
                                <th>Agama</th>
                                <th>Pekerjaan</th>
                                <th>Status Perkawinan</th>
                                <th>Alamat</th>
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
                                    <td>{{ data.gender }}</td>
                                    <td>{{ data.religion }}</td>
                                    <td>{{ data.profession }}</td>
                                    <td v-if="data.marital_status == 'jejaka' || data.marital_status == 'perawan'">Belum
                                        Kawin</td>
                                    <td v-else-if="data.marital_status == 'kawin'">Kawin</td>
                                    <td v-else-if="data.marital_status == 'cerai_hidup'">Cerai Hidup</td>
                                    <td v-else>Cerai Mati</td>
                                    <td>{{ data.address }}</td>
                                    <td>
                                        <i class="bx bx-trash text-danger" style="cursor:pointer;"
                                            @click="letter.data_keluarga.splice(index, 1)"></i>
                                    </td>
                                </tr>
                                <tr v-show="data.active" style="background-color: #f5f0f0;">
                                    <td colspan="9">
                                        <div class="row">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nik Ibu</label>
                                                <input type="text" class="form-control" placeholder="Nik Ibu"
                                                    v-model="data.mother_nik" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nama Ibu</label>
                                                <input type="text" class="form-control" placeholder="Nama Ibu"
                                                    v-model="data.mother_name" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nik Ayah</label>
                                                <input type="text" class="form-control" placeholder="Nik Ayah"
                                                    v-model="data.father_nik" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nama Ayah</label>
                                                <input type="text" class="form-control" placeholder="Nama Ayah"
                                                    v-model="data.father_name" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4 mb-3">
                                                <label class="form-label">Golongan Darah</label>
                                                <select class="form-control" v-model="data.blood_type"
                                                    placeholder="Golongan Darah">
                                                    <option>A</option>
                                                    <option>B</option>
                                                    <option>AB</option>
                                                    <option>O</option>
                                                    <option>A+</option>
                                                    <option>A-</option>
                                                    <option>B+</option>
                                                    <option>B-</option>
                                                    <option>AB+</option>
                                                    <option>AB-</option>
                                                    <option>O+</option>
                                                    <option>O-</option>
                                                    <option>Tidak Tahu</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-4 mb-3">
                                                <label class="form-label">Status Hub Dlm Keluarga</label>
                                                <select class="form-control" v-model="data.family_status">
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
                                            <div class="col-sm-4 mb-3">
                                                <label class="form-label">Pendidikan Terakhir</label>
                                                <select class="form-control" v-model="data.last_study">
                                                    <option>Tidak/Belum Sekolah</option>
                                                    <option>Tidak Tamat SD/Sederajat</option>
                                                    <option>Tamat SD/Sederajat</option>
                                                    <option>SLTP/Sederajat</option>
                                                    <option>SLTA/Sederjat</option>
                                                    <option>Diploma I/II</option>
                                                    <option>Akademi/Diploma III/S. Muda</option>
                                                    <option>Diploma IV/Strata I</option>
                                                    <option>Strata II </option>
                                                    <option>Strata III </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="data.have_physical_mental_disorders">
                                                <label class="form-check-label" for="defaultCheck3"> Punya Kelainan
                                                    Fisik & Mental ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="data.have_physical_mental_disorders">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Penyandang Cacat</label>
                                                <select class="form-control" v-model="data.disabilities">
                                                    <option>Cacat Fisik</option>
                                                    <option>Cacat Netra/Buta</option>
                                                    <option>Cacat Rungu/Wicara</option>
                                                    <option>Cacat Mental/Jiwa</option>
                                                    <option>Cacat Fisik dan Mental</option>
                                                    <option>Cacat lainnya</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="data.have_paspor">
                                                <label class="form-check-label" for="defaultCheck3"> Punya Paspor ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="data.have_paspor">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nomor Paspor</label>
                                                <input type="text" class="form-control" placeholder="Nomor Paspor"
                                                    v-model="data.paspor_number" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Tgl. Berakhir Paspor</label>
                                                <input type="date" class="form-control"
                                                    v-model="data.paspor_due_date" />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="data.have_akta">
                                                <label class="form-check-label" for="defaultCheck3"> Punya Akta Lahir/
                                                    Surat Lahir ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="data.have_akta">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label">Nomor Akta Kelahiran Surat Kenal Lahir</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Nomor Akta Kelahiran Surat Kenal Lahir"
                                                    v-model="data.akta_number" />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="data.have_akta_nikah">
                                                <label class="form-check-label" for="defaultCheck3"><span
                                                        class="text-danger">*)</span> Punya Akta Perkawinan/ Buku Nikah
                                                    ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="data.have_akta_nikah">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Nomor Akta
                                                    Perkawinan/ Buku Nikah</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Nomor Akta Perkawinan/ Buku Nikah"
                                                    v-model="data.marriage_certificate_number" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Tanggal
                                                    Perkawinan</label>
                                                <input type="date" class="form-control" v-model="data.marriage_date" />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    v-model="data.have_akta_cerai">
                                                <label class="form-check-label" for="defaultCheck3"><span
                                                        class="text-danger">*)</span> Punya Akta Cerai/ Surat Cerai ?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" v-if="data.have_akta_cerai">
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Nomor Akta
                                                    Perceraian/ Buku Cerai</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Nomor Akta Perceraian/ Buku Cerai"
                                                    v-model="data.divorce_certificate_number" />
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"><span class="text-danger">*)</span> Tanggal
                                                    Perceraian</label>
                                                <input type="date" class="form-control" v-model="data.divorce_date" />
                                            </div>
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
            <div class="row">
                <div class="mb-3 col-lg-6">
                    <label class="form-label">Nama Ketua RT</label>
                    <input type="text" class="form-control" placeholder="Nama Ketua RT" v-model="letter.rt_name" />
                </div>
                <div class="mb-3 col-lg-6">
                    <label class="form-label">Nama Ketua RW</label>
                    <input type="text" class="form-control" placeholder="Nama Ketua RW" v-model="letter.rw_name" />
                </div>
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
    <modal ref="modal_verification" :size="'lg'" @on-hide="reset" :title="'Verifikasi Biodata Penduduk WNI'"
        :close_button="true">
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
        <resident-form @on-save="modal_add_kepala_keluarga.hide(), spinner.show()" @finish="savedDataKeluarga">
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
    name: "BiodataPendudukWni",
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
            url: `${origin}/backend/biodata-penduduk-wni-letters`,
            modal: null,
            modal_add_kepala_keluarga: null,
            modal_add_data_keluarga: null,
            modal_form_penandatangan_kecamatan: null,
            modal_form_penandatangan_desa: null,
            modal_verification: null,
            penandatangan_desa: null,
            penandatangan_kecamatan: null,
            file_pendukung: null,
            letter: {
                id: "",
                kepala_keluarga: null,
                rt: "",
                rw: "",
                zip_code: "",
                phone: "",
                province: "",
                district: "",
                sub_district: "",
                village: "",
                dusun: "",
                data_keluarga: [],
                rt_name: "",
                rw_name: "",
                file_pendukung: null,
                penandatangan_kecamatan: null,
                penandatangan_desa: null,
                is_verified: false,
            },
            spinner: null,
        };
    },
    computed: {
        ...mapState(useUserStore, ['user']),
    },
    mounted() {
        this.modal = this.$refs.modal;
        this.modal_add_data_keluarga = this.$refs.modal_add_data_keluarga;
        this.modal_add_kepala_keluarga = this.$refs.modal_add_kepala_keluarga;
        this.modal_form_penandatangan_kecamatan = this.$refs.modal_form_penandatangan_kecamatan;
        this.modal_form_penandatangan_desa = this.$refs.modal_form_penandatangan_desa;
        this.modal_verification = this.$refs.modal_verification;
        this.spinner = this.$refs.spinner;
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
            try {
                this.spinner.show();
                if (!this.letter.kepala_keluarga) {
                    throw "kepala_keluarga_is_null";
                }
                if (!this.letter.penandatangan_desa) {
                    throw "penandatangan_desa_is_null";
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
            data.kepala_keluarga.have_physical_mental_disorders = data.kepala_keluarga.disabilities != null;
            data.kepala_keluarga.have_paspor = data.kepala_keluarga.paspor_number != null || data.kepala_keluarga.paspor_due_date != null;
            data.kepala_keluarga.have_akta = data.kepala_keluarga.birth_certificate_number != null;
            data.kepala_keluarga.have_akta_nikah = data.kepala_keluarga.marriage_certificate_number != null || data.kepala_keluarga.marriage_date != null;
            data.kepala_keluarga.have_akta_cerai = data.kepala_keluarga.divorce_certificate_number != null || data.kepala_keluarga.divorce_date != null;
            data.data_keluarga.forEach((p, key) => {
                data.data_keluarga[key].have_physical_mental_disorders = p.disabilities != null;
                data.data_keluarga[key].have_paspor = p.paspor_number != null || p.paspor_due_date != null;
                data.data_keluarga[key].have_akta = p.birth_certificate_number != null;
                data.data_keluarga[key].have_akta_nikah = p.marriage_certificate_number != null || p.marriage_date != null;
                data.data_keluarga[key].have_akta_cerai = p.divorce_certificate_number != null || p.divorce_date != null;
            })
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
                kepala_keluarga: null,
                rt: "",
                rw: "",
                zip_code: "",
                phone: "",
                province: "",
                district: "",
                sub_district: "",
                village: "",
                dusun: "",
                data_keluarga: [],
                rt_name: "",
                rw_name: "",
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
                // data.active = false;
                // data.have_paspor = false;
                // data.have_akta = false;
                // data.have_akta_nikah = false;
                // data.have_akta_cerai = false;
                this.letter.kepala_keluarga = data;
            }
        },
        savedDataKeluarga(data) {
            this.spinner.hide();
            if (data) {
                // data.foreach((p, key) => {
                //     data[key].active = false;
                //     data[key].have_paspor = false;
                //     data[key].have_akta = false;
                //     data[key].have_akta_nikah = false;
                //     data[key].have_akta_cerai = false;
                //     data[key].rt = "";
                // })
                this.letter.data_keluarga = data;
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
                let that = this
                iframe.onload = function () {
                    that.spinner.hide();
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