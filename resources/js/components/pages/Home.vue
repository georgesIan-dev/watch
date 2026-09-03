<template>
    <v-container fluid class="pa-4">
        <v-row>
        <!-- Attendance Column -->
        <v-col cols="12" md="6">
            <v-card class="comic-card">
            <v-card-title class="comic-card-header bg-white">
                <v-icon left color="black" class="mr-2">mdi-clock-outline</v-icon>
                <span class="comic-card-title">Live Attendance Monitoring</span>
                <v-spacer />
                <v-chip class="comic-chip bg-white" small>
                Total: {{ attendanceList.length }}
                </v-chip>
            </v-card-title>

            <v-card-text class="pa-4">
                <v-text-field
                v-model="attendanceSearch"
                append-icon="mdi-magnify"
                label="Search Attendance..."
                single-line
                hide-details
                outlined
                dense
                class="comic-input mb-4"
                />

                <v-data-table
                :headers="attendanceHeaders"
                :items="attendanceList"
                :search="attendanceSearch"
                :loading="loading"
                class="comic-table"
                :items-per-page="10"
                >
                <template v-slot:item.status="{ item }">
                    <v-chip
                    :class="getStatusColor(item.status)"
                    class="comic-status-chip"
                    x-small
                    >
                    {{ item.status }}
                    </v-chip>
                </template>

                <template v-slot:item.actions="{ item }">
                    <v-btn
                    icon
                    x-small
                    class="comic-btn-action"
                    @click="handleCheckOut(item)"
                    :disabled="item.status === 'Time Out'"
                    >
                    <v-icon color="black" small>mdi-logout</v-icon>
                    </v-btn>
                </template>
                </v-data-table>
            </v-card-text>
            </v-card>
        </v-col>

        <!-- Employees Column -->
        <v-col cols="12" md="6">
            <v-card class="comic-card">
            <v-card-title class="comic-card-header bg-white">
                <v-icon left color="black" class="mr-2">mdi-account-group</v-icon>
                <span class="comic-card-title text-black">Employee Roster</span>
                <v-spacer />
                <v-btn
                class="comic-btn bg-yellow"
                small
                @click="openAddEmployeeModal"
                >
                <v-icon left small color="black">mdi-plus-box</v-icon>
                Add
                </v-btn>
            </v-card-title>

            <v-card-text class="pa-4">
                <v-text-field
                v-model="employeeSearch"
                append-icon="mdi-magnify"
                label="Search Employees..."
                single-line
                hide-details
                outlined
                dense
                class="comic-input mb-4"
                />

                <v-data-table
                :headers="employeeHeaders"
                :items="employeeList"
                :search="employeeSearch"
                :loading="loading"
                class="comic-table"
                :items-per-page="10"
                >
                <template v-slot:item.avatar="{ item }">
                    <v-avatar size="32" class="comic-avatar">
                    <img :src="item.avatar || 'https://cdn.vuetifyjs.com/images/john.jpg'" :alt="item.name" />
                    </v-avatar>
                </template>

                <template v-slot:item.actions="{ item }">
                    <v-btn
                    icon
                    x-small
                    class="comic-btn-action mr-1"
                    @click="quickCheckIn(item)"
                    >
                    <v-icon color="black" small>mdi-login</v-icon>
                    </v-btn>
                </template>
                </v-data-table>
            </v-card-text>
            </v-card>
        </v-col>
        </v-row>
    </v-container>
</template>

