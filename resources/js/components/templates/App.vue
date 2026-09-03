<template>
    <v-app>
        <v-app-bar elevation="0" color="surface" height="64" class="px-2 border-b">
            <v-app-bar-nav-icon @click.stop="drawer = !drawer" />

            <v-icon icon="mdi-monitor-eye" color="primary" class="mx-2" />
            <span class="text-subtitle-1 font-weight-bold">Monitoring System</span>

            <v-spacer />

            <v-btn icon="mdi-logout-variant" variant="text" @click="logout" />
        </v-app-bar>

        <v-navigation-drawer v-model="drawer" temporary color="surface" width="260">
            <v-list nav class="pa-2">
                <v-list-item
                    v-for="item in navItems"
                    :key="item.path"
                    :to="item.path"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    color="primary"
                    rounded="lg"
                    class="mb-1"
                />
            </v-list>
        </v-navigation-drawer>

        <v-main>
            <router-view />
        </v-main>
    </v-app>
</template>

<script>
export default {
    name: 'AppShell',

    data: () => ({
        drawer: false,
        navItems: [
            { title: 'Home', path: '/', icon: 'mdi-home-variant' },
            { title: 'Company', path: '/company', icon: 'mdi-domain' },
            { title: 'Watch Party', path: '/watch-party', icon: 'mdi-youtube' },
        ],
    }),

    methods: {
        logout() {
            // TODO: wire up to the auth/logout endpoint
        },
    },
}
</script>