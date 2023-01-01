import { createApp } from 'vue'
import { createPinia } from 'pinia'

import router from './router'

import "./assets/vendor/fonts/boxicons.css";
import "./assets/vendor/css/core.css";
import "./assets/vendor/css/theme-default.css";
import "./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css";
import "./assets/vendor/libs/apex-charts/apex-charts.css";
import "./assets/css/demo.css";

import "./assets/js/config.js";
import "./assets/vendor/js/helpers.js";
import "./assets/vendor/libs/jquery/jquery.js";
import "./assets/vendor/libs/popper/popper.js";
import "./assets/vendor/js/bootstrap.js";
import "./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js";
import "./assets/vendor/js/menu.js";
import "./assets/vendor/libs/apex-charts/apexcharts.js";
import "./assets/js/main.js";
import "./assets/js/dashboards-analytics.js";

import App from './App.vue'

const pinia = createPinia()
const app = createApp(App)
app.use(pinia)
app.use(router)
app.mount('#app')