<script>
export default {
name: 'MonitoringDashboard',
data() {
    return {
    loading: false,
    attendanceSearch: '',
    employeeSearch: '',
    attendanceHeaders: [
        { text: 'Emp ID', value: 'employee_code', width: '100px' },
        { text: 'Name', value: 'name' },
        { text: 'Time In', value: 'time_in' },
        { text: 'Status', value: 'status', sortable: false },
        { text: 'Action', value: 'actions', sortable: false, align: 'center' }
    ],
    employeeHeaders: [
        { text: '', value: 'avatar', sortable: false, width: '50px' },
        { text: 'Emp ID', value: 'employee_code', width: '100px' },
        { text: 'Name', value: 'name' },
        { text: 'Department', value: 'department' },
        { text: 'Log', value: 'actions', sortable: false, align: 'center' }
    ],
    attendanceList: [
        { id: 1, employee_code: 'EMP-001', name: 'George', time_in: '06:00 AM', status: 'On Time' },
        { id: 2, employee_code: 'EMP-002', name: 'Ian', time_in: '06:31 AM', status: 'Late' },
        { id: 3, employee_code: 'EMP-003', name: 'Pagayanan', time_in: '06:20 AM', status: 'On Time' },
        { id: 4, employee_code: 'EMP-004', name: 'Juarez', time_in: '05:02 AM', status: 'Time Out' }

    ],
    employeeList: [
        { id: 1, employee_code: 'EMP-001', name: 'George', department: 'IHS Department', avatar: '' },
        { id: 2, employee_code: 'EMP-002', name: 'Ian', department: 'IHS Department', avatar: '' },
        { id: 3, employee_code: 'EMP-003', name: 'Pagayanan', department: 'IHS Department', avatar: '' },
        { id: 4, employee_code: 'EMP-004', name: 'Juarez', department: 'IHS Department', avatar: '' }
    ]
    };
},
methods: {
    getStatusColor(status) {
    switch (status) {
        case 'On Time': return 'bg-green text-black';
        case 'Late': return 'bg-yellow text-black';
        case 'Time Out': return 'bg-grey-lighten-1 text-black';
        default: return 'bg-white text-black';
    }
    },
    quickCheckIn(employee) {
    const exists = this.attendanceList.some(a => a.employee_code === employee.employee_code && a.status !== 'Time Out');
    if (exists) return;
    
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    this.attendanceList.unshift({
        id: Date.now(),
        employee_code: employee.employee_code,
        name: employee.name,
        time_in: timeString,
        status: 'On Time'
    });
    },
    handleCheckOut(item) {
    const target = this.attendanceList.find(a => a.id === item.id);
    if (target) {
        target.status = 'Time Out';
    }
    },
    openAddEmployeeModal() {
    // Trigger modal handler logic
    }
}
};
</script>

<style scoped>
.comic-card {
    border: 4px solid #000000 !important;
    border-radius: 16px !important;
    box-shadow: 6px 6px 0px #000000 !important;
    background-color: #ffffff !important;
    overflow: hidden;
}

.comic-card-header {
    border-bottom: 4px solid #000000 !important;
    padding: 12px 16px !important;
}

.comic-card-title {
    font-weight: 700;
    font-size: 1.15rem;
    letter-spacing: 0.5px;
}

.comic-chip {
    border: 2px solid #000000 !important;
    font-weight: 700 !important;
    }

.comic-status-chip {
    border: 2px solid #000000 !important;
    font-weight: 700 !important;
    box-shadow: 2px 2px 0px #000000;
}

.comic-btn {
    border: 2px solid #000000 !important;
    box-shadow: 3px 3px 0px #000000 !important;
    font-weight: 700 !important;
    text-transform: none !important;
}

.comic-btn:active {
    transform: translate(2px, 2px);
    box-shadow: 1px 1px 0px #000000 !important;
}

.comic-btn-action {
    border: 2px solid #000000 !important;
    background-color: #00f0ff !important;
    box-shadow: 2px 2px 0px #000000 !important;
    }

.comic-avatar {
    border: 2px solid #000000 !important;
}

/* Custom Overrides for Inputs & Tables */
::v-deep .comic-input .v-input__control .v-input__slot {
    border: 3px solid #000000 !important;
    border-radius: 10px !important;
    box-shadow: 3px 3px 0px #000000 !important;
    background-color: #fffbe6 !important;
}

::v-deep .comic-table {
    border: 3px solid #000000 !important;
    border-radius: 12px !important;
    overflow: hidden;
}

::v-deep .comic-table th {
    background-color: #ffde59 !important;
    color: #000000 !important;
    font-weight: 700 !important;
    border-bottom: 3px solid #000000 !important;
    font-size: 0.9rem !important;
}

::v-deep .comic-table td {
    border-bottom: 1px solid #000000 !important;
    font-weight: 600;
}

.bg-cyan { background-color: #00f0ff !important; }
.bg-pink { background-color: #ff66c4 !important; }
.bg-yellow { background-color: #ffde59 !important; }
.bg-green { background-color: #7ee787 !important; }
</style>