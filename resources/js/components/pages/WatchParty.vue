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
                <template v-if="chatMessages.length === 0">
                    <div class="chat-greeting">Hey there,</div>
                </template>

                <div v-else class="chat-thread" ref="chatThread">
                    <div
                        v-for="(msg, index) in chatMessages"
                        :key="index"
                        :class="['chat-bubble', msg.role === 'user' ? 'chat-bubble--user' : 'chat-bubble--ai']"
                    >
                        <pre v-if="msg.role === 'ai'" class="chat-code">{{ msg.text }}</pre>
                        <span v-else>{{ msg.text }}</span>
                    </div>

                    <div v-if="aiTyping" class="chat-bubble chat-bubble--ai chat-bubble--typing">
                        <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                    </div>
                </div>

                <div class="chat-input-box">
                    <textarea
                        v-model="chatInput"
                        class="chat-input-field"
                        placeholder="How can I help you today?"
                        rows="1"
                        @keydown.enter.exact.prevent="sendChatMessage"
                    ></textarea>
                    <div class="chat-input-toolbar">
                        <v-icon size="18">mdi-plus</v-icon>
                        <span class="chat-toolbar-chip">Chat</span>
                        <span class="chat-toolbar-chip chat-toolbar-chip--muted">Cowork</span>
                        <v-spacer />
                        <v-btn
                            icon="mdi-send"
                            size="small"
                            variant="text"
                            :disabled="!chatInput.trim() || aiTyping"
                            @click="sendChatMessage"
                        />
                    </div>
                </div>

                <div v-if="chatMessages.length === 0" class="chat-quick-actions">
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-pencil-outline</v-icon> Write</span>
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-school-outline</v-icon> Learn</span>
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-code-tags</v-icon> Code</span>
                    <span class="chat-quick-chip"><v-icon size="16" class="mr-1">mdi-coffee-outline</v-icon> Life stuff</span>
                </div>
            </div>
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
                }"
                :style="isFloating ? floatStyle : {}"
            >
                <div v-if="isFloating" class="float-header" @mousedown="startDrag">
                    <span class="float-title">{{ displayTitle || 'Now Playing' }}</span>
                    <div class="float-actions">
                        <v-btn icon="mdi-close" size="x-small" variant="text" title="Back to normal view" @click="closeFloating" />
                    </div>
                </div>

                <div class="player-frame-wrap">
                    <iframe
                        :src="`https://www.youtube-nocookie.com/embed/${currentVideoId}?autoplay=1&rel=0`"
                        title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        class="player-frame"
                    ></iframe>
                </div>

                <div v-if="isFloating" class="float-resize-handle" @mousedown="startResize"></div>
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
                    <div class="side-heading">Search results</div>

                    <div v-if="loading && results.length === 0" class="d-flex justify-center pa-6">
                        <v-progress-circular indeterminate color="primary"></v-progress-circular>
                    </div>

                    <div v-else-if="results.length === 0" class="empty-text">
                        No results yet. Try searching for something above.
                    </div>

                    <div v-else class="compact-list" :class="{ 'is-loading': loading }">
                        <div
                            v-for="video in results"
                            :key="video.videoId"
                            class="compact-row"
                            :class="{ 'compact-row--active': video.videoId === currentVideoId }"
                            role="button"
                            tabindex="0"
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
                            </div>
                            <button
                                class="icon-btn row-action"
                                title="Add to queue"
                                aria-label="Add to queue"
                                @click.stop="addToQueue(video)"
                            >
                                <v-icon size="20">mdi-playlist-plus</v-icon>
                            </button>
                        </div>
                    </div>

                    <div v-if="nextPageToken && results.length" class="d-flex justify-center mt-4">
                        <button class="yt-pill" @click="searchVideos(true)">Load more</button>
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
                        role="button"
                        tabindex="0"
                        @click="playVideo(video)"
                        @keydown.enter="playVideo(video)"
                    >
                        <div class="thumb">
                            <v-img :src="video.thumbnail" aspect-ratio="1.7778" cover>
                                <span v-if="video.duration" class="duration-badge">{{ video.duration }}</span>
                                <button
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
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="nextPageToken && results.length" class="d-flex justify-center mt-8">
                    <button class="yt-pill" @click="searchVideos(true)">Load more</button>
                </div>
            </section>
        </div>
    </div>
</template>


