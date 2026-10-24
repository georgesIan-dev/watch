<template>
    <v-app>
        <v-app-bar elevation="0" color="surface" height="64" class="px-2 border-b">
            <!-- <v-app-bar-nav-icon @click.stop="drawer = !drawer" /> -->

            <!-- Brand: click to go home and refresh, like the YouTube logo -->
            <button type="button" class="brand-link" title="Refresh" @click="refreshHome">
                <v-icon icon="mdi-monitor-eye" color="primary" class="mx-2" />
                <span class="text-subtitle-1 font-weight-bold">Anak ng Teteng TV</span>
            </button>

            <v-spacer />

            <!-- Avatar: also refreshes the page -->
            <v-btn icon variant="text" aria-label="Refresh" title="Refresh" @click="refreshHome">
                <v-avatar size="36" class="header-avatar">
                    <img :src="avatarSrc" alt="" class="header-avatar__img" />
                </v-avatar>
            </v-btn>
        </v-app-bar>

        <!-- <v-navigation-drawer v-model="drawer" temporary color="surface" width="260">
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
        </v-navigation-drawer> -->

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
        // Bound with :src on purpose: a static src="/..." makes Vite try to bundle it,
        // and laravel-vite-plugin has no public dir, so the build fails.
        avatarSrc: '/jalibi.png',
        navItems: [
            // { title: 'Home', path: '/', icon: 'mdi-home-variant' },
            { title: 'Company', path: '/company', icon: 'mdi-domain' },
            { title: 'Watch Party', path: '/', icon: 'mdi-youtube' },
        ],
    }),

    methods: {
        refreshHome() {
            // Full reload back to the home page (same behaviour as clicking the YouTube logo)
            window.location.assign('/')
        },
    },
}
</script>

<style scoped>
.brand-link {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px 4px 0;
    border-radius: 8px;
    background: none;
    border: 0;
    color: inherit;
    font: inherit;
    cursor: pointer;
}

/* jalibi.png is the full poster (triangle + "KEEP DISTANCE"), so zoom in on the face */
.header-avatar {
    position: relative;
    overflow: hidden;
}

.header-avatar__img {
    position: absolute;
    width: 183%;
    height: 183%;
    max-width: none;
    left: -41.7%;
    top: -15.7%;
}

.brand-link:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}
</style>