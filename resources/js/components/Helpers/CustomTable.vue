<template>
  <div>
    <div class="nav-item d-flex align-items-center mb-3">
      <i class="bx bx-search fs-4 lh-0"></i
      ><input
        type="text"
        class="form-control border-0 shadow-none"
        placeholder="Search..."
        aria-label="Search..."
        :value="search"
        @input="modifySearch"
      />
    </div>
    <hr class="m-0" />
    <div class="table-responsive text-nowrap">
      <table id="table" class="table" v-if="!loading">
        <thead>
          <tr>
            <th v-for="d in thead">{{ d }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0" v-if="data.length > 0">
          <tr v-for="(d, key) in data">
            <slot
              v-bind="{ ...d, key: current * per_page - per_page + key + 1 }"
            ></slot>
          </tr>
        </tbody>
        <tbody v-else>
          <tr class="text-center text-danger">
            <td :colspan="thead.length">Data tidak ditemukan !</td>
          </tr>
        </tbody>
      </table>
      <span v-else>Loading ...</span>
    </div>
    <pagination
      :max="last"
      :current="current"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import axios from "axios";
import Pagination from "./Pagination.vue";
export default {
  name: "CustomTable",
  components: {
    Pagination,
  },
  props: {
    thead: {
      type: Array,
      default: [],
    },
    url: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      loading: true,
      data: [],
      current: parseInt(this.$route.query.page ?? 1),
      last: null,
      per_page: null,
      search: this.$route.query.search ?? "",
      timeout: null,
      beforeSearch: this.$route.query.search ?? "",
    };
  },
  async created() {
    await this.getData();
    this.$watch(
      () => this.$route.query,
      async () => {
        this.current = parseInt(this.$route.query.page ?? 1);
        await this.getData();
      }
    );
  },
  methods: {
    async getData() {
      this.loading = true;

      const {
        data: { data, meta: {last_page, per_page} },
      } = await axios.get(
        `${this.url}?page=${this.current}&search=${this.search}`
      );

      if(Array.isArray(data)) {
        this.data = data;
      } else {
        console.log("Data tidak mengembalikan sebuah array!");
      }
      this.last = last_page;
      this.per_page = per_page;
      this.loading = false;
    },
    changePage(cur) {
      this.$router.push({
        name: this.$route.name,
        query: {
          page: cur,  
          search: this.$route.query.search,
        },
      });
    },
    modifySearch({ target: { value } }) {
      if (this.timeout != null) {
        clearTimeout(this.timeout);
      }
      let query = {};
      if (value != "") {
        query.search = value;
      }
      this.search = value;
      this.timeout = setTimeout(() => {
        if (this.beforeSearch != value) {
          this.getData();
          this.$router.replace({ name: this.$route.name, query: query });
          this.beforeSearch = value;
        }
        this.timeout = null;
      }, 500);
    },
    refresh() {
      this.getData();
    },
  },
};
</script>