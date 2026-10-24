<template>
    <div class="watch-page">
        <!-- ERROR SNACKBAR -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
            {{ snackbar.message }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar.show = false">Close</v-btn>
            </template>
        </v-snackbar>

        <!-- CHAT MODE OVERLAY (shown while the video is floating) -->
        <div v-if="chatMode" class="chat-overlay" :class="{ 'chat-overlay--menu-open': chatMenuOpen }">
            <aside class="chat-sidebar">
                <div class="chat-brand">
                    <span class="brand-mark" aria-hidden="true"></span>
                    <span class="brand-name">Diwa</span>
                    <button class="chat-icon-btn chat-sidebar-close" aria-label="Close menu" @click="chatMenuOpen = false">
                        <v-icon size="20">mdi-close</v-icon>
                    </button>
                </div>

                <button class="chat-new-btn" @click="startNewChat">
                    <v-icon size="18">mdi-square-edit-outline</v-icon>
                    New chat
                </button>

                <nav class="chat-nav">
                    <div class="chat-nav-item"><v-icon size="18">mdi-magnify</v-icon> Search chats</div>
                    <div class="chat-nav-item"><v-icon size="18">mdi-bookmark-outline</v-icon> Saved prompts</div>
                    <div class="chat-nav-item"><v-icon size="18">mdi-folder-outline</v-icon> Files</div>
                </nav>

                <div class="chat-recent-label">Recent</div>
                <div class="chat-recent">
                    <div v-for="title in recentChats" :key="title" class="chat-recent-item">{{ title }}</div>
                </div>

                <div class="chat-account">
                    <span class="chat-account-avatar">U</span>
                    <div>
                        <div>User</div>
                        <div class="chat-account-plan">Free plan</div>
                    </div>
                </div>
            </aside>

            <div class="chat-scrim" @click="chatMenuOpen = false"></div>

            <main class="chat-main">
                <header class="chat-topbar">
                    <button class="chat-icon-btn chat-menu-btn" aria-label="Open menu" @click="chatMenuOpen = true">
                        <v-icon size="22">mdi-menu</v-icon>
                    </button>
                    <div class="chat-model">
                        Diwa 2
                        <v-icon size="18">mdi-chevron-down</v-icon>
                    </div>
                    <div class="chat-topbar-spacer"></div>
                    <button class="chat-icon-btn" title="Share" aria-label="Share">
                        <v-icon size="20">mdi-export-variant</v-icon>
                    </button>
                </header>

                <div class="chat-body" ref="chatThread">
                    <div v-if="chatMessages.length === 0" class="chat-welcome">
                        <span class="brand-mark brand-mark--lg" aria-hidden="true"></span>
                        <h2 class="chat-greeting">What are we working on today?</h2>
                        <div class="chat-suggestions">
                            <button
                                v-for="suggestion in chatSuggestions"
                                :key="suggestion.label"
                                class="chat-suggestion"
                                @click="sendChatMessage(suggestion.prompt)"
                            >
                                <v-icon size="18">{{ suggestion.icon }}</v-icon>
                                {{ suggestion.label }}
                            </button>
                        </div>
                    </div>

                    <div v-else class="chat-thread">
                        <template v-for="(msg, index) in chatMessages" :key="index">
                            <div v-if="msg.role === 'user'" class="chat-msg--user">{{ msg.text }}</div>

                            <div v-else class="chat-msg--ai">
                                <span class="brand-mark brand-mark--sm" aria-hidden="true"></span>
                                <div class="chat-ai-content">
                                    <p class="chat-ai-intro">{{ msg.intro }}</p>
                                    <div class="chat-codeblock">
                                        <div class="chat-codeblock-head">
                                            <span>{{ msg.lang }}</span>
                                            <button class="chat-copy-btn" @click="copyCode(index)">
                                                <v-icon size="14">{{ copiedIndex === index ? 'mdi-check' : 'mdi-content-copy' }}</v-icon>
                                                {{ copiedIndex === index ? 'Copied' : 'Copy' }}
                                            </button>
                                        </div>
                                        <pre class="chat-code">{{ msg.text }}</pre>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-if="aiThinking" class="chat-msg--ai">
                            <span class="brand-mark brand-mark--sm brand-mark--thinking" aria-hidden="true"></span>
                            <span class="chat-thinking">Thinking…</span>
                        </div>
                    </div>
                </div>

                <div class="chat-composer-wrap">
                    <div class="chat-composer">
                        <textarea
                            ref="composer"
                            v-model="chatInput"
                            class="chat-input-field"
                            placeholder="Ask Diwa anything"
                            rows="1"
                            @input="autoGrowComposer"
                            @keydown.enter.exact.prevent="sendChatMessage()"
                        ></textarea>
                        <div class="chat-composer-bar">
                            <button class="chat-icon-btn" title="Attach file" aria-label="Attach file">
                                <v-icon size="20">mdi-paperclip</v-icon>
                            </button>
                            <div class="chat-topbar-spacer"></div>
                            <button
                                class="chat-send-btn"
                                aria-label="Send message"
                                :disabled="!chatInput.trim() || aiTyping"
                                @click="sendChatMessage()"
                            >
                                <v-icon size="18">mdi-arrow-up</v-icon>
                            </button>
                        </div>
                    </div>
                    <div class="chat-disclaimer">Diwa can make mistakes. Check important info.</div>
                </div>
            </main>
        </div>

        <!-- SEARCH -->
        <div class="search-strip">
            <div class="search-bar">
                <div class="search-box">
                    <v-icon size="20" class="search-box-icon">mdi-magnify</v-icon>
                    <input
                        v-model="searchQuery"
                        class="search-input"
                        type="text"
                        placeholder="Search"
                        aria-label="Search videos"
                        autofocus
                        @keyup.enter="searchVideos()"
                    />
                    <button
                        v-if="searchQuery"
                        class="icon-btn"
                        title="Clear"
                        aria-label="Clear search"
                        @click="searchQuery = ''"
                    >
                        <v-icon size="20">mdi-close</v-icon>
                    </button>
                </div>
                <button class="search-submit" title="Search" aria-label="Search" @click="searchVideos()">
                    <v-icon size="22">mdi-magnify</v-icon>
                </button>
            </div>

            <div class="chip-row">
                <button
                    v-for="option in orderOptions"
                    :key="option.value"
                    class="yt-chip"
                    :class="{ 'yt-chip--active': searchOrder === option.value }"
                    @click="searchOrder = option.value"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <!-- WATCH LAYOUT (a video is playing) -->
        <div v-if="currentVideoId" class="watch-grid" :class="{ 'watch-grid--theater': theaterMode }">
            <!-- PLAYER — one persistent iframe; CSS moves it between default, theater and floating -->
            <div
                class="player-host"
                :class="{
                    'player-host--floating': isFloating,
                    'player-host--theater': theaterMode && !isFloating,
                    'player-host--dragging': drag || resize,
                }"
                :style="isFloating ? floatStyle : {}"
            >
                <div v-if="isFloating" class="float-header" @pointerdown="startDrag">
                    <span class="float-title">{{ displayTitle || 'Now Playing' }}</span>
                    <div class="float-actions">
                        <v-btn icon="mdi-close" size="x-small" variant="text" title="Back to normal view" @click="closeFloating" />
                    </div>
                </div>

                <div class="player-frame-wrap">
                    <!-- Fallback: plain embed when www.youtube.com (the IFrame API) is blocked on this network -->
                    <iframe
                        v-if="playerFallback"
                        :src="`https://www.youtube-nocookie.com/embed/${currentVideoId}?autoplay=1&rel=0`"
                        title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        class="player-frame"
                    ></iframe>
                    <!-- The YouTube IFrame API puts its iframe in here (see createPlayer) -->
                    <div v-else ref="playerSlot" class="player-frame"></div>

                    <!-- Our own message instead of YouTube's "Video unavailable" screen -->
                    <div v-if="playerError" class="player-error">
                        <v-icon size="40" class="mb-3">mdi-cancel</v-icon>
                        <div class="player-error-title">Can't play this video here</div>
                        <div class="player-error-text">{{ playerError.message }}</div>
                        <a
                            class="yt-pill player-error-link"
                            :href="`https://www.youtube.com/watch?v=${playerError.videoId}`"
                            target="_blank"
                            rel="noopener"
                        >
                            <v-icon size="18">mdi-open-in-new</v-icon>
                            Watch on YouTube
                        </a>
                    </div>

                    <!-- Sits below YouTube's own top-right icons so it doesn't cover them -->
                    <button
                        v-if="!isFloating"
                        class="player-popout-btn"
                        title="Pop out to floating window"
                        aria-label="Pop out to floating window"
                        @click="openFloating"
                    >
                        <v-icon size="20">mdi-picture-in-picture-bottom-right</v-icon>
                    </button>
                </div>

                <div v-if="isFloating" class="float-resize-handle" @pointerdown="startResize"></div>
            </div>

            <!-- VIDEO INFO -->
            <section class="watch-info">
                <h1 class="watch-title">{{ displayTitle }}</h1>

                <div class="watch-meta-row">
                    <div class="channel">
                        <div class="channel-avatar">{{ channelInitial(displayChannel) }}</div>
                        <span class="channel-name">{{ displayChannel }}</span>
                    </div>

                    <div class="watch-actions">
                        <span v-if="videoDetails" class="yt-pill yt-pill--static" title="Likes">
                            <v-icon size="18">mdi-thumb-up-outline</v-icon>
                            {{ formatCount(videoDetails.likeCount) }}
                        </span>
                        <button
                            class="yt-pill theater-toggle"
                            :title="theaterMode ? 'Default view (t)' : 'Theater mode (t)'"
                            @click="toggleTheater"
                        >
                            <v-icon size="18">{{ theaterMode ? 'mdi-crop-square' : 'mdi-rectangle-outline' }}</v-icon>
                            {{ theaterMode ? 'Default view' : 'Theater' }}
                        </button>
                        <button class="yt-pill" title="Pop out to floating window" @click="openFloating">
                            <v-icon size="18">mdi-picture-in-picture-bottom-right</v-icon>
                            Pop out
                        </button>
                    </div>
                </div>

                <div
                    v-if="loadingDetails || videoDetails"
                    class="description-box"
                    :class="{ 'description-box--open': showDescription }"
                    @click="expandDescription"
                >
                    <div v-if="loadingDetails" class="text-medium-emphasis">Loading details...</div>

                    <template v-else>
                        <div class="description-stats">
                            <span>{{ formatCount(videoDetails.viewCount) }} views</span>
                            <span>{{ timeAgo(videoDetails.publishedAt) }}</span>
                            <span>{{ formatDate(videoDetails.publishedAt) }}</span>
                        </div>

                        <div class="description-text" :class="{ 'description-clamped': !showDescription }">{{ videoDetails.description }}</div>

                        <button
                            v-if="videoDetails.description && videoDetails.description.length > 150"
                            class="description-toggle"
                            @click.stop="showDescription = !showDescription"
                        >
                            {{ showDescription ? 'Show less' : '...more' }}
                        </button>
                    </template>
                </div>
            </section>

            <!-- SIDE: QUEUE + SEARCH RESULTS -->
            <aside class="watch-side">
                <div v-if="queue.length" class="queue-panel">
                    <div class="queue-head">
                        Queue <span class="queue-count">{{ queue.length }}</span>
                    </div>
                    <div class="queue-list">
                        <div
                            v-for="(item, index) in queue"
                            :key="item.videoId + index"
                            class="compact-row"
                            role="button"
                            tabindex="0"
                            @click="playFromQueue(index)"
                            @keydown.enter="playFromQueue(index)"
                        >
                            <div class="thumb thumb--compact thumb--queue">
                                <v-img :src="item.thumbnail" aspect-ratio="1.7778" cover>
                                    <span v-if="item.duration" class="duration-badge">{{ item.duration }}</span>
                                </v-img>
                            </div>
                            <div class="compact-text">
                                <div class="video-title video-title--compact">{{ item.title }}</div>
                                <div class="video-sub">{{ item.channelTitle }}</div>
                            </div>
                            <button
                                class="icon-btn row-action"
                                title="Remove from queue"
                                aria-label="Remove from queue"
                                @click.stop="removeFromQueue(index)"
                            >
                                <v-icon size="18">mdi-close</v-icon>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Related (based on what's playing) vs. the user's own search -->
                    <div class="chip-row side-tabs">
                        <button
                            v-for="tab in sideTabs"
                            :key="tab.value"
                            class="yt-chip"
                            :class="{ 'yt-chip--active': sideTab === tab.value }"
                            @click="sideTab = tab.value"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <div v-if="sideLoading && sideList.length === 0" class="d-flex justify-center pa-6">
                        <v-progress-circular indeterminate color="primary"></v-progress-circular>
                    </div>

                    <div v-else-if="sideList.length === 0" class="empty-text">
                        {{ sideEmptyText }}
                    </div>

                    <div v-else class="compact-list" :class="{ 'is-loading': sideTab === 'search' && loading }">
                        <div
                            v-for="video in sideList"
                            :key="video.videoId"
                            class="compact-row"
                            :class="{
                                'compact-row--active': video.videoId === currentVideoId,
                                'is-blocked': video.embeddable === false,
                            }"
                            role="button"
                            :tabindex="video.embeddable === false ? -1 : 0"
                            :aria-disabled="video.embeddable === false"
                            :title="video.embeddable === false ? blockedTooltip : undefined"
                            @click="playVideo(video)"
                            @keydown.enter="playVideo(video)"
                        >
                            <div class="thumb thumb--compact">
                                <v-img :src="video.thumbnail" aspect-ratio="1.7778" cover>
                                    <span v-if="video.duration" class="duration-badge">{{ video.duration }}</span>
                                </v-img>
                            </div>
                            <div class="compact-text">
                                <div class="video-title video-title--compact">{{ video.title }}</div>
                                <div class="video-sub">{{ video.channelTitle }}</div>
                                <div class="video-sub" :title="formatDate(video.publishedAt)">{{ uploadedLabel(video.publishedAt) }}</div>
                                <div v-if="video.embeddable === false" class="blocked-note">
                                    <v-icon size="14">mdi-cancel</v-icon> Can't play here
                                </div>
                            </div>
                            <button
                                v-if="video.embeddable !== false"
                                class="icon-btn row-action"
                                title="Add to queue"
                                aria-label="Add to queue"
                                @click.stop="addToQueue(video)"
                            >
                                <v-icon size="20">mdi-playlist-plus</v-icon>
                            </button>
                        </div>
                    </div>

                    <!-- Infinite scroll: loads the next page when this row nears the viewport -->
                    <div
                        v-if="sideTab === 'search' && nextPageToken && results.length"
                        v-intersect="{ handler: onResultsEndVisible, options: { rootMargin: '0px 0px 600px 0px' } }"
                        class="load-more-row mt-4"
                    >
                        <v-progress-circular v-if="loadingMore" indeterminate color="primary" size="28" />
                        <button v-else-if="autoLoadPaused" class="yt-pill" @click="retryLoadMore">Load more</button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- BROWSE LAYOUT (nothing playing yet) -->
        <div v-else class="browse">
            <section v-if="queue.length" class="browse-section">
                <div class="browse-heading">Queue <span class="queue-count">{{ queue.length }}</span></div>
                <div class="browse-grid">
                    <div
                        v-for="(item, index) in queue"
                        :key="item.videoId + index"
                        class="video-card"
                        role="button"
                        tabindex="0"
                        @click="playFromQueue(index)"
                        @keydown.enter="playFromQueue(index)"
                    >
                        <div class="thumb">
                            <v-img :src="item.thumbnail" aspect-ratio="1.7778" cover>
                                <span v-if="item.duration" class="duration-badge">{{ item.duration }}</span>
                                <button
                                    class="icon-btn thumb-action"
                                    title="Remove from queue"
                                    aria-label="Remove from queue"
                                    @click.stop="removeFromQueue(index)"
                                >
                                    <v-icon size="18">mdi-close</v-icon>
                                </button>
                            </v-img>
                        </div>
                        <div class="card-body">
                            <div class="channel-avatar channel-avatar--sm">{{ channelInitial(item.channelTitle) }}</div>
                            <div class="card-text">
                                <div class="video-title">{{ item.title }}</div>
                                <div class="video-sub video-sub--lg">{{ item.channelTitle }}</div>
                                <div class="video-sub video-sub--lg" :title="formatDate(item.publishedAt)">{{ uploadedLabel(item.publishedAt) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- LIVE NOW -->
            <!-- <v-card variant="flat" color="surface-bright" class="mt-4 border">
                <v-card-text>
                    <div class="d-flex align-center mb-2">
                        <v-icon color="error" size="14" class="mr-1">mdi-circle</v-icon>
                        <span class="text-subtitle-2 font-weight-bold">Live Now</span>
                        <v-spacer />
                        <v-btn icon="mdi-refresh" size="small" variant="text" @click="fetchLiveVideos" />
                    </div>

                    <div v-if="loadingLive" class="d-flex justify-center pa-4">
                        <v-progress-circular indeterminate color="primary"></v-progress-circular>
                    </div>

                    <div v-else-if="liveResults.length === 0" class="text-medium-emphasis">
                        No live videos found right now.
                    </div>

                    <v-row v-else>
                        <v-col
                            v-for="video in liveResults"
                            :key="video.videoId"
                            cols="6"
                            sm="4"
                            md="3"
                            lg="2"
                        >
                            <v-card variant="tonal" color="surface-variant" class="grid-card" @click="playVideo(video)">
                                <v-img :src="video.thumbnail" aspect-ratio="1.77" cover class="grid-thumb">
                                    <v-chip size="x-small" color="error" class="live-badge">LIVE</v-chip>
                                    <v-btn
                                        icon="mdi-plus"
                                        size="x-small"
                                        variant="flat"
                                        color="surface"
                                        class="grid-remove-btn"
                                        title="Add to queue"
                                        @click.stop="addToQueue(video)"
                                    />
                                </v-img>
                                <div class="pa-2">
                                    <div class="text-caption font-weight-medium grid-title">{{ video.title }}</div>
                                    <div class="text-caption grid-channel">{{ video.channelTitle }}</div>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card> -->

            <section class="browse-section">
                <div v-if="queue.length" class="browse-heading">Search results</div>

                <div v-if="loading && results.length === 0" class="d-flex justify-center pa-8">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                </div>

                <div v-else-if="results.length === 0" class="empty-text">
                    No results yet. Try searching for something above.
                </div>

                <div v-else class="browse-grid" :class="{ 'is-loading': loading }">
                    <div
                        v-for="video in results"
                        :key="video.videoId"
                        class="video-card"
                        :class="{ 'is-blocked': video.embeddable === false }"
                        role="button"
                        :tabindex="video.embeddable === false ? -1 : 0"
                        :aria-disabled="video.embeddable === false"
                        :title="video.embeddable === false ? blockedTooltip : undefined"
                        @click="playVideo(video)"
                        @keydown.enter="playVideo(video)"
                    >
                        <div class="thumb">
                            <v-img :src="video.thumbnail" aspect-ratio="1.7778" cover>
                                <span v-if="video.duration" class="duration-badge">{{ video.duration }}</span>
                                <button
                                    v-if="video.embeddable !== false"
                                    class="icon-btn thumb-action"
                                    title="Add to queue"
                                    aria-label="Add to queue"
                                    @click.stop="addToQueue(video)"
                                >
                                    <v-icon size="20">mdi-playlist-plus</v-icon>
                                </button>
                            </v-img>
                        </div>
                        <div class="card-body">
                            <div class="channel-avatar channel-avatar--sm">{{ channelInitial(video.channelTitle) }}</div>
                            <div class="card-text">
                                <div class="video-title">{{ video.title }}</div>
                                <div class="video-sub video-sub--lg">{{ video.channelTitle }}</div>
                                <div class="video-sub video-sub--lg" :title="formatDate(video.publishedAt)">{{ uploadedLabel(video.publishedAt) }}</div>
                                <div v-if="video.embeddable === false" class="blocked-note">
                                    <v-icon size="14">mdi-cancel</v-icon> Can't play here
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Infinite scroll: loads the next page when this row nears the viewport -->
                <div
                    v-if="nextPageToken && results.length"
                    v-intersect="{ handler: onResultsEndVisible, options: { rootMargin: '0px 0px 600px 0px' } }"
                    class="load-more-row mt-8"
                >
                    <v-progress-circular v-if="loadingMore" indeterminate color="primary" size="28" />
                    <button v-else-if="autoLoadPaused" class="yt-pill" @click="retryLoadMore">Load more</button>
                </div>
            </section>
        </div>
    </div>
</template>


<script>
import axios from "axios";
const YOUTUBE_SEARCH_ENDPOINT = "/api/youtube/search";
const YT_IFRAME_API_SRC = "https://www.youtube.com/iframe_api";
// A proxy/firewall can make the API hang instead of failing, so give up after this long
const YT_API_TIMEOUT_MS = 8000;

// IFrame Player API error codes (https://developers.google.com/youtube/iframe_api_reference#onError)
const YT_ERROR_NOT_FOUND = 100; // removed or private
const YT_ERRORS_EMBED_BLOCKED = [101, 150]; // owner turned off playback on other websites

// Load YouTube's IFrame API script once and share it between page visits.
let ytApiPromise = null;
function loadYouTubeIframeApi() {
    if (window.YT && window.YT.Player) return Promise.resolve(window.YT);
    if (!ytApiPromise) {
        ytApiPromise = new Promise((resolve, reject) => {
            const fail = (message) => {
                clearTimeout(timer);
                ytApiPromise = null; // allow a retry on the next page visit
                reject(new Error(message));
            };
            const timer = setTimeout(() => fail("Timed out loading the YouTube IFrame API"), YT_API_TIMEOUT_MS);

            const previous = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {
                clearTimeout(timer);
                if (typeof previous === "function") previous();
                resolve(window.YT);
            };
            const script = document.createElement("script");
            script.src = YT_IFRAME_API_SRC;
            script.async = true;
            script.onerror = () => fail("Failed to load the YouTube IFrame API");
            document.head.appendChild(script);
        });
    }
    return ytApiPromise;
}
const YOUTUBE_LIVE_ENDPOINT = "/api/youtube/live";
const YOUTUBE_RELATED_ENDPOINT = "/api/youtube/related";
const THEATER_STORAGE_KEY = "watchParty.theaterMode";

// Remember theater mode between visits, like YouTube does.
function readTheaterPreference() {
    try {
        return localStorage.getItem(THEATER_STORAGE_KEY) === "1";
    } catch (e) {
        return false;
    }
}

export default {
    name: "WatchParty",
    data() {
        return {
            searchQuery: "Trending PH",
            results: [],
            queue: [],
            currentVideoId: "",
            currentVideoTitle: "",
            currentChannelTitle: "",
            loading: false,
            nextPageToken: "",
            // infinite scroll
            loadingMore: false,
            resultsEndVisible: false,
            autoLoadPaused: false, // set after a failed page load so we don't retry in a loop
            searchGeneration: 0, // bumps on every new search so a late "load more" can't mix into new results
            blockedTooltip: "The owner turned off playback on other websites",
            // related videos for whatever is playing (side column)
            relatedResults: [],
            loadingRelated: false,
            sideTab: "related",
            sideTabs: [
                { value: "related", label: "Related" },
                { value: "search", label: "Search results" },
            ],
            // set when the player reports an error; shows our own overlay
            playerError: null,
            // true when the IFrame API can't load (e.g. www.youtube.com blocked by a proxy);
            // we then use the plain embed iframe without error handling
            playerFallback: false,
            snackbar: {
                show: false,
                message: "",
                color: "error",
            },

            // for video details
            videoDetails: null,
            loadingDetails: false,
            showDescription: false,

            liveResults: [],
            loadingLive: false,
            // search ORDER BY DATE
            searchOrder: "relevance",
            orderOptions: [
                { value: "relevance", label: "Relevance" },
                { value: "date", label: "Latest" },
            ],

            // Theater mode (wide player)
            theaterMode: readTheaterPreference(),

            // Floating window state
            isFloating: false,
            chatMode: false,
            chatInput: "",
            chatMessages: [],
            aiTyping: false, // true from send until the reply finishes streaming
            aiThinking: false, // true only while the "Thinking…" indicator shows
            chatMenuOpen: false,
            copiedIndex: null,
            recentChats: [
                "Attendance CSV export bug",
                "Monthly report SQL join",
                "Laravel form request rules",
                "Vue table pagination",
                "Refactor API controller",
                "Debounce search input",
                "Docker build for Laravel",
                "Fix N+1 in employee list",
            ],
            chatSuggestions: [
                { icon: "mdi-code-tags", label: "Write a function", prompt: "Write a function that formats a date like Oct 19, 2026" },
                { icon: "mdi-bug-outline", label: "Debug my code", prompt: "Why doesn't my Vue list update after I change the array?" },
                { icon: "mdi-database-outline", label: "Write a SQL query", prompt: "Write a SQL query that counts orders per user" },
                { icon: "mdi-lightbulb-outline", label: "Explain a concept", prompt: "Explain debounce vs throttle with an example" },
            ],
            replyIntros: [
                "Here's one way to do it:",
                "Sure. Here's an example you can adapt:",
                "This should work for your case:",
                "Try this approach:",
                "Here's a cleaner version:",
            ],
            codeSnippets: [
                `import axios from "axios";
export default {
    name: "EmployeeMonitor",
    data() {
        return {
            employees: [],
            attendanceLogs: [],
            loading: false,
            searchQuery: "",
            filters: {
                department: null,
                status: null,
                dateRange: [],
            },
        };
    },
    computed: {
        filteredEmployees() {
            return this.employees.filter((emp) => {
                const matchesSearch = emp.name
                    .toLowerCase()
                    .includes(this.searchQuery.toLowerCase());
                const matchesDept = !this.filters.department || emp.department === this.filters.department;
                return matchesSearch && matchesDept;
            });
        },
        totalPresent() {
            return this.attendanceLogs.filter((log) => log.status === "Present").length;
        },
    },
    methods: {
        async fetchEmployees() {
            this.loading = true;
            try {
                const res = await axios.get("/api/employees");
                this.employees = res.data;
            } catch (err) {
                console.error("Failed to fetch employees:", err);
            } finally {
                this.loading = false;
            }
        },
        async checkIn(employeeId) {
            const timestamp = new Date().toISOString();
            await axios.post("/api/attendance/check-in", {
                employee_id: employeeId,
                time_in: timestamp,
            });
            this.fetchAttendanceLogs();
        },
        async checkOut(employeeId) {
            const timestamp = new Date().toISOString();
            await axios.post("/api/attendance/check-out", {
                employee_id: employeeId,
                time_out: timestamp,
            });
            this.fetchAttendanceLogs();
        },
        async fetchAttendanceLogs() {
            const res = await axios.get("/api/attendance/logs", {
                params: this.filters,
            });
            this.attendanceLogs = res.data;
        },
        exportToCsv() {
            const rows = this.filteredEmployees.map((e) => [e.id, e.name, e.department]);
            const csv = rows.map((r) => r.join(",")).join("\n");
            const blob = new Blob([csv], { type: "text/csv" });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = "employees.csv";
            link.click();
        },
    },
    mounted() {
        this.fetchEmployees();
        this.fetchAttendanceLogs();
    },
};`,
                `<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with('employee');

        if ($request->filled('department')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('time_in', [$request->date_from, $request->date_to]);
        }

        return response()->json($query->orderByDesc('time_in')->paginate(20));
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $attendance = Attendance::create([
            'employee_id' => $validated['employee_id'],
            'time_in' => now(),
            'status' => now()->format('H:i') > '06:15' ? 'Late' : 'On Time',
        ]);

        return response()->json($attendance, 201);
    }

    public function checkOut(Request $request, Attendance $attendance)
    {
        $attendance->update([
            'time_out' => now(),
            'status' => 'Time Out',
        ]);

        return response()->json($attendance);
    }

    public function summary()
    {
        return response()->json([
            'total_employees' => Employee::count(),
            'present_today' => Attendance::whereDate('time_in', today())->count(),
            'late_today' => Attendance::whereDate('time_in', today())
                ->where('status', 'Late')
                ->count(),
        ]);
    }
}`,
                `function calculateTotal(items) {
    return items.reduce((sum, i) => sum + i.price, 0);
}`,
                `const debounce = (fn, delay) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};`,
                `SELECT users.name, COUNT(orders.id) AS total_orders
FROM users
LEFT JOIN orders ON orders.user_id = users.id
GROUP BY users.id;`,
                `public function index()
{
    return response()->json(
        Employee::with('department')->get()
    );
}`,
                `const isEven = (n) => n % 2 === 0;
console.log([1,2,3,4].filter(isEven));`,
                `class Stack {
    constructor() { this.items = []; }
    push(item) { this.items.push(item); }
    pop() { return this.items.pop(); }
}`,
                `export default {
    computed: {
        fullName() {
            return \`\${this.first} \${this.last}\`;
        }
    }
}`,
                `def fibonacci(n):
    a, b = 0, 1
    for _ in range(n):
        a, b = b, a + b
    return a`,
            ],
            floatPos: { x: 0, y: 0 },
            floatSize: { w: 360, h: 240 },
            drag: null,
            resize: null,
        };
    },
    computed: {
        // Details endpoint returns clean text; search snippets are only a fallback
        // so the title/channel show instantly while details are loading.
        displayTitle() {
            return this.videoDetails?.title || this.currentVideoTitle;
        },
        displayChannel() {
            return this.videoDetails?.channelTitle || this.currentChannelTitle;
        },
        sideList() {
            return this.sideTab === "related" ? this.relatedResults : this.results;
        },
        sideLoading() {
            return this.sideTab === "related" ? this.loadingRelated : this.loading;
        },
        sideEmptyText() {
            return this.sideTab === "related"
                ? "No related videos found."
                : "No results yet. Try searching for something above.";
        },
        floatStyle() {
            return {
                top: this.floatPos.y + 'px',
                left: this.floatPos.x + 'px',
                width: this.floatSize.w + 'px',
                height: this.floatSize.h + 'px',
            };
        },
    },
    watch: {
        searchQuery(newVal){
            clearTimeout(this._searchDebounce);

            if(!newVal || !newVal.trim()){
                this.results = [];
                this.nextPageToken = "";
                return;
            }
            this._searchDebounce = setTimeout(() => {
                this.searchVideos();
            }, 500);
        },
        searchOrder(){
            if(this.searchQuery && this.searchQuery.trim()){
                this.searchVideos();
            }
        },
        currentVideoId(videoId) {
            this.playerError = null;
            this.loadPlayer(videoId);
        },
        // Hide the page scrollbar behind the full-screen chat overlay.
        chatMode(active) {
            document.documentElement.style.overflow = active ? "hidden" : "";
        },
    },

    methods: {
      parseDuration(iso) {
        if (!iso) return "";
        const match = iso.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);
        if (!match) return "";

        const h = parseInt(match[1] || 0, 10);
        const m = parseInt(match[2] || 0, 10);
        const s = parseInt(match[3] || 0, 10);

        const pad = (n) => String(n).padStart(2, "0");

        if (h > 0) return `${h}:${pad(m)}:${pad(s)}`;
        return `${m}:${pad(s)}`;
    },

async fetchDurations(videoList) {
    const ids = videoList.map((v) => v.videoId).join(",");
    if (!ids) return;

    try {
        const response = await axios.get("/api/youtube/durations", { params: { ids } });
        const { durations = {}, notEmbeddable = [] } = response.data;
        const blocked = new Set(notEmbeddable);

        videoList.forEach((video) => {
            if (durations[video.videoId]) {
                video.duration = this.parseDuration(durations[video.videoId]);
            }
            // false = owner disabled embedding; the card is shown greyed out and can't be played
            video.embeddable = !blocked.has(video.videoId);
        });
       // Dito na natin pinipilit mag-re-render si Vue — dahil galing ang
        // videoList param sa ibang array reference (hindi direktang this.results),
        // hindi agad napapansin ang pagbabago kung hindi natin i-re-assign.
        this.results = [...this.results];
        this.relatedResults = [...this.relatedResults];
    } catch (error) {
        console.warn("Failed to fetch durations", error);
    }
},
        showError(message) {
            this.snackbar.message = message;
            this.snackbar.color = "error";
            this.snackbar.show = true;
        },

        showInfo(message) {
            this.snackbar.message = message;
            this.snackbar.color = "info";
            this.snackbar.show = true;
        },

        // ===== PLAYER (YouTube IFrame API) =====
        // The player instance lives on this._ytPlayer (not in data) so Vue doesn't
        // wrap YouTube's object in a reactive proxy.
        loadPlayer(videoId) {
            if (!videoId || this.playerFallback) return; // fallback iframe follows currentVideoId on its own
            if (this._ytPlayer) {
                // Before onReady the player can't take commands; onReady loads the latest video.
                if (this._ytPlayerReady) this._ytPlayer.loadVideoById(videoId);
                return;
            }
            this.createPlayer();
        },

        async createPlayer() {
            if (this._ytPlayerCreating) return;
            this._ytPlayerCreating = true;
            try {
                const YT = await loadYouTubeIframeApi();
                await this.$nextTick(); // the player slot renders together with currentVideoId
                const slot = this.$refs.playerSlot;
                if (!slot || this._unmounting || this._ytPlayer) return;

                // Give YouTube its own child element to replace, so it never swaps out a node Vue owns.
                const mount = document.createElement("div");
                slot.appendChild(mount);

                const startId = this.currentVideoId;
                this._ytPlayer = new YT.Player(mount, {
                    host: "https://www.youtube-nocookie.com",
                    videoId: startId,
                    playerVars: { autoplay: 1, rel: 0, playsinline: 1 },
                    events: {
                        onReady: (event) => {
                            this._ytPlayerReady = true;
                            // The user may have picked another video while the player was starting.
                            if (this.currentVideoId && this.currentVideoId !== startId) {
                                event.target.loadVideoById(this.currentVideoId);
                            }
                        },
                        onError: (event) => this.onPlayerError(event.data),
                    },
                });
            } catch (error) {
                // Don't leave the user with a blank player: fall back to the plain embed,
                // which only needs youtube-nocookie.com. Videos still play; we just can't
                // catch "can't be played here" errors in this mode.
                console.warn("YouTube IFrame API unavailable, using plain embed:", error);
                this.playerFallback = true;
            } finally {
                this._ytPlayerCreating = false;
            }
        },

        destroyPlayer() {
            if (this._ytPlayer && typeof this._ytPlayer.destroy === "function") {
                this._ytPlayer.destroy();
            }
            this._ytPlayer = null;
            this._ytPlayerReady = false;
        },

        onPlayerError(code) {
            const videoId = this.currentVideoId;
            const embedBlocked = YT_ERRORS_EMBED_BLOCKED.includes(code);
            const cantPlay = embedBlocked || code === YT_ERROR_NOT_FOUND;

            // Grey it out in the results too, so it isn't picked again
            if (embedBlocked) this.markNotEmbeddable(videoId);

            // Something is queued: skip to it, like a playlist would
            if (cantPlay && this.queue.length) {
                const skippedTitle = this.displayTitle;
                this.playFromQueue(0);
                this.showInfo(`Skipped "${skippedTitle}" because it can't be played here.`);
                return;
            }

            let message = "YouTube couldn't play this video.";
            if (embedBlocked) message = "The owner turned off playback on other websites.";
            else if (code === YT_ERROR_NOT_FOUND) message = "This video was removed or set to private.";
            this.playerError = { code, videoId, message };
        },

        markNotEmbeddable(videoId) {
            [...this.results, ...this.relatedResults].forEach((video) => {
                if (video.videoId === videoId) video.embeddable = false;
            });
            this.results = [...this.results];
            this.relatedResults = [...this.relatedResults];
        },

        // Search API item -> the video shape the lists use
        mapSearchItem(item) {
            return {
                videoId: item.id.videoId,
                title: this.decodeHtml(item.snippet.title),
                channelTitle: this.decodeHtml(item.snippet.channelTitle),
                thumbnail: item.snippet.thumbnails?.medium?.url,
                publishedAt: item.snippet.publishedAt,
            };
        },

        async fetchRelated(videoId) {
            this.relatedResults = [];
            this.loadingRelated = true;
            try {
                const response = await axios.get(YOUTUBE_RELATED_ENDPOINT, { params: { id: videoId } });
                if (videoId !== this.currentVideoId) return; // user already moved on to another video

                const queued = new Set(this.queue.map((video) => video.videoId));
                const mapped = (response.data.items || [])
                    .filter((item) => item.id?.videoId && !queued.has(item.id.videoId))
                    .map(this.mapSearchItem);

                this.relatedResults = mapped;
                this.fetchDurations(mapped);
            } catch (error) {
                // Quiet: the user can still switch to their search results
                console.warn("Failed to fetch related videos", error);
            } finally {
                if (videoId === this.currentVideoId) this.loadingRelated = false;
            }
        },
    async fetchVideoDetails(videoId) {
            this.videoDetails = null;
            this.loadingDetails = true;
            try {
                const response = await axios.get("/api/youtube/video", { params: { id: videoId } });
                const item = response.data.items?.[0];
                if (item) {
                    this.videoDetails = {
                        title: item.snippet.title,
                        description: item.snippet.description,
                        channelTitle: item.snippet.channelTitle,
                        publishedAt: item.snippet.publishedAt,
                        viewCount: item.statistics?.viewCount,
                        likeCount: item.statistics?.likeCount,
                    };
                }
            } catch (error) {
                console.warn("Failed to fetch video details", error);
            } finally {
                this.loadingDetails = false;
            }
    },

    formatDate(dateStr) {
        if (!dateStr) return "";
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric", month: "long", day: "numeric",
        });
    },

    formatShortDate(dateStr) {
        if (!dateStr) return "";
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric", month: "short", day: "numeric",
        });
    },

    // "3 days ago", "2 weeks ago" — same wording as YouTube.
    timeAgo(dateStr) {
        if (!dateStr) return "";
        const seconds = Math.max(0, Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000));
        const units = [
            ["year", 31536000],
            ["month", 2592000],
            ["week", 604800],
            ["day", 86400],
            ["hour", 3600],
            ["minute", 60],
        ];
        for (const [name, size] of units) {
            const value = Math.floor(seconds / size);
            if (value >= 1) return `${value} ${name}${value > 1 ? "s" : ""} ago`;
        }
        return "just now";
    },

    // Shown under every video card: "3 days ago • Oct 16, 2026"
    uploadedLabel(dateStr) {
        if (!dateStr) return "";
        return `${this.timeAgo(dateStr)} • ${this.formatShortDate(dateStr)}`;
    },

    // The search API returns titles HTML-escaped (e.g. &#39; and &amp;).
    decodeHtml(text) {
        if (!text) return "";
        const el = document.createElement("textarea");
        el.innerHTML = text;
        return el.value;
    },

    channelInitial(name) {
        return (name || "?").trim().charAt(0).toUpperCase();
    },

    formatCount(num) {
        if (!num) return "0";
        num = parseInt(num, 10);
        if (num >= 1000000) return (num / 1000000).toFixed(1) + "M";
        if (num >= 1000) return (num / 1000).toFixed(1) + "K";
        return num.toString();
    },
        // Thin wrapper around our own backend endpoints. Key rotation and
        // quota handling now happen server-side in YoutubeController.
        async youtubeRequest(endpoint, params) {
            return axios.get(endpoint, { params });
        },

        async fetchLiveVideos() {
            this.loadingLive = true;
            try {
                const response = await this.youtubeRequest(YOUTUBE_LIVE_ENDPOINT, {
                    q: this.searchQuery || "live",
                    maxResults: 4,
                });

                const items = response.data.items || [];
                this.liveResults = items.map((item) => ({
                    videoId: item.id.videoId,
                    title: this.decodeHtml(item.snippet.title),
                    channelTitle: this.decodeHtml(item.snippet.channelTitle),
                    thumbnail: item.snippet.thumbnails?.medium?.url,
                    publishedAt: item.snippet.publishedAt,
                }));
            } catch (error) {
                // SILENT ERROR: Huwag magpakita ng snackbar para sa Live Videos
                // para hindi ma-distract ang user kung search naman ang habol nila.
                console.warn("Live videos failed silently (probably quota)");
            } finally {
                this.loadingLive = false;
            }
        },

        async searchVideos(loadMore = false) {
            const query = (this.searchQuery || "").trim();
            if (!query) return;

            if (loadMore) {
                // Only one page request at a time, and never while a new search is running
                if (this.loadingMore || this.loading || !this.nextPageToken) return;
                this.loadingMore = true;
            } else {
                // Huwag muna i-clear ang results para hindi "kumukurap" ang screen
                this.loading = true;
                this.autoLoadPaused = false;
                this.searchGeneration++;
                // Typing a new search while watching: show those results in the side column
                if (this.currentVideoId) this.sideTab = "search";
            }
            const generation = this.searchGeneration;

            try {
                const response = await this.youtubeRequest(YOUTUBE_SEARCH_ENDPOINT, {
                    q: query,
                    maxResults: 10,
                    order: this.searchOrder,
                    pageToken: loadMore ? this.nextPageToken : undefined,
                });

                // A newer search started while this page was loading — drop it
                if (generation !== this.searchGeneration) return;

                const items = response.data.items || [];
                const mapped = items.map(this.mapSearchItem);

                if (!loadMore) {
                    this.results = mapped; // Dito lang natin palitan ang results
                } else {
                    this.results = [...this.results, ...mapped];
                }
                this.nextPageToken = response.data.nextPageToken || "";
                this.fetchDurations(mapped);
            } catch (error) {
                console.error("Search Error:", error);

                // --- ETO ANG LOGIC NA GUSTO MO ---
                // Ipakita lang ang Error Snackbar KUNG walang laman ang results.
                // Kung may results naman, itago lang ang error (silent fail).
                if (this.results.length === 0) {
                    const msg = error.response?.data?.message || "Quota exceeded. Try again later.";
                    this.showError(msg);
                } else {
                    console.warn("Quota reached for next results, but keeping current view.");
                }

                // Stop auto-loading and show the Load more button instead,
                // otherwise the still-visible sentinel would retry (and burn quota) in a loop.
                if (loadMore && generation === this.searchGeneration) this.autoLoadPaused = true;
            } finally {
                if (loadMore) {
                    this.loadingMore = false;
                } else if (generation === this.searchGeneration) {
                    this.loading = false;
                }
                // If the new page was too short to push the sentinel off screen, keep going
                this.$nextTick(this.maybeAutoLoad);
            }
        },

        onResultsEndVisible(isVisible) {
            this.resultsEndVisible = isVisible;
            this.maybeAutoLoad();
        },

        maybeAutoLoad() {
            if (!this.resultsEndVisible || this.autoLoadPaused) return;
            if (this.currentVideoId && this.sideTab !== "search") return;
            if (this.loading || this.loadingMore || !this.nextPageToken) return;
            this.searchVideos(true);
        },

        retryLoadMore() {
            this.autoLoadPaused = false;
            this.searchVideos(true);
        },

        playVideo(video) {
            if (video.embeddable === false) return;
            this.currentVideoId = video.videoId;
            this.currentVideoTitle = video.title;
            this.currentChannelTitle = video.channelTitle;
            this.showDescription = false;
            this.fetchVideoDetails(video.videoId);
            this.sideTab = "related";
            this.fetchRelated(video.videoId);
            window.scrollTo({ top: 0 });
        },

        addToQueue(video) {
            if (video.embeddable === false) return;
            this.queue.push(video);
            this.results = this.results.filter((r) => r.videoId !== video.videoId);
            this.relatedResults = this.relatedResults.filter((r) => r.videoId !== video.videoId);
        },

        removeFromQueue(index) {
            this.queue.splice(index, 1);
        },

        playFromQueue(index) {
            const video = this.queue[index];
            if (video) {
                // playVideo() already fetches the details — no second API call needed.
                this.playVideo(video);
                this.queue.splice(index, 1);
            }
        },
