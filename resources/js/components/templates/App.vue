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

            <!-- Avatar: shows the "keep distance" effect -->
            <v-btn icon variant="text" aria-label="Keep distance!" title="Keep distance!" @click="toggleKeepDistance">
                <v-avatar size="36" class="header-avatar" :class="{ 'header-avatar--laugh': keepDistance.open }">
                    <img :src="avatarSrc" alt="" class="header-avatar__img" />
                </v-avatar>
            </v-btn>
        </v-app-bar>

        <!-- KEEP DISTANCE EFFECT: kpdstnc.png flies out of the avatar, shockwaves push outward -->
        <Transition name="kd">
            <div
                v-if="keepDistance.open"
                class="kd-overlay"
                :style="keepDistance.origin"
                @click="closeKeepDistance"
            >
                <span class="kd-ring" aria-hidden="true"></span>
                <span class="kd-ring kd-ring--2" aria-hidden="true"></span>
                <span class="kd-ring kd-ring--3" aria-hidden="true"></span>
                <img :src="keepDistanceSrc" alt="Keep distance!" class="kd-image" />
            </div>
        </Transition>

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
// How long the effect stays up before closing by itself
const KEEP_DISTANCE_MS = 2600

export default {
    name: 'AppShell',

    data: () => ({
        drawer: false,
        // Bound with :src on purpose: a static src="/..." makes Vite try to bundle it,
        // and laravel-vite-plugin has no public dir, so the build fails.
        avatarSrc: '/jalibi.png',
        keepDistanceSrc: '/kpdstnc.png',
        keepDistance: {
            open: false,
            origin: {}, // where the avatar is, so the image flies out of it
        },
        navItems: [
            // { title: 'Home', path: '/', icon: 'mdi-home-variant' },
            { title: 'Company', path: '/company', icon: 'mdi-domain' },
            { title: 'Watch Party', path: '/', icon: 'mdi-youtube' },
        ],
    }),

    mounted() {
        // Preload so the effect isn't waiting on the image the first time
        new Image().src = this.keepDistanceSrc
    },

    beforeUnmount() {
        this.closeKeepDistance()
    },

    methods: {
        refreshHome() {
            // Full reload back to the home page (same behaviour as clicking the YouTube logo)
            window.location.assign('/')
        },

        toggleKeepDistance(event) {
            if (this.keepDistance.open) {
                this.closeKeepDistance()
                return
            }

            // Offset from the screen centre to the avatar, used by the fly-out animation
            const rect = event.currentTarget.getBoundingClientRect()
            const x = rect.left + rect.width / 2 - window.innerWidth / 2
            const y = rect.top + rect.height / 2 - window.innerHeight / 2
            this.keepDistance.origin = { '--kd-from-x': `${x}px`, '--kd-from-y': `${y}px` }
            this.keepDistance.open = true

            window.addEventListener('keydown', this.onKeepDistanceKey)
            this._keepDistanceTimer = setTimeout(this.closeKeepDistance, KEEP_DISTANCE_MS)
        },

        closeKeepDistance() {
            clearTimeout(this._keepDistanceTimer)
            window.removeEventListener('keydown', this.onKeepDistanceKey)
            this.keepDistance.open = false
        },

        onKeepDistanceKey(event) {
            if (event.key === 'Escape') this.closeKeepDistance()
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

/* Avatar does a little laugh-shake while the effect is up */
.header-avatar--laugh {
    animation: kd-laugh 0.5s ease-in-out;
}

@keyframes kd-laugh {
    0%, 100% { transform: rotate(0); }
    20% { transform: rotate(-14deg) scale(1.1); }
    40% { transform: rotate(12deg) scale(1.1); }
    60% { transform: rotate(-8deg); }
    80% { transform: rotate(5deg); }
}

/* ===== KEEP DISTANCE EFFECT ===== */
.kd-overlay {
    --kd-copper: 200, 110, 60; /* the orange-brown of jalibi.png */
    position: fixed;
    inset: 0;
    z-index: 3000; /* above the app bar, chat overlay and floating player */
    display: grid;
    place-items: center;
    background: radial-gradient(circle, rgba(var(--kd-copper), 0.25), rgba(0, 0, 0, 0.82) 70%);
    backdrop-filter: blur(4px);
    cursor: pointer;
    overflow: hidden;
}

/* Rings and image share one grid cell, so all are centred */
.kd-overlay > * {
    grid-area: 1 / 1;
}

.kd-image {
    width: min(80vw, 460px);
    max-height: 75vh;
    object-fit: contain;
    filter: drop-shadow(0 0 28px rgba(var(--kd-copper), 0.7));
    animation:
        kd-fly-out 0.65s cubic-bezier(0.34, 1.56, 0.64, 1) both,
        kd-breathe 1.4s ease-in-out 0.65s infinite;
}

/* From the avatar (tiny, spinning) to the middle of the screen, with an overshoot */
@keyframes kd-fly-out {
    from {
        transform: translate(var(--kd-from-x), var(--kd-from-y)) scale(0.06) rotate(-200deg);
        opacity: 0;
    }
    35% {
        opacity: 1;
    }
    to {
        transform: none;
        opacity: 1;
    }
}

@keyframes kd-breathe {
    0%, 100% { transform: scale(1) rotate(0); }
    50% { transform: scale(1.04) rotate(1.5deg); }
}

/* Shockwaves pushing outward: "keep distance" */
.kd-ring {
    width: 220px;
    aspect-ratio: 1;
    border: 4px solid rgba(var(--kd-copper), 0.9);
    border-radius: 50%;
    opacity: 0;
    animation: kd-shockwave 1.3s ease-out 0.45s infinite;
}

.kd-ring--2 {
    animation-delay: 0.85s;
}

.kd-ring--3 {
    animation-delay: 1.25s;
}

@keyframes kd-shockwave {
    from {
        transform: scale(0.4);
        opacity: 0.9;
    }
    to {
        transform: scale(4.5);
        opacity: 0;
        border-width: 1px;
    }
}

/* Vue <Transition name="kd"> */
.kd-enter-active {
    transition: opacity 0.2s ease;
}

.kd-leave-active {
    transition: opacity 0.3s ease;
}

.kd-leave-active .kd-image {
    animation: none; /* a running animation would override the shrink */
    transition: transform 0.3s ease-in;
    transform: scale(0.7) rotate(8deg);
}

.kd-enter-from,
.kd-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .header-avatar--laugh,
    .kd-image {
        animation: none;
    }

    .kd-ring {
        display: none;
    }

    .kd-leave-active .kd-image {
        transition: none;
        transform: none;
    }
}

.brand-link:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}
</style>