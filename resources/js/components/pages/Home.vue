<template>
    <v-container fluid class="pa-4">
        <v-row>
            <!-- Attendance -->
            <v-col cols="12" md="6">
                <v-card variant="flat" color="surface" class="border">
                    <v-card-title class="d-flex align-center border-b py-3">
                        <v-icon icon="mdi-clock-outline" class="mr-2" />
                        <span class="text-subtitle-1 font-weight-bold">Live Attendance</span>
                        <v-spacer />
                        <v-chip size="small" variant="tonal">Total: {{ attendanceList.length }}</v-chip>
                    </v-card-title>

                    <v-card-text class="pa-4">
                        <v-text-field
                            v-model="attendanceSearch"
                            prepend-inner-icon="mdi-magnify"
                            label="Search attendance..."
                            variant="outlined"
                            density="comfortable"
                            hide-details
                            class="mb-4"
                        />

                        <v-data-table
                            :headers="attendanceHeaders"
                            :items="attendanceList"
                            :search="attendanceSearch"
                            :loading="loading"
                            :items-per-page="10"
                        >
                            <template #item.status="{ item }">
                                <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                                    {{ item.status }}
                                </v-chip>
                            </template>

                            <template #item.actions="{ item }">
                                <v-btn
                                    icon="mdi-logout"
                                    size="small"
                                    variant="text"
                                    :disabled="item.status === 'Time Out'"
                                    @click="handleCheckOut(item)"
                                />
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Employees -->
            <v-col cols="12" md="6">
                <v-card variant="flat" color="surface" class="border">
                    <v-card-title class="d-flex align-center border-b py-3">
                        <v-icon icon="mdi-account-group" class="mr-2" />
                        <span class="text-subtitle-1 font-weight-bold">Employee Roster</span>
                        <v-spacer />
                        <v-btn
                            size="small"
                            color="primary"
                            prepend-icon="mdi-plus-box"
                            @click="openAddEmployeeModal"
                        >
                            Add
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-4">
                        <v-text-field
                            v-model="employeeSearch"
                            prepend-inner-icon="mdi-magnify"
                            label="Search employees..."
                            variant="outlined"
                            density="comfortable"
                            hide-details
                            class="mb-4"
                        />

                        <v-data-table
                            :headers="employeeHeaders"
                            :items="employeeList"
                            :search="employeeSearch"
                            :loading="loading"
                            :items-per-page="10"
                        >
                            <template #item.avatar="{ item }">
                                <v-avatar size="32">
                                    <v-img :src="item.avatar || 'https://cdn.vuetifyjs.com/images/john.jpg'" :alt="item.name" />
                                </v-avatar>
                            </template>

                            <template #item.actions="{ item }">
                                <v-btn icon="mdi-login" size="small" variant="text" @click="quickCheckIn(item)" />
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
                { title: 'Emp ID', key: 'employee_code', width: 100 },
                { title: 'Name', key: 'name' },
                { title: 'Time In', key: 'time_in' },
                { title: 'Status', key: 'status', sortable: false },
                { title: 'Action', key: 'actions', sortable: false, align: 'center' },
            ],
            employeeHeaders: [
                { title: '', key: 'avatar', sortable: false, width: 50 },
                { title: 'Emp ID', key: 'employee_code', width: 100 },
                { title: 'Name', key: 'name' },
                { title: 'Department', key: 'department' },
                { title: 'Log', key: 'actions', sortable: false, align: 'center' },
            ],

            attendanceList: [
                { id: 1, employee_code: 'EMP-001', name: 'George', time_in: '06:00 AM', status: 'On Time' },
                { id: 2, employee_code: 'EMP-002', name: 'Ian', time_in: '06:31 AM', status: 'Late' },
                { id: 3, employee_code: 'EMP-003', name: 'Pagayanan', time_in: '06:20 AM', status: 'On Time' },
                { id: 4, employee_code: 'EMP-004', name: 'Juarez', time_in: '05:02 AM', status: 'Time Out' },
            ],
            employeeList: [
                { id: 1, employee_code: 'EMP-001', name: 'George', department: 'IHS Department', avatar: '' },
                { id: 2, employee_code: 'EMP-002', name: 'Ian', department: 'IHS Department', avatar: '' },
                { id: 3, employee_code: 'EMP-003', name: 'Pagayanan', department: 'IHS Department', avatar: '' },
                { id: 4, employee_code: 'EMP-004', name: 'Juarez', department: 'IHS Department', avatar: '' },
            ],
        }
    },

    methods: {
        statusColor(status) {
            return { 'On Time': 'success', Late: 'warning', 'Time Out': 'secondary' }[status] || 'default'
        },

        quickCheckIn(employee) {
            const alreadyIn = this.attendanceList.some(
                (a) => a.employee_code === employee.employee_code && a.status !== 'Time Out'
            )
            if (alreadyIn) return

            this.attendanceList.unshift({
                id: Date.now(),
                employee_code: employee.employee_code,
                name: employee.name,
                time_in: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                status: 'On Time',
            })
        },

        handleCheckOut(item) {
            const target = this.attendanceList.find((a) => a.id === item.id)
            if (target) target.status = 'Time Out'
        },

        openAddEmployeeModal() {
            // TODO: wire up to an add-employee dialog
        },
    },
}
</script>
