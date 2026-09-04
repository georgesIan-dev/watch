<template>
    <div>
        <!-- ERROR SNACKBAR -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
            {{ snackbar.message }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar.show = false">Close</v-btn>
            </template>
        </v-snackbar>

        <!-- CHAT MODE OVERLAY (shown while the video is floating) -->
        <div v-if="chatMode" class="chat-overlay">
            <div class="chat-sidebar">
                <div class="chat-sidebar-brand">Claude</div>

                <div class="chat-sidebar-item chat-sidebar-item--active">
                    <v-icon size="18" class="mr-2">mdi-plus</v-icon> New
                </div>
                <div class="chat-sidebar-item"><v-icon size="18" class="mr-2">mdi-archive-outline</v-icon> Projects</div>
                <div class="chat-sidebar-item"><v-icon size="18" class="mr-2">mdi-shape-outline</v-icon> Artifacts</div>
                <div class="chat-sidebar-item"><v-icon size="18" class="mr-2">mdi-code-tags</v-icon> Code</div>
                <div class="chat-sidebar-item"><v-icon size="18" class="mr-2">mdi-tune</v-icon> Customize</div>

                <div class="chat-sidebar-label mt-4 mb-1">Chats and tasks</div>
                <div class="chat-sidebar-item chat-sidebar-item--muted" v-for="n in 6" :key="n">Untitled chat</div>

                <v-spacer />

                <div class="chat-sidebar-footer">
                    <v-avatar size="24" color="primary" class="mr-2">
                        <span class="text-caption">U</span>
                    </v-avatar>
                    User · Free
                </div>
            </div>

            <div class="chat-main">
                <div class="chat-greeting">Hey there,</div>

                <div class="chat-input-box">
                    <div class="chat-input-placeholder">How can I help you today?</div>
                    <div class="chat-input-toolbar">
                        <v-icon size="18">mdi-plus</v-icon>
                        <span class="chat-toolbar-chip">Chat</span>
                        <span class="chat-toolbar-chip chat-toolbar-chip--muted">Cowork</span>
                    </div>
                </div>

                <div class="chat-quick-actions">
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-pencil-outline</v-icon> Write</span>
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-school-outline</v-icon> Learn</span>
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-code-tags</v-icon> Code</span>
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-coffee-outline</v-icon> Life stuff</span>
                </div>
            </div>
        </div>

        <!-- FLOATING VIDEO WINDOW -->
        <div
            v-if="currentVideoId && isFloating"
            ref="floatWindow"
            class="float-window"
            :style="{ top: floatPos.y + 'px', left: floatPos.x + 'px', width: floatSize.w + 'px', height: floatSize.h + 'px' }"
        >
            <div class="float-header" @mousedown="startDrag">
                <span class="float-title">{{ currentVideoTitle || 'Now Playing' }}</span>
                <div class="float-actions">
                    <v-btn icon="mdi-close" size="x-small" variant="text" title="Close" @click="closeFloating" />
                </div>
            </div>
            <div class="float-body">
                <iframe
                    :src="`https://www.youtube-nocookie.com/embed/${currentVideoId}?autoplay=1&rel=0`"
                    title="Floating YouTube video player"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    class="float-frame"
                ></iframe>
            </div>
            <div class="float-resize-handle" @mousedown="startResize"></div>
        </div>

        <!-- MAIN CARD -->
        <v-card v-if="!chatMode" variant="flat" color="surface" class="border" min-height="86vh">
            <v-card-text>
                <!-- HEADER -->
                <div class="text-h6 font-weight-bold text-primary mb-3">Watch Party</div>

                <!-- SEARCH -->
                <v-card variant="tonal" color="surface-variant" class="mb-4 pa-3">
                    <div class="d-flex align-center ga-2">
                        <v-text-field
                            v-model="searchQuery"
                            variant="solo"
                            density="comfortable"
                            placeholder="Type something to watch... e.g. lofi music, cooking tutorial"
                            prepend-inner-icon="mdi-magnify"
                            hide-details
                            clearable
                            autofocus
                            @keyup.enter="searchVideos"
                        ></v-text-field>
                        <v-btn
                            color="primary"
                            size="large"
                            height="48"
                            @click="searchVideos"
                        >
                            <v-icon start>mdi-magnify</v-icon>
                            Search
                        </v-btn>
                    </div>
                </v-card>

                <v-row>
                    <!-- LEFT: PLAYER -->
                    <v-col cols="12" md="8">
                        <v-card variant="flat" color="surface-bright" class="border">
                            <v-card-text class="pa-0 position-relative">
                                <div v-if="currentVideoId" class="player-wrapper">
                                    <iframe
                                        :src="`https://www.youtube.com/embed/${currentVideoId}?autoplay=1&rel=0`"
                                        title="YouTube video player"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                        class="player-frame"
                                    ></iframe>

                                    <v-btn
                                        icon="mdi-picture-in-picture-top-right"
                                        size="small"
                                        variant="flat"
                                        color="surface"
                                        class="float-toggle-btn"
                                        title="Pop out to floating window"
                                        @click="openFloating"
                                    />
                                </div>

                                <div v-else class="player-placeholder d-flex align-center justify-center">
                                    <div class="text-center text-medium-emphasis">
                                        <v-icon size="64">mdi-youtube</v-icon>
                                        <div class="mt-2">Search for a video and select it to start watching</div>
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>

                        <div v-if="currentVideoTitle" class="text-subtitle-1 font-weight-bold mt-2">
                            {{ currentVideoTitle }}
                        </div>

                        <!-- QUEUE -->
                        <v-card variant="flat" color="surface-bright" class="mt-4 border">
                            <v-card-text>
                                <div class="text-subtitle-2 font-weight-bold mb-2">
                                    Queue ({{ queue.length }})
                                </div>
                                <div v-if="queue.length === 0" class="text-medium-emphasis">
                                    No videos queued yet. Add videos from the search results.
                                </div>
                                <v-list v-else density="compact">
                                    <v-list-item
                                        v-for="(item, index) in queue"
                                        :key="item.videoId + index"
                                    >
                                        <template v-slot:prepend>
                                            <v-avatar rounded size="48">
                                                <v-img :src="item.thumbnail" cover></v-img>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2">
                                            {{ item.title }}
                                        </v-list-item-title>
                                        <template v-slot:append>
                                            <v-btn icon variant="text" size="small" @click="playFromQueue(index)">
                                                <v-icon>mdi-play</v-icon>
                                            </v-btn>
                                            <v-btn icon variant="text" size="small" @click="removeFromQueue(index)">
                                                <v-icon>mdi-close</v-icon>
                                            </v-btn>
                                        </template>
                                    </v-list-item>
                                </v-list>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- RIGHT: SEARCH RESULTS -->
                    <v-col cols="12" md="4">
                        <v-card variant="flat" color="surface-bright" class="border" height="100%">
                            <v-card-text>
                                <div class="text-subtitle-2 font-weight-bold mb-2">
                                    Search Results
                                </div>

                                <div v-if="loading" class="d-flex justify-center pa-4">
                                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                                </div>

                                <div v-else-if="results.length === 0" class="text-medium-emphasis">
                                    No results yet. Try searching for something above.
                                </div>

                                <v-list v-else lines="two" density="compact">
                                    <v-list-item
                                        v-for="video in results"
                                        :key="video.videoId"
                                        @click="playVideo(video)"
                                    >
                                        <template v-slot:prepend>
                                            <v-avatar rounded size="64">
                                                <v-img :src="video.thumbnail" cover></v-img>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">
                                            {{ video.title }}
                                        </v-list-item-title>
                                        <v-list-item-subtitle>
                                            {{ video.channelTitle }}
                                        </v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click.stop="addToQueue(video)"
                                            >
                                                <v-icon>mdi-plus</v-icon>
                                            </v-btn>
                                        </template>
                                    </v-list-item>
                                </v-list>

                                <div v-if="nextPageToken" class="d-flex justify-center mt-2">
                                    <v-btn variant="text" size="small" @click="searchVideos(true)">
                                        Load more
                                    </v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>
    </div>
</template>

<script>
import axios from "axios";

// YouTube Data API v3 key, exposed to the client build via Vite.
// Set VITE_YOUTUBE_API_KEY in your .env file (must be prefixed with VITE_
// for Vite to expose it to the frontend).
const YOUTUBE_API_KEY = import.meta.env.VITE_YOUTUBE_API_KEY;
const YOUTUBE_SEARCH_URL = "https://www.googleapis.com/youtube/v3/search";

export default {
    name: "WatchParty",
    data() {
        return {
            searchQuery: "",
            results: [],
            queue: [],
            currentVideoId: "",
            currentVideoTitle: "",
            loading: false,
            nextPageToken: "",
            snackbar: {
                show: false,
                message: "",
                color: "error",
            },

            // Floating window state
            isFloating: false,
            chatMode: false,
            floatPos: { x: 0, y: 0 },
            floatSize: { w: 360, h: 240 },
            drag: null,
            resize: null,
        };
    },
    watch: {
        searchQuery(newVal) {
            if (!newVal || !newVal.trim()) {
                this.results = [];
                this.nextPageToken = "";
            }
        },
    },
    methods: {
        showError(message) {
            this.snackbar.message = message;
            this.snackbar.color = "error";
            this.snackbar.show = true;
        },

        async searchVideos(loadMore = false) {
            const query = (this.searchQuery || "").trim();
            if (!query) {
                this.showError("Enter something to search for");
                return;
            }
            if (!YOUTUBE_API_KEY) {
                this.showError(
                    "Missing YouTube API key. Set VITE_YOUTUBE_API_KEY in your .env file."
                );
                return;
            }

            if (!loadMore) {
                this.results = [];
                this.nextPageToken = "";
            }

            this.loading = true;
            try {
                const response = await axios.get(YOUTUBE_SEARCH_URL, {
                    params: {
                        key: YOUTUBE_API_KEY,
                        q: query,
                        part: "snippet",
                        type: "video",
                        maxResults: 10,
                        pageToken: loadMore ? this.nextPageToken : undefined,
                    },
                });

                const items = response.data.items || [];
                const mapped = items.map((item) => ({
                    videoId: item.id.videoId,
                    title: item.snippet.title,
                    channelTitle: item.snippet.channelTitle,
                    thumbnail:
                        item.snippet.thumbnails?.medium?.url ||
                        item.snippet.thumbnails?.default?.url,
                }));

                this.results = loadMore ? [...this.results, ...mapped] : mapped;
                this.nextPageToken = response.data.nextPageToken || "";
            } catch (error) {
                console.error("YouTube search error:", error);
                const message =
                    error.response?.data?.error?.message ||
                    "Failed to fetch videos from YouTube";
                this.showError(message);
            } finally {
                this.loading = false;
            }
        },

        playVideo(video) {
            this.currentVideoId = video.videoId;
            this.currentVideoTitle = video.title;
        },

        addToQueue(video) {
            this.queue.push(video);
            this.results = this.results.filter((r) => r.videoId !== video.videoId);
        },

        removeFromQueue(index) {
            this.queue.splice(index, 1);
        },

        playFromQueue(index) {
            const video = this.queue[index];
            if (video) {
                this.playVideo(video);
            }
        },

        openFloating() {
            // Default to the bottom-right corner of the viewport
            this.floatPos = {
                x: Math.max(16, window.innerWidth - this.floatSize.w - 24),
                y: Math.max(16, window.innerHeight - this.floatSize.h - 24),
            };
            this.isFloating = true;
            this.chatMode = true;
        },

        closeFloating() {
            this.isFloating = false;
            this.chatMode = false;
            this.currentVideoId = "";
            this.currentVideoTitle = "";
        },

        startDrag(event) {
            this.drag = {
                startX: event.clientX,
                startY: event.clientY,
                origX: this.floatPos.x,
                origY: this.floatPos.y,
            };
            window.addEventListener("mousemove", this.onDrag);
            window.addEventListener("mouseup", this.stopDrag);
        },

        onDrag(event) {
            if (!this.drag) return;
            const dx = event.clientX - this.drag.startX;
            const dy = event.clientY - this.drag.startY;

            const maxX = window.innerWidth - this.floatSize.w;
            const maxY = window.innerHeight - this.floatSize.h;

            this.floatPos = {
                x: Math.min(Math.max(0, this.drag.origX + dx), Math.max(0, maxX)),
                y: Math.min(Math.max(0, this.drag.origY + dy), Math.max(0, maxY)),
            };
        },

        stopDrag() {
            this.drag = null;
            window.removeEventListener("mousemove", this.onDrag);
            window.removeEventListener("mouseup", this.stopDrag);
        },

        startResize(event) {
            event.stopPropagation();
            this.resize = {
                startX: event.clientX,
                startY: event.clientY,
                origW: this.floatSize.w,
                origH: this.floatSize.h,
            };
            window.addEventListener("mousemove", this.onResize);
            window.addEventListener("mouseup", this.stopResize);
        },

        onResize(event) {
            if (!this.resize) return;
            const dw = event.clientX - this.resize.startX;
            const dh = event.clientY - this.resize.startY;

            this.floatSize = {
                w: Math.min(Math.max(240, this.resize.origW + dw), window.innerWidth - this.floatPos.x - 8),
                h: Math.min(Math.max(160, this.resize.origH + dh), window.innerHeight - this.floatPos.y - 8),
            };
        },

        stopResize() {
            this.resize = null;
            window.removeEventListener("mousemove", this.onResize);
            window.removeEventListener("mouseup", this.stopResize);
        },
    },

    beforeUnmount() {
        window.removeEventListener("mousemove", this.onDrag);
        window.removeEventListener("mouseup", this.stopDrag);
        window.removeEventListener("mousemove", this.onResize);
        window.removeEventListener("mouseup", this.stopResize);
    },
};
</script>

<style scoped>
.player-wrapper {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 aspect ratio */
}
.player-frame {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
.player-placeholder {
    width: 100%;
    height: 400px;
    background-color: rgb(var(--v-theme-surface-variant));
}

.float-toggle-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    opacity: 0.85;
}

.float-window {
    position: fixed;
    z-index: 2400;
    display: flex;
    flex-direction: column;
    background-color: rgb(var(--v-theme-surface-bright));
    border: 1px solid rgb(var(--v-theme-surface-variant));
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
    overflow: hidden;
    min-width: 240px;
    min-height: 160px;
}

.float-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 4px 4px 10px;
    background-color: rgb(var(--v-theme-surface-variant));
    cursor: move;
    user-select: none;
    flex: 0 0 auto;
}