///        openFloating() {
        //     this.floatPos = {
        //         x: Math.max(16, window.innerWidth - this.floatSize.w - 24),
        //         y: Math.max(16, window.innerHeight - this.floatSize.h - 24),
        //     };
        //     this.isFloating = true;
        //     this.chatMode = true;
        // },

        openFloating() {
            // Phones get a smaller window so the chat stays usable.
            if (window.innerWidth < 768) {
                const w = Math.min(240, window.innerWidth - 32);
                this.floatSize = { w, h: Math.round((w * 9) / 16) + 32 };
            } else {
                this.floatSize = {
                    w: Math.min(this.floatSize.w, window.innerWidth - 48),
                    h: Math.min(this.floatSize.h, window.innerHeight - 96),
                };
            }

            // Top-right, under the chat top bar, so it never covers the message box.
            this.floatPos = {
                x: Math.max(16, window.innerWidth - this.floatSize.w - 24),
                y: 64,
            };
            this.isFloating = true;
            this.chatMode = true;
        },

        closeFloating() {
            this.isFloating = false;
            this.chatMode = false;
            this.chatMenuOpen = false;
        },

        expandDescription() {
            if (!this.showDescription) this.showDescription = true;
        },

        toggleTheater() {
            this.theaterMode = !this.theaterMode;
            try {
                localStorage.setItem(THEATER_STORAGE_KEY, this.theaterMode ? "1" : "0");
            } catch (e) {
                // storage blocked — theater mode still works for this visit
            }
        },

        // "t" toggles theater mode, same shortcut as YouTube.
        onGlobalKeydown(event) {
            if (event.key !== "t" && event.key !== "T") return;
            if (event.ctrlKey || event.metaKey || event.altKey) return;
            const target = event.target;
            if (target && (target.tagName === "INPUT" || target.tagName === "TEXTAREA" || target.isContentEditable)) return;
            if (!this.currentVideoId || this.isFloating) return;
            this.toggleTheater();
        },

        // Accepts a suggestion prompt; otherwise sends what's in the composer.
        sendChatMessage(prompt) {
            const text = (typeof prompt === "string" ? prompt : this.chatInput).trim();
            if (!text || this.aiTyping) return;

            this.chatMessages.push({ role: "user", text });
            this.chatInput = "";
            this.resetComposerHeight();
            this.aiTyping = true;
            this.aiThinking = true;
            this.scrollChatToBottom();

            const delay = 700 + Math.random() * 900;
            this._replyTimer = setTimeout(() => {
                const code = this.pickSnippet(text);
                const intro = this.replyIntros[Math.floor(Math.random() * this.replyIntros.length)];
                this.aiThinking = false;
                this.chatMessages.push({
                    role: "ai",
                    intro,
                    lang: this.detectLanguage(code),
                    full: code,
                    text: "",
                });
                this.streamReply(this.chatMessages.length - 1);
            }, delay);
        },

        // Types the code out gradually, like a real AI reply.
        streamReply(index) {
            const msg = this.chatMessages[index];
            const reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

            if (reduceMotion) {
                msg.text = msg.full;
                this.aiTyping = false;
                this.scrollChatToBottom();
                return;
            }

            const step = Math.max(2, Math.ceil(msg.full.length / 90));
            this._streamTimer = setInterval(() => {
                msg.text = msg.full.slice(0, msg.text.length + step);
                this.scrollChatToBottom();
                if (msg.text.length >= msg.full.length) {
                    clearInterval(this._streamTimer);
                    this._streamTimer = null;
                    this.aiTyping = false;
                }
            }, 16);
        },

        // Answer SQL questions with SQL, Laravel questions with PHP, etc.
        pickSnippet(question) {
            const q = question.toLowerCase();
            let wanted = null;
            if (/sql|query|database|join/.test(q)) wanted = "SQL";
            else if (/php|laravel|controller|eloquent/.test(q)) wanted = "PHP";
            else if (/python/.test(q)) wanted = "Python";
            else if (/vue|javascript|js|react|function|debounce|array/.test(q)) wanted = "JavaScript";

            const matches = wanted
                ? this.codeSnippets.filter((code) => this.detectLanguage(code) === wanted)
                : [];
            const pool = matches.length ? matches : this.codeSnippets;
            return pool[Math.floor(Math.random() * pool.length)];
        },

        detectLanguage(code) {
            if (/^<\?php|public function|\$this->/.test(code)) return "PHP";
            if (/^\s*(SELECT|INSERT|UPDATE|DELETE)\b/i.test(code)) return "SQL";
            if (/^\s*def\s/m.test(code)) return "Python";
            return "JavaScript";
        },

        async copyCode(index) {
            const msg = this.chatMessages[index];
            try {
                await navigator.clipboard.writeText(msg.full);
            } catch (e) {
                // clipboard blocked — still show feedback so it looks normal
            }
            this.copiedIndex = index;
            clearTimeout(this._copyTimer);
            this._copyTimer = setTimeout(() => {
                this.copiedIndex = null;
            }, 1500);
        },

        startNewChat() {
            this.clearChatTimers();
            this.chatMessages = [];
            this.chatInput = "";
            this.aiTyping = false;
            this.aiThinking = false;
            this.chatMenuOpen = false;
            this.resetComposerHeight();
        },

        clearChatTimers() {
            clearTimeout(this._replyTimer);
            clearInterval(this._streamTimer);
            clearTimeout(this._copyTimer);
            this._streamTimer = null;
        },

        autoGrowComposer(event) {
            const el = event.target;
            el.style.height = "auto";
            el.style.height = Math.min(el.scrollHeight, 200) + "px";
        },

        resetComposerHeight() {
            const el = this.$refs.composer;
            if (el) el.style.height = "";
        },

        scrollChatToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.chatThread;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        startDrag(event) {
            if (event.target.closest("button")) return; // let the close button click
            event.preventDefault();
            this.drag = {
                startX: event.clientX,
                startY: event.clientY,
                origX: this.floatPos.x,
                origY: this.floatPos.y,
            };
            window.addEventListener("pointermove", this.onDrag);
            window.addEventListener("pointerup", this.stopDrag);
            window.addEventListener("pointercancel", this.stopDrag);
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
            window.removeEventListener("pointermove", this.onDrag);
            window.removeEventListener("pointerup", this.stopDrag);
            window.removeEventListener("pointercancel", this.stopDrag);
        },

        startResize(event) {
            event.stopPropagation();
            event.preventDefault();
            this.resize = {
                startX: event.clientX,
                startY: event.clientY,
                origW: this.floatSize.w,
                origH: this.floatSize.h,
            };
            window.addEventListener("pointermove", this.onResize);
            window.addEventListener("pointerup", this.stopResize);
            window.addEventListener("pointercancel", this.stopResize);
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
            window.removeEventListener("pointermove", this.onResize);
            window.removeEventListener("pointerup", this.stopResize);
            window.removeEventListener("pointercancel", this.stopResize);
        },
        
    },

    mounted() {
        this.fetchLiveVideos();
        this.searchVideos();
        window.addEventListener("keydown", this.onGlobalKeydown);
    },

    beforeUnmount() {
        this._unmounting = true; // stops a player that is still being created
        this.destroyPlayer();
        window.removeEventListener("keydown", this.onGlobalKeydown);
        document.documentElement.style.overflow = "";
        this.stopDrag();
        this.stopResize();
        this.clearChatTimers();
    },
};
</script>

