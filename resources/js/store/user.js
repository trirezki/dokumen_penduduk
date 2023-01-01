import { defineStore } from 'pinia'
import axios from 'axios'

export const useUserStore = defineStore({
  id: 'user',
  state: () => ({
    user: null
  }),
  getters: {
    getRoles: (state) => {
      return state.user;
    }
  },
  actions: {
    async fetchUser() {
      axios.get(`${window.location.origin}/me`).then(response => {
        this.user = response.data;
      })
    }
  }
})