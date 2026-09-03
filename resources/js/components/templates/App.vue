<template>
    <v-app class="twitch-app-bg">
        <!-- Top Playful App Bar -->
        <v-app-bar
        elevation="0"
        class="comic-app-bar px-2"
        height="70"
        >
        <v-btn
            icon
            class="comic-nav-btn mr-2"
            @click.stop="drawer = !drawer"
        >
            <v-icon size="28" color="black">mdi-menu</v-icon>
        </v-btn>

        <!-- Comic Logo Badge -->
        <div class="comic-badge">
            <v-icon color="yellow-accent-4" class="mr-1">mdi-clipboard-list-outline</v-icon>
            <span class="comic-title">MONITORING SYSTEM</span>
            <span class="comic-tag">v1</span>
        </div>

        <v-spacer />
        <v-icon
            size="large"
            class="cursor-pointer hover:opacity-80 transition-all duration-200"
            @click="logout"
        >
            mdi-logout-variant
        </v-icon>
        </v-app-bar>

        <!-- Side Navigation Drawer -->
        <v-navigation-drawer
        v-model="drawer"
        temporary
        class="comic-drawer"
        width="280"
        >
        <div class="drawer-header pa-4 text-center">
            <span class="drawer-title">MENU</span>
        </div>

        <v-list class="pa-3">
            <v-list-item
            v-for="(item, i) in items"
            :key="i"
            :to="item.path"
            class="comic-list-item mb-3"
            active-class="comic-item-active"
            >
            <template v-slot:prepend>
                <div class="icon-bubble mr-3">
                <v-icon :icon="item.icon" color="black" size="22"></v-icon>
                </div>
            </template>
            
            <v-list-item-title class="comic-item-title">
                {{ item.title }}
            </v-list-item-title>
            </v-list-item>
        </v-list>
        </v-navigation-drawer>

        <v-main class="pa-4">
        <router-view></router-view>
        </v-main>
    </v-app>
</template>

<script>
export default {
    data: () => ({
        drawer: false,
        items: [
        {
            title: 'Home',
            path: '/',
            icon: 'mdi-home-variant'
        },
        {
            title: 'Company',
            path: '/Company',
            icon: 'mdi-domain'
        },
        {
            title: 'Watch Party',
            path: '/watch-party',
            icon: 'mdi-youtube'
        },
        ],
    }),
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&display=swap');

/* Global Font & App Styles */
.twitch-app-bg {
  font-family: 'Fredoka', 'Comic Sans MS', sans-serif !important;
}

/* Comic Top Bar */
.comic-app-bar {
  background-color: #ffde59 !important;
  border-bottom: 4px solid #000000 !important;
  box-shadow: 0px 4px 0px #000000 !important;
}

/* Nav Menu Button */
.comic-nav-btn {
  background-color: #ffffff !important;
  border: 3px solid #000000 !important;
  box-shadow: 3px 3px 0px #000000 !important;
  transition: transform 0.1s ease !important;
}
.comic-nav-btn:active {
  transform: translate(2px, 2px) !important;
  box-shadow: 1px 1px 0px #000000 !important;
}

/* Logo Badge */
.comic-badge {
  display: flex;
  align-items: center;
  background-color: #ff66c4;
  color: #ffffff;
  border: 3px solid #000000;
  border-radius: 12px;
  padding: 4px 14px;
  box-shadow: 3px 3px 0px #000000;
  transform: rotate(-1deg);
}

.comic-title {
  font-weight: 700;
  font-size: 1.1rem;
  letter-spacing: 0.5px;
  text-shadow: 1.5px 1.5px 0px #000000;
}

.comic-tag {
  background-color: #00f0ff;
  color: #000000;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 1px 6px;
  border: 2px solid #000000;
  border-radius: 6px;
  margin-left: 8px;
}

/* Side Drawer */
.comic-drawer {
  background-color: #fffbe6 !important;
  border-right: 4px solid #000000 !important;
}

.drawer-header {
  border-bottom: 3px dashed #000000;
}

.drawer-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #000000;
}

/* List Items */
.comic-list-item {
  background-color: #ffffff !important;
  border: 3px solid #000000 !important;
  border-radius: 12px !important;
  box-shadow: 4px 4px 0px #000000 !important;
  transition: all 0.15s ease !important;
}

.comic-list-item:hover {
  transform: translate(-2px, -2px) !important;
  box-shadow: 6px 6px 0px #000000 !important;
  background-color: #00f0ff !important;
}

.comic-item-active {
  background-color: #ff66c4 !important;
  color: #ffffff !important;
}

.comic-item-title {
  font-weight: 700 !important;
  font-size: 1.1rem !important;
  color: #000000 !important;
}

.icon-bubble {
  background-color: #ffde59;
  border: 2px solid #000000;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>