<style scoped>
/* ===== PAGE ===== */
.watch-page {
    min-height: 100%;
    padding-bottom: 48px;
    overflow-x: clip; /* theater player bleeds to the viewport edges */
}

.load-more-row {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 44px; /* keeps the observer target measurable when it's empty */
}

.empty-text {
    color: rgb(var(--v-theme-on-surface-variant));
    font-size: 14px;
    padding: 24px 0;
}

.is-loading {
    opacity: 0.5;
    pointer-events: none;
    transition: opacity 0.15s ease;
}

/* ===== SEARCH ===== */
.search-strip {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 16px 24px 16px;
}

.search-bar {
    display: flex;
    width: 100%;
    max-width: 640px;
    height: 40px;
}

.search-box {
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    padding: 0 4px 0 16px;
    background-color: rgb(var(--v-theme-background));
    border: 1px solid rgba(var(--v-theme-on-surface), 0.18);
    border-right: none;
    border-radius: 40px 0 0 40px;
}

.search-box:focus-within {
    border-color: rgb(var(--v-theme-primary));
}

.search-box-icon {
    color: rgb(var(--v-theme-on-surface-variant));
}

.search-input {
    flex: 1 1 auto;
    min-width: 0;
    height: 100%;
    background: transparent;
    border: none;
    outline: none;
    color: rgb(var(--v-theme-on-surface));
    font-family: inherit;
    font-size: 16px;
}