<script>
import axios from "axios";
const YOUTUBE_SEARCH_ENDPOINT = "/api/youtube/search";
const YOUTUBE_LIVE_ENDPOINT = "/api/youtube/live";
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
            aiTyping: false,
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
        const durations = response.data;

        videoList.forEach((video) => {
            if (durations[video.videoId]) {
                video.duration = this.parseDuration(durations[video.videoId]);
            }
        });
       // Dito na natin pinipilit mag-re-render si Vue — dahil galing ang
        // videoList param sa ibang array reference (hindi direktang this.results),
        // hindi agad napapansin ang pagbabago kung hindi natin i-re-assign.
        this.results = [...this.results];
    } catch (error) {
        console.warn("Failed to fetch durations", error);
    }
},
        showError(message) {
            this.snackbar.message = message;
            this.snackbar.color = "error";
            this.snackbar.show = true;
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

            if (!loadMore) {
                // Huwag muna i-clear ang results para hindi "kumukurap" ang screen
                this.loading = true; 
            }

            try {
                const response = await this.youtubeRequest(YOUTUBE_SEARCH_ENDPOINT, {
                    q: query,
                    maxResults: 10,
                    order: this.searchOrder,
                    pageToken: loadMore ? this.nextPageToken : undefined,
                });

                const items = response.data.items || [];
                const mapped = items.map((item) => ({
                    videoId: item.id.videoId,
                    title: this.decodeHtml(item.snippet.title),
                    channelTitle: this.decodeHtml(item.snippet.channelTitle),
                    thumbnail: item.snippet.thumbnails?.medium?.url,
                    publishedAt: item.snippet.publishedAt,
                }));

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
            } finally {
                this.loading = false;
            }
        },

        playVideo(video) {
            this.currentVideoId = video.videoId;
            this.currentVideoTitle = video.title;
            this.currentChannelTitle = video.channelTitle;
            this.showDescription = false;
            this.fetchVideoDetails(video.videoId);
            window.scrollTo({ top: 0 });
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

        sendChatMessage() {
            const text = this.chatInput.trim();
            if (!text || this.aiTyping) return;

            this.chatMessages.push({ role: "user", text });
            this.chatInput = "";
            this.aiTyping = true;
            this.scrollChatToBottom();

            const delay = 1000 + Math.random() * 1000;
            setTimeout(() => {
                const snippet = this.codeSnippets[Math.floor(Math.random() * this.codeSnippets.length)];
                this.chatMessages.push({ role: "ai", text: snippet });
                this.aiTyping = false;
                this.scrollChatToBottom();
            }, delay);
        },

        scrollChatToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.chatThread;
                if (el) el.scrollTop = el.scrollHeight;
            });
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

    mounted() {
        this.fetchLiveVideos();
        this.searchVideos();
        window.addEventListener("keydown", this.onGlobalKeydown);
    },

    beforeUnmount() {
        window.removeEventListener("keydown", this.onGlobalKeydown);
        document.documentElement.style.overflow = "";
        window.removeEventListener("mousemove", this.onDrag);
        window.removeEventListener("mouseup", this.stopDrag);
        window.removeEventListener("mousemove", this.onResize);
        window.removeEventListener("mouseup", this.stopResize);
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
    min-height: 0;
    width: 100%;
    max-width: 720px;
    margin: 0 auto;
}

.chat-thread {
    flex: 1 1 auto;
    width: 100%;
    max-width: 640px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 8px 4px;
}

.chat-bubble {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 0.88rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}

.chat-bubble--user {
    align-self: flex-end;
    background-color: #2a2a2a;
    color: #f2f2f2;
}

.chat-bubble--ai {
    align-self: flex-start;
    background-color: #171717;
    border: 1px solid #2a2a2a;
    color: #d4d4d4;
}

.chat-code {
    margin: 0;
    font-family: 'Courier New', monospace;
    font-size: 0.82rem;
    white-space: pre;
    overflow-x: auto;
}

.chat-bubble--typing {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 14px;
}

.chat-bubble--typing .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #8a8a8a;
    animation: chat-dot-bounce 1.2s infinite ease-in-out;
}

.chat-bubble--typing .dot:nth-child(2) { animation-delay: 0.15s; }
.chat-bubble--typing .dot:nth-child(3) { animation-delay: 0.3s; }

@keyframes chat-dot-bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
    30% { transform: translateY(-4px); opacity: 1; }
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

.chat-input-field {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    resize: none;
    color: #f2f2f2;
    font-size: 0.95rem;
    font-family: inherit;
    margin-bottom: 18px;
}

.chat-input-field::placeholder {
    color: #8a8a8a;
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