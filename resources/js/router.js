import { createRouter, createWebHistory } from 'vue-router'

const Home = () => import('./components/pages/Home.vue');
const Company = () => import('./components/pages/Company.vue');
const WatchParty = () => import('./components/pages/WatchParty.vue');

// import Home from './pages/Home.vue';
// import Company from '../pages/Company.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: Home,
            name: 'Home',
        },
        {
            path: '/company',
            component: Company,
            name: 'Company',
        },
        {
            path: '/watch-party',
            component: WatchParty,
            name: 'WatchParty',
        },
    ]
});

export default router;