.search-input::placeholder {
    color: rgb(var(--v-theme-on-surface-variant));
}

.search-submit {
    flex: 0 0 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(var(--v-theme-on-surface), 0.08);
    border: 1px solid rgba(var(--v-theme-on-surface), 0.18);
    border-radius: 0 40px 40px 0;
    color: rgb(var(--v-theme-on-surface));
    cursor: pointer;
}

.search-submit:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.16);
}

.chip-row {
    display: flex;
    gap: 12px;
}

.yt-chip {
    height: 32px;
    padding: 0 12px;
    border: none;
    border-radius: 8px;
    background-color: rgba(var(--v-theme-on-surface), 0.1);
    color: rgb(var(--v-theme-on-surface));
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.yt-chip:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.2);
}

.yt-chip--active,
.yt-chip--active:hover {
    background-color: rgb(var(--v-theme-on-surface));
    color: rgb(var(--v-theme-background));
}

/* ===== SHARED BUTTONS ===== */
.icon-btn {
    width: 36px;
    height: 36px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 50%;
    color: rgb(var(--v-theme-on-surface));
    cursor: pointer;
}

.icon-btn:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.12);
}

.yt-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 36px;
    padding: 0 16px;
    background-color: rgba(var(--v-theme-on-surface), 0.1);
    border: none;
    border-radius: 18px;
    color: rgb(var(--v-theme-on-surface));
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    cursor: pointer;
}

