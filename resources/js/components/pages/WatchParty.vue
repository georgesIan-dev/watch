<template>
    <div>
        <!-- ERROR SNACKBAR -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
            {{ snackbar.message }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar.show = false">Close</v-btn>
            </template>
        </v-snackbar>

        <!-- SHARED PLAYER HOST — one persistent iframe, repositioned via CSS between docked/floating -->
        <div
            v-if="currentVideoId"
            class="player-host"
            :class="{ 'player-host--floating': isFloating }"
            :style="isFloating ? floatStyle : {}"
        >
            <div v-if="isFloating" class="float-header" @mousedown="startDrag">
                <span class="float-title">{{ currentVideoTitle || 'Now Playing' }}</span>
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

                <v-btn
                    v-if="!isFloating"
                    icon="mdi-picture-in-picture-top-right"
                    size="small"
                    variant="flat"
                    color="surface"
                    class="float-toggle-btn"
                    title="Pop out to floating window"
                    @click="openFloating"
                />
            </div>

            <div v-if="isFloating" class="float-resize-handle" @mousedown="startResize"></div>
        </div>

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

                <!-- PLAYER AREA (contained, not full-bleed) -->
                <v-row v-if="currentVideoId">
                    <v-col cols="12">
                        <v-card variant="flat" color="surface-bright" class="border player-card mx-auto">
                            <v-card-text class="pa-0 position-relative">
                                <div v-if="!currentVideoId" class="player-placeholder d-flex align-center justify-center">
                                    <div class="text-center text-medium-emphasis">
                                        <v-icon size="64">mdi-youtube</v-icon>
                                        <div class="mt-2">Search for a video and select it to start watching</div>
                                    </div>
                                </div>

                                <div v-else-if="!isFloating" class="player-reserve"></div>

                                <div v-else class="docked-placeholder d-flex align-center justify-center">
                                    <div class="text-center text-medium-emphasis">
                                        <v-icon size="48">mdi-picture-in-picture-top-right</v-icon>
                                        <div class="mt-2">Playing in floating window</div>
                                        <v-btn size="small" variant="text" color="primary" class="mt-2" @click="isFloating = false">
                                            Dock back here
                                        </v-btn>
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- QUEUE -->
                <v-card variant="flat" color="surface-bright" class="mt-4 border">
                    <v-card-text>
                        <div class="text-subtitle-2 font-weight-bold mb-2">
                            Queue ({{ queue.length }})
                        </div>
                        <div v-if="queue.length === 0" class="text-medium-emphasis">
                            No videos queued yet. Add videos from the search results.
                        </div>
                        <v-row v-else>
                            <v-col
                                v-for="(item, index) in queue"
                                :key="item.videoId + index"
                                cols="6"
                                sm="4"
                                md="3"
                                lg="2"
                            >
                                <v-card variant="tonal" color="surface-variant" class="grid-card" @click="playFromQueue(index)">
                                    <v-img :src="item.thumbnail" aspect-ratio="1.77" cover class="grid-thumb">
                                        <v-btn
                                            icon="mdi-close"
                                            size="x-small"
                                            variant="flat"
                                            color="surface"
                                            class="grid-remove-btn"
                                            @click.stop="removeFromQueue(index)"
                                        />
                                    </v-img>
                                    <div class="pa-2">
                                        <div class="text-caption font-weight-medium grid-title">{{ item.title }}</div>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- SEARCH RESULTS (grid) -->
                <v-card variant="flat" color="surface-bright" class="mt-4 border">
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

                        <v-row v-else>
                            <v-col
                                v-for="video in results"
                                :key="video.videoId"
                                cols="6"
                                sm="4"
                                md="3"
                                lg="2"
                            >
                                <v-card variant="tonal" color="surface-variant" class="grid-card" @click="playVideo(video)">
                                    <v-img :src="video.thumbnail" aspect-ratio="1.77" cover class="grid-thumb">
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
                                        <div class="text-caption text-medium-emphasis">{{ video.channelTitle }}</div>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>

                        <div v-if="nextPageToken" class="d-flex justify-center mt-3">
                            <v-btn variant="text" size="small" @click="searchVideos(true)">
                                Load more
                            </v-btn>
                        </div>
                    </v-card-text>
                </v-card>
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

    beforeUnmount() {
        window.removeEventListener("mousemove", this.onDrag);
        window.removeEventListener("mouseup", this.stopDrag);
        window.removeEventListener("mousemove", this.onResize);
        window.removeEventListener("mouseup", this.stopResize);
    },
};
</script>

<style scoped>
.player-card {
    max-width: 900px;
}

.player-host {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background-color: black;
    border-radius: 12px;
    overflow: hidden;
}

.player-host:not(.player-host--floating) {
    max-width: 900px;
    max-height: 480px;
    margin: 0 auto;
}

.player-host--floating {
    position: fixed;
    z-index: 2400;
    aspect-ratio: unset;
    display: flex;
    flex-direction: column;
    border: 1px solid rgb(var(--v-theme-surface-variant));
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
    min-width: 240px;
    min-height: 160px;
}

.player-reserve {
    width: 100%;
    max-width: 900px;
    aspect-ratio: 16 / 9;
    max-height: 480px;
    margin: 0 auto;
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

.player-placeholder,
.docked-placeholder {
    width: 100%;
    max-width: 900px;
    height: 400px;
    margin: 0 auto;
    background-color: rgb(var(--v-theme-surface-variant));
}

.float-toggle-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    opacity: 0.85;
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

/* ===== GRID CARDS (queue + search results) ===== */
.grid-card {
    cursor: pointer;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.grid-thumb {
    position: relative;
}

.grid-remove-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    opacity: 0.9;
}

.grid-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.3;
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