<template>
  <div class="modal fade" ref="modal" tabindex="-1" aria-hidden="true">
    <div
      class="modal-dialog modal-dialog-centered"
      :class="sizeComputed"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header" v-if="header">
          <h5 class="modal-title" v-if="title != ''">
            {{ title }}
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
            v-if="close_button"
          ></button>
        </div>
        <div class="modal-body">
          <slot></slot>
        </div>
        <div class="modal-footer" v-if="$slots.footer">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  name: "Modal",
  emits: ["on-hide", "on-show"],
  props: {
    size: {
      type: String,
    },
    backdrop: {
      type: [Boolean, String],
      default: true,
    },
    keyboard: {
      type: Boolean,
      default: true,
    },
    header: {
      type: Boolean,
      default: true,
    },
    title: {
      type: String,
      default: "",
    },
    close_button: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      self: null,
    };
  },
  computed: {
    sizeComputed() {
      if (this.size == "modal-fullscreen") {
        return "modal-fullscreen";
      }

      if (this.size == "xl") {
        return "modal-xl";
      }

      if (this.size == "lg") {
        return "modal-lg";
      }

      if (this.size == "sm") {
        return "modal-sm";
      }

      return "";
    },
  },
  mounted() {
    this.self = new bootstrap.Modal(this.$refs.modal, {
      backdrop: this.backdrop,
      keyboard: this.keyboard,
    });
    this.$refs.modal.addEventListener("hidden.bs.modal", (event) => {
      this.$emit("on-hide");
    });
    this.$refs.modal.addEventListener("shown.bs.modal", (event) => {
      this.$emit("on-show");
    });
  },
  methods: {
    async show() {
      await this.self.show();
    },
    async hide() {
      await this.self.hide();
    },
  },
};
</script>