.yt-pill:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.2);
}

.yt-pill--static,
.yt-pill--static:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.1);
    cursor: default;
}

.yt-chip:focus-visible,
.yt-pill:focus-visible,
.icon-btn:focus-visible,
.search-submit:focus-visible,
.description-toggle:focus-visible,
.compact-row:focus-visible,
.video-card:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}

/* ===== WATCH LAYOUT ===== */
.watch-grid {
    --side-width: 402px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) var(--side-width);
    grid-template-rows: auto 1fr;
    grid-template-areas:
        "player side"
        "info   side";
    column-gap: 24px;
    align-items: start;
    max-width: 1754px;
    margin: 0 auto;
    padding: 8px 24px 0;
}

.watch-grid--theater {
    grid-template-areas:
        "player player"
        "info   side";
    padding-top: 0;
}

.player-host {
    grid-area: player;
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    max-height: calc(100vh - 64px - 140px);
    background-color: #000;
    border-radius: 12px;
    overflow: hidden;
}

/* Theater: full-bleed black band across the whole window */
.player-host--theater {
    width: 100vw;
    margin-left: calc(50% - 50vw);
    aspect-ratio: auto;
    height: min(56.25vw, max(480px, calc(100vh - 64px - 140px)));
    max-height: none;
    border-radius: 0;
}

