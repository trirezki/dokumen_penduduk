<template>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container" v-if="$route.name != 'NotFound'">
            <sidebar></sidebar>
            <div class="layout-page">
                <navbar></navbar>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row">
                            <router-view v-if="user != null"></router-view>
                        </div>
                    </div>
                </div>
                <footer></footer>
            </div>
        </div>
        <router-view v-else></router-view>
    </div>
</template>
<script>
import Sidebar from './components/Layouts/Sidebar.vue'
import Navbar from './components/Layouts/Navbar.vue'
import Footer from './components/Layouts/Footer.vue'
import { mapActions, mapState } from 'pinia'
import { useUserStore } from './store/user'
export default {
    name: "App",
    components: {
        Sidebar,
        Navbar,
        Footer,
    },
    computed: {
        ...mapState(useUserStore, ['user']),
    },
    created() {
        this.fetchUser();
        this.$watch(
        () => this.$route.name,
        async () => {
            let backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove()
            }
        }
        );
    },
    methods: {
        ...mapActions(useUserStore, ['fetchUser'])
    }
}
</script>
<style>
.swal2-container {
    z-index: 2000;
}
.table-responsive {
    min-height: 300px;
}
.table-responsive.no-min-height {
    min-height:min-content;
}
</style>