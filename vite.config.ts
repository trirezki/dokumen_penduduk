import { defineConfig } from 'vite'
import autoprefixer from 'autoprefixer'
import laravel from 'vite-plugin-laravel'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
	plugins: [
		vue(),
		laravel(),
	],
	resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
})