.player-host--floating {
    position: fixed;
    z-index: 2400;
    aspect-ratio: unset;
    max-height: none;
    margin: 0;
    display: flex;
    flex-direction: column;
    border: 1px solid rgb(var(--v-theme-surface-variant));
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
    min-width: 240px;
    min-height: 160px;
}

.player-frame-wrap {
    position: relative;
    flex: 1 1 auto;
    width: 100%;
    height: 100%;
}

.player-frame {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

.player-popout-btn {
    position: absolute;
    top: 64px;
    right: 12px;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    padding: 0;
    background-color: rgba(0, 0, 0, 0.6);
    border: none;
    border-radius: 50%;
    color: #fff;
    cursor: pointer;
}

.player-popout-btn:hover {
    background-color: rgba(0, 0, 0, 0.85);
}

.player-popout-btn:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}

/* The IFrame API's iframe has no scoped attribute, hence :deep */
.player-frame :deep(iframe) {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
}

/* While dragging/resizing, stop the iframe from swallowing the pointer */
.player-host--dragging .player-frame {
    pointer-events: none;
}

.player-error {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    text-align: center;
    background-color: rgb(var(--v-theme-surface));
    color: rgb(var(--v-theme-on-surface));
}

.player-error-title {
    font-size: 18px;
    font-weight: 600;
}

