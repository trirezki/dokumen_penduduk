<template>
  <nav aria-label="Page navigation" class="mt-2" v-if="max > 1">
    <ul class="pagination pagination-lg justify-content-center">
      <li class="page-item prev">
        <span
          class="page-link"
          v-if="parseInt((current ?? 1)) > 1"
          @click="changePage(current - 1)"
        >
          <i class="tf-icon bx bx-chevrons-left"></i>
        </span>
      </li>
      <li
        v-for="n in max"
        class="page-item"
        :class="{ active: n == (current ?? 1) }"
        @click="changePage(n)"
      >
        <span
          class="page-link"
          >{{ n }}</span
        >
      </li>
      <li class="page-item next">
        <span
          class="page-link"
          v-if="parseInt((current ?? 1)) < max"
          @click="changePage(current + 1)"
        >
          <i class="tf-icon bx bx-chevrons-right"></i>
        </span>
      </li>
    </ul>
  </nav>
</template>
<script>
export default {
  name: "Pagination",
  props: {
    current: Number,
    max: {
      type: Number,
      default: 1,
    },
  },
  methods: {
    changePage(number) {
      this.$emit('change-page', number);
    }
  }
};
</script>