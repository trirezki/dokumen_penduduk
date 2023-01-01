import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from '../../components/Account.vue'
import CreateAccount from '../../components/CreateAccount.vue'
const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            path: '/tambah',
            component: CreateAccount,
        }
    ],
})
const app = createApp(App)
app.use(router)
app.mount('#app_village_account')
