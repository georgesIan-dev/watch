import { defineStore } from 'pinia'

export const useSampleStore = defineStore('sampleStore', {
    state: () => ({
        count: 0,
        name: 'Your Name!',
    }),

    getters: {
        doubleCount: (state) => state.count * 2,
    },

    actions: {
        increment() {
            this.count++
        },
    },
})