.float-title {
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 75%;
}

.float-actions {
    display: flex;
    align-items: center;
}

.float-body {
    position: relative;
    flex: 1 1 auto;
    background-color: black;
}

.float-frame {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

.float-resize-handle {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 16px;
    height: 16px;
    cursor: nwse-resize;
}

.float-resize-handle::after {
    content: '';
    position: absolute;
    right: 3px;
    bottom: 3px;
    width: 8px;
    height: 8px;
    border-right: 2px solid rgb(var(--v-theme-on-surface-variant));
    border-bottom: 2px solid rgb(var(--v-theme-on-surface-variant));
    opacity: 0.6;
}

/* ===== CHAT MODE OVERLAY ===== */
.chat-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    background-color: #000000;
    color: #e5e5e5;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.chat-sidebar {
    width: 260px;
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    padding: 20px 14px;
    border-right: 1px solid #2a2a2a;
}

.chat-sidebar-brand {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 18px;
    color: #ffffff;
}

.chat-sidebar-item {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #d4d4d4;
    cursor: pointer;
}

.chat-sidebar-item--active {
    background-color: #262626;
    color: #ffffff;
}

.chat-sidebar-item--muted {
    color: #8a8a8a;
    padding-left: 10px;
}

.chat-sidebar-item:hover {
    background-color: #1c1c1c;
}