.player-error-text {
    max-width: 40ch;
    margin-top: 6px;
    color: rgb(var(--v-theme-on-surface-variant));
    font-size: 14px;
}

.player-error-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 16px;
    text-decoration: none;
}

.float-header {
    touch-action: none;
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
    color: white;
}

.float-actions {
    display: flex;
    align-items: center;
}

.float-resize-handle {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 24px;
    height: 24px;
    cursor: nwse-resize;
    touch-action: none;
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

/* ===== VIDEO INFO ===== */
.watch-info {
    grid-area: info;
    min-width: 0;
    padding-top: 12px;
}

.watch-title {
    margin: 0 0 12px;
    font-size: 20px;
    line-height: 28px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.watch-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.channel {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.channel-avatar {
    width: 40px;
    height: 40px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background-color: rgba(var(--v-theme-on-surface), 0.12);
    font-size: 16px;
    font-weight: 600;
}

.channel-avatar--sm {
    width: 36px;
    height: 36px;
    font-size: 14px;
}

.channel-name {
    font-size: 16px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.watch-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.description-box {
    margin-top: 12px;
    padding: 12px;
    border-radius: 12px;
    background-color: rgba(var(--v-theme-on-surface), 0.1);
    font-size: 14px;
    line-height: 20px;
    cursor: pointer;
}

.description-box:not(.description-box--open):hover {
    background-color: rgba(var(--v-theme-on-surface), 0.16);
}

.description-box--open {
    cursor: default;
}

.description-stats {
    display: flex;
    flex-wrap: wrap;
    column-gap: 10px;
    margin-bottom: 4px;
    font-weight: 600;
}

.description-text {
    white-space: pre-line;
    overflow-wrap: anywhere;
}

.description-clamped {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.description-toggle {
    margin-top: 4px;
    padding: 0;
    background: none;
    border: none;
    color: rgb(var(--v-theme-on-surface));
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

/* ===== SIDE COLUMN ===== */
.watch-side {
    grid-area: side;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.watch-grid--theater .watch-side {
    padding-top: 12px;
}

.side-tabs {
    margin-bottom: 12px;
}

.side-heading {
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 600;
}

.queue-panel {
    border: 1px solid rgba(var(--v-theme-on-surface), 0.18);
    border-radius: 12px;
    overflow: hidden;
}

.queue-head {
    padding: 12px 16px;
    background-color: rgba(var(--v-theme-on-surface), 0.06);
    font-size: 16px;
    font-weight: 600;
}

.queue-count {
    margin-left: 6px;
    color: rgb(var(--v-theme-on-surface-variant));
    font-weight: 400;
}

.queue-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 360px;
    padding: 8px;
    overflow-y: auto;
}

.compact-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.compact-row {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    border-radius: 8px;
    cursor: pointer;
}

.compact-row--active {
    background-color: rgba(var(--v-theme-on-surface), 0.1);
}

.compact-text {
    flex: 1 1 auto;
    min-width: 0;
    padding: 2px 32px 0 0;
}

.row-action {
    position: absolute;
    top: -4px;
    right: -6px;
}

/* ===== THUMBNAILS & TEXT ===== */
.thumb {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    background-color: rgba(var(--v-theme-on-surface), 0.1);
}

.thumb--compact {
    flex: 0 0 168px;
    width: 168px;
    border-radius: 8px;
}

.thumb--queue {
    flex-basis: 120px;
    width: 120px;
}

.duration-badge {
    position: absolute;
    right: 6px;
    bottom: 6px;
    padding: 1px 4px;
    border-radius: 4px;
    background-color: rgba(0, 0, 0, 0.8);
    color: #fff;
    font-size: 12px;
    font-weight: 500;
    line-height: 18px;
}

.video-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 4px;
    font-size: 16px;
    line-height: 22px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.video-title--compact {
    font-size: 14px;
    line-height: 20px;
}

.video-sub {
    overflow: hidden;
    color: rgb(var(--v-theme-on-surface-variant));
    font-size: 12px;
    line-height: 18px;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.video-sub--lg {
    font-size: 14px;
    line-height: 20px;
}

/* ===== BROWSE LAYOUT ===== */
.browse {
    max-width: 1754px;
    margin: 0 auto;
    padding: 8px 24px 0;
}

.browse-section + .browse-section {
    margin-top: 40px;
}

.browse-heading {
    margin-bottom: 16px;
    font-size: 20px;
    font-weight: 700;
}

.browse-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 40px 16px;
}

.video-card {
    min-width: 0;
    border-radius: 12px;
    cursor: pointer;
}

.card-body {
    display: flex;
    gap: 12px;
    padding-top: 12px;
}

.card-text {
    flex: 1 1 auto;
    min-width: 0;
}

.thumb-action {
    position: absolute;
    top: 6px;
    right: 6px;
    background-color: rgba(0, 0, 0, 0.7);
    color: #fff;
}

.thumb-action:hover {
    background-color: rgba(0, 0, 0, 0.9);
}

.live-badge {
    position: absolute;
    top: 4px;
    left: 4px;
    font-weight: 700;
}

/* Video the owner won't let us embed: visible, but can't be played or queued */
.compact-row.is-blocked,
.video-card.is-blocked {
    cursor: not-allowed;
}

.is-blocked .thumb,
.is-blocked .video-title,
.is-blocked .video-sub,
.is-blocked .channel-avatar {
    opacity: 0.4;
}

.blocked-note {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
    color: rgb(var(--v-theme-on-surface-variant));
    font-size: 12px;
}

/* Hover-only controls on mouse devices; always visible on touch */
@media (hover: hover) {
    .row-action,
    .thumb-action {
        opacity: 0;
    }

    .compact-row:hover .row-action,
    .video-card:hover .thumb-action,
    .row-action:focus-visible,
    .thumb-action:focus-visible {
        opacity: 1;
    }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1280px) {
    .watch-grid {
        --side-width: 360px;
    }
}

@media (max-width: 960px) {
    .watch-grid,
    .watch-grid--theater {
        grid-template-columns: minmax(0, 1fr);
        grid-template-rows: auto;
        grid-template-areas:
            "player"
            "info"
            "side";
        padding: 0;
    }

    .player-host:not(.player-host--floating) {
        width: 100%;
        height: auto;
        margin-left: 0;
        aspect-ratio: 16 / 9;
        max-height: none;
        border-radius: 0;
    }

    .watch-info,
    .watch-side {
        padding: 12px 16px 0;
    }

    .watch-side {
        margin-top: 12px;
    }

    .theater-toggle {
        display: none;
    }
}

@media (max-width: 600px) {
    .search-strip {
        padding: 12px 12px 12px;
    }

    .browse {
        padding: 0;
    }

    .browse-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 24px;
    }

    .browse .thumb {
        border-radius: 0;
    }

    .card-body,
    .browse-heading,
    .browse .empty-text {
        padding-inline: 12px;
    }

    .thumb--compact {
        flex-basis: 140px;
        width: 140px;
    }
}

/* ===== CHAT MODE OVERLAY ("Diwa") ===== */
.chat-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    background-color: #0e0f11;
    color: #e8e8ea;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}

.chat-icon-btn {
    width: 36px;
    height: 36px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #c9cad0;
    cursor: pointer;
}

.chat-icon-btn:hover {
    background-color: #1f2126;
}

/* Brand mark: teal-to-blue tile with an open ring */
.brand-mark {
    position: relative;
    width: 28px;
    height: 28px;
    flex: 0 0 auto;
    border-radius: 9px;
    background: linear-gradient(135deg, #0d9488, #2563eb);
}

.brand-mark::after {
    content: '';
    position: absolute;
    inset: 7px;
    border: 2.5px solid #fff;
    border-right-color: transparent;
    border-radius: 50%;
    transform: rotate(-45deg);
}

.brand-mark--sm {
    width: 26px;
    height: 26px;
    margin-top: 2px;
}

.brand-mark--lg {
    width: 52px;
    height: 52px;
    border-radius: 15px;
}

.brand-mark--lg::after {
    inset: 13px;
    border-width: 4px;
}

.brand-mark--thinking::after {
    animation: brand-spin 0.9s linear infinite;
}

@keyframes brand-spin {
    to { transform: rotate(315deg); }
}

/* Sidebar */
.chat-sidebar {
    width: 272px;
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    padding: 14px 12px;
    background-color: #16171a;
    border-right: 1px solid #24262b;
}

.chat-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 6px 16px;
}

.brand-name {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.chat-sidebar-close {
    display: none;
    margin-left: auto;
}

.chat-new-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 40px;
    padding: 0 12px;
    background-color: #1d1f24;
    border: 1px solid #2c2f36;
    border-radius: 10px;
    color: #f2f2f3;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.chat-new-btn:hover {
    background-color: #24272d;
}

.chat-nav {
    display: flex;
    flex-direction: column;
    margin-top: 8px;
}

.chat-nav-item,
.chat-recent-item {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
}

.chat-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #c9cad0;
}

.chat-recent-item {
    color: #a9abb3;
}

.chat-nav-item:hover,
.chat-recent-item:hover {
    background-color: #1f2126;
}

.chat-recent-label {
    margin: 18px 12px 6px;
    color: #7d808a;
    font-size: 12px;
    font-weight: 600;
}

.chat-recent {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}

.chat-account {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 8px 2px;
    border-top: 1px solid #24262b;
    font-size: 14px;
}

.chat-account-avatar {
    width: 32px;
    height: 32px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background-color: #2563eb;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
}

.chat-account-plan {
    color: #7d808a;
    font-size: 12px;
}

.chat-scrim {
    display: none;
}

/* Main column */
.chat-main {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.chat-topbar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 8px;
    height: 56px;
    padding: 0 12px;
}

.chat-menu-btn {
    display: none;
}

.chat-model {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

.chat-model:hover {
    background-color: #1f2126;
}

.chat-topbar-spacer {
    flex: 1 1 auto;
}

.chat-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.chat-welcome {
    width: min(100%, 720px);
    margin: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    text-align: center;
}

.chat-greeting {
    margin: 0;
    color: #f4f4f5;
    font-size: 28px;
    line-height: 1.25;
    font-weight: 600;
    letter-spacing: -0.02em;
}

.chat-suggestions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    width: 100%;
    max-width: 560px;
}

.chat-suggestion {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: transparent;
    border: 1px solid #2c2f36;
    border-radius: 12px;
    color: #d6d7dc;
    font-family: inherit;
    font-size: 14px;
    text-align: left;
    cursor: pointer;
}

.chat-suggestion:hover {
    background-color: #1a1c20;
}

.chat-thread {
    width: min(100%, 760px);
    margin: 0 auto;
    padding: 16px 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.chat-msg--user {
    align-self: flex-end;
    max-width: 80%;
    padding: 10px 16px;
    border-radius: 18px;
    background-color: #24262b;
    color: #f2f2f3;
    font-size: 15px;
    line-height: 1.5;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.chat-msg--ai {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.chat-ai-content {
    flex: 1 1 auto;
    min-width: 0;
}

.chat-ai-intro {
    margin: 2px 0 10px;
    color: #e4e4e7;
    font-size: 15px;
    line-height: 1.6;
}

.chat-codeblock {
    overflow: hidden;
    background-color: #121316;
    border: 1px solid #2c2f36;
    border-radius: 10px;
}

.chat-codeblock-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 8px 6px 14px;
    background-color: #1a1c20;
    color: #9a9ca5;
    font-size: 12px;
}

.chat-copy-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: #9a9ca5;
    font-family: inherit;
    font-size: 12px;
    cursor: pointer;
}

.chat-copy-btn:hover {
    background-color: #24262b;
    color: #e4e4e7;
}

.chat-code {
    margin: 0;
    padding: 14px;
    overflow-x: auto;
    color: #d4d4d8;
    font-family: 'JetBrains Mono', 'Fira Code', Consolas, 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.55;
    white-space: pre;
}

.chat-thinking {
    margin-top: 4px;
    color: #9a9ca5;
    font-size: 14px;
}

/* Composer */
.chat-composer-wrap {
    flex: 0 0 auto;
    width: min(100%, 760px);
    margin: 0 auto;
    padding: 8px 20px 14px;
}

.chat-composer {
    padding: 12px 12px 8px 16px;
    background-color: #16171a;
    border: 1px solid #2c2f36;
    border-radius: 20px;
}

.chat-composer:focus-within {
    border-color: #3f434d;
}

.chat-input-field {
    width: 100%;
    max-height: 200px;
    background: transparent;
    border: none;
    outline: none;
    resize: none;
    color: #f2f2f3;
    font-family: inherit;
    font-size: 15px;
    line-height: 1.5;
}

.chat-input-field::placeholder {
    color: #7d808a;
}

.chat-composer-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
}

.chat-send-btn {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    padding: 0;
    background-color: #f2f2f3;
    border: none;
    border-radius: 50%;
    color: #0e0f11;
    cursor: pointer;
}

.chat-send-btn:disabled {
    background-color: #2c2f36;
    color: #6b6e77;
    cursor: default;
}

.chat-disclaimer {
    margin-top: 8px;
    color: #7d808a;
    font-size: 12px;
    text-align: center;
}

.chat-icon-btn:focus-visible,
.chat-new-btn:focus-visible,
.chat-suggestion:focus-visible,
.chat-copy-btn:focus-visible,
.chat-send-btn:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

/* Phone: sidebar becomes a slide-in drawer */
@media (max-width: 768px) {
    .chat-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        z-index: 2;
        width: min(84vw, 300px);
        transform: translateX(-100%);
        transition: transform 0.2s ease;
    }

    .chat-overlay--menu-open .chat-sidebar {
        transform: none;
    }

    .chat-scrim {
        display: block;
        position: fixed;
        inset: 0;
        z-index: 1;
        background-color: rgba(0, 0, 0, 0.5);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .chat-overlay--menu-open .chat-scrim {
        opacity: 1;
        pointer-events: auto;
    }

    .chat-menu-btn,
    .chat-sidebar-close {
        display: grid;
    }

    .chat-greeting {
        font-size: 22px;
    }

    .chat-suggestions {
        grid-template-columns: minmax(0, 1fr);
    }

    .chat-thread,
    .chat-composer-wrap {
        padding-inline: 12px;
    }

    .chat-msg--user {
        max-width: 88%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .chat-sidebar,
    .chat-scrim {
        transition: none;
    }

    .brand-mark--thinking::after {
        animation: none;
    }
}
</style>