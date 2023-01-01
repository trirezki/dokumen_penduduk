<template>
  <div tabindex="-1" class="position-relative" ref="parent">
    <input type="text" class="form-control" :style="
      show
        ? 'border-bottom-right-radius: 0; border-bottom-left-radius: 0'
        : ''
    " :placeholder="placeholder" v-model="value" @focus="onFocus" @blur="onFocusOut($event)" />
    <div class="list-group position-absolute bg-white w-100" style="
        border-top-right-radius: 0;
        border-top-left-radius: 0;
        max-height: 200px;
        overflow-y: scroll;
        z-index: 3;
      " v-show="show" ref="listSearch">
      <span class="list-group-item">
        <slot name="default_first"></slot>
      </span>
      <span v-if="!loading" style="cursor: pointer" class="list-group-item list-group-item-action" v-for="(data, n) in resultSearch"
        @click="onSelected(data)">
        <slot :data="data" :key="n"></slot>
      </span>
      <span v-else style="cursor: pointer" class="list-group-item list-group-item-action">
        Loading ...
      </span>
    </div>
  </div>
</template>
<script>
import axios from "axios";
export default {
  name: "SearchInput",
  props: {
    modelValue: Object | Array,
    placeholder: {
      type: String,
      default: "",
    },
    url: {
      type: String,
    },
    filter: Object,
  },
  emits: ["update:modelValue", "change"],
  data() {
    return {
      success_search: null,
      value: "",
      focus: false,
      resultSearch: [],
      timeoutSearch: null,
      loading: false,
      busy: false,
      next: this.url,
    };
  },
  computed: {
    show() {
      return this.focus;
    },
  },
  mounted() {
    this.$watch(
      () => [this.value, this.show],
      () => {
        if (this.show && this.value != this.success_search) {
          this.onSearch(this.value);
          this.$refs.listSearch.addEventListener("scroll", this.handleScroll);
        }
      },
      { immediate: true }
    );
  },
  methods: {
    handleScroll() {
      const { scrollHeight, scrollTop, clientHeight } = this.$refs.listSearch;
      if (scrollTop + clientHeight >= scrollHeight - 3) {
        this.getData();
      }
    },
    search() {
      this.onSearch(this.value);
      this.$refs.listSearch.addEventListener("scroll", this.handleScroll);
    },
    onSearch(value) {
      if (this.timeoutSearch != null) {
        clearTimeout(this.timeoutSearch);
      }
      this.timeoutSearch = setTimeout(() => {
        this.loading = true;
        this.resultSearch = [];
        this.busy = false;
        this.next = `${this.url}?search=${value}`;
        this.getData(value);
      }, 500);
    },
    // searchRequest(value) {
    //   this.loading = true;
    //   axios.get(`${this.url}?search=${value}`).then((response) => {
    //     this.resultSearch = response.data;

    //     this.loading = false;
    //   });
    // },
    getData() {
      if (this.next && !this.busy) {
        this.busy = true;
        let filter = "";
        if (this.filter) {
          for (let [key, value] of Object.entries(this.filter)) {
            filter += `&${key}=${value}`;
          }
        }
        axios.get(this.next + filter).then((response) => {
          this.next = response.data.links.next
            ? response.data.links.next + "&search=" + this.value + filter
            : null;

          response.data.data.forEach((p) => {
            this.resultSearch.push(p);
          });
          this.busy = false;
          this.loading = false;
          this.success_search = this.value;
        });
      }
    },
    onSelected(data) {
      // if (this.default_response) {
      //   for (var key in this.default_response) {
      //     data[key] = this.default_response[key];
      //   }
      // }
      let result = this.modelValue;
      if (Array.isArray(this.modelValue)) {
        if (!this.modelValue.some(p => p.id == data.id)) {
          result.push(data);
        }
      } else {
        result = data;
      }
      this.$emit("update:modelValue", result);
      this.$emit("change", result);
      this.focus = false;
    },
    onFocus() {
      this.focus = true;
    },
    onFocusOut(event) {
      if (!this.$refs.parent.contains(event.relatedTarget)) {
        this.focus = false;
      }
    },
  },
};
</script>