.chat-sidebar-label {
    font-size: 0.72rem;
    color: #7a7a7a;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0 10px;
}

.chat-sidebar-footer {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
    color: #b5b5b5;
    padding: 10px;
    border-top: 1px solid #2a2a2a;
}

.chat-main {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 24px;
}

.chat-greeting {
    font-size: 2.4rem;
    font-family: 'Georgia', serif;
    background-color: #1c1c1c;
    padding: 12px 24px;
    border-radius: 6px;
    color: #f2f2f2;
}

.chat-input-box {
    width: min(100%, 640px);
    background-color: #171717;
    border: 1px solid #2a2a2a;
    border-radius: 16px;
    padding: 18px 20px;
}

.chat-input-placeholder {
    color: #8a8a8a;
    font-size: 0.95rem;
    margin-bottom: 18px;
}

.chat-input-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #b5b5b5;
}

.chat-toolbar-chip {
    background-color: #2a2a2a;
    border-radius: 8px;
    padding: 4px 12px;
    font-size: 0.8rem;
}

.chat-toolbar-chip--muted {
    background-color: transparent;
    color: #8a8a8a;
}

.chat-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.chat-quick-chip {
    display: flex;
    align-items: center;
    background-color: #1c1c1c;
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 0.85rem;
    color: #d4d4d4;
    cursor: default;
}
</style>