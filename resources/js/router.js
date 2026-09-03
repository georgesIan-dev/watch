import { createRouter, createWebHistory } from 'vue-router'

const Home = () => import('./components/pages/Home.vue')
const Company = () => import('./components/pages/Company.vue')
const WatchParty = () => import('./components/pages/WatchParty.vue')

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'Home', component: Home },
        { path: '/company', name: 'Company', component: Company },
        { path: '/watch-party', name: 'WatchParty', component: WatchParty },
    ],
})

export default router
