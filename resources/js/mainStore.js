import { defineStore } from 'pinia'

export const useSampleStore = defineStore('sampleStore', {
    state: () => {
        return {
            // Global Variables
            count: 0,
            name: 'Your Name!',
        }
    },

    getters: {
        // Global Computed
        doubleCount: (state) => state.count * 2,
    },

    actions: {
        // Global Functions
        increment() {
            this.count++
        },
    },
})
