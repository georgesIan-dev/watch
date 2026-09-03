<template>
    <div>
        <!-- ERROR SNACKBAR -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
            {{ snackbar.message }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar.show = false">Close</v-btn>
            </template>
        </v-snackbar>

        <!-- MAIN CARD -->
        <v-card style="border-radius: 0px; min-height: 86vh; border: 1px solid black">
            <v-card-text>
                <!-- HEADER -->
                <div class="text-h6 font-weight-bold text-primary mb-3">WATCH PARTY</div>

                <!-- SEARCH -->
                <v-card
                    class="mb-4 pa-3"
                    style="border: 2px solid black; background-color: #fff9e6"
                >
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
                        <v-card style="border: 1px solid black">
                            <v-card-text class="pa-0">
                                <div v-if="currentVideoId" class="player-wrapper">
                                    <iframe
                                        :src="`https://www.youtube.com/embed/${currentVideoId}?autoplay=1&rel=0`"
                                        title="YouTube video player"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                        class="player-frame"
                                    ></iframe>
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
                        <v-card class="mt-4" style="border: 1px solid black">
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
                        <v-card style="border: 1px solid black; height: 100%">
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
    background-color: #f5f5f5;
}
</style>