<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Badge from '@/Components/Badge.vue';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

defineOptions({ layout: MainLayout });

const props = defineProps({
    summary: Object,
    cash_flow: Array,
    aging: Object,
    top_expenses: Array,
    ledger: Array,
    filters: Object,
});

// ─── Filters ──────────────────────────────────────────────────────────────────
const dateFrom = ref(props.filters?.start_date || props.summary?.start_date || '');
const dateTo   = ref(props.filters?.end_date   || props.summary?.end_date   || '');
const activeFilter = ref(props.filters?.filter || 'month');

const applyFilter = (type) => {
    activeFilter.value = type;
    router.get('/reports', { filter: type }, { preserveState: true });
};

const applyDateRange = () => {
    if (!dateFrom.value || !dateTo.value) return;
    activeFilter.value = 'custom';
    router.get('/reports', { start_date: dateFrom.value, end_date: dateTo.value }, { preserveState: true });
};

// ─── Formatting ───────────────────────────────────────────────────────────────
const fmt = (v) => new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(v || 0);
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-AE', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

// ─── Ledger Pagination ────────────────────────────────────────────────────────
const PAGE_SIZES = [10, 25, 50, 100];
const ledgerPage = ref(1);
const ledgerPerPage = ref(10);
const totalPages = computed(() => Math.max(1, Math.ceil(props.ledger.length / ledgerPerPage.value)));
const paginatedLedger = computed(() => {
    const s = (ledgerPage.value - 1) * ledgerPerPage.value;
    return props.ledger.slice(s, s + ledgerPerPage.value);
});
watch(ledgerPerPage, () => { ledgerPage.value = 1; });
function pageRange(cur, total) {
    const d = 2, r = [];
    for (let i = Math.max(1, cur - d); i <= Math.min(total, cur + d); i++) r.push(i);
    return r;
}

// ─── Chart.js Bar Chart ───────────────────────────────────────────────────────
const chartCanvas = ref(null);
let chartInstance = null;

const buildChart = () => {
    if (!chartCanvas.value || !props.cash_flow?.length) return;
    if (chartInstance) chartInstance.destroy();

    const labels  = props.cash_flow.map(m => m.month);
    const inflow  = props.cash_flow.map(m => m.inflow);
    const outflow = props.cash_flow.map(m => m.outflow);

    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Inflow (Collections)',
                    data: inflow,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 8,
                    borderSkipped: false,
                },
                {
                    label: 'Outflow (Expenses)',
                    data: outflow,
                    backgroundColor: 'rgba(239, 68, 68, 0.80)',
                    borderRadius: 8,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#94a3b8',
                    bodyColor: '#f1f5f9',
                    padding: 12,
                    callbacks: {
                        label: (ctx) => ` ${ctx.dataset.label}: ${fmt(ctx.raw)}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { weight: '700', size: 11 } } },
                y: {
                    grid: { color: 'rgba(148,163,184,0.1)' },
                    ticks: { color: '#94a3b8', font: { size: 11 }, callback: (v) => 'AED ' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) }
                },
            },
        },
    });
};

onMounted(() => nextTick(buildChart));
watch(() => props.cash_flow, () => nextTick(buildChart));

// ─── PDF Export ───────────────────────────────────────────────────────────────
const exportPDF = async () => {
    const { jsPDF } = await import('jspdf');
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const W = doc.internal.pageSize.getWidth();
    let y = 15;

    const addTitle = (text, size = 14, color = [30, 41, 59]) => {
        doc.setFontSize(size);
        doc.setTextColor(...color);
        doc.setFont('helvetica', 'bold');
        doc.text(text, 14, y);
        y += size * 0.6;
    };
    const addLine = () => { doc.setDrawColor(220, 220, 220); doc.line(14, y, W - 14, y); y += 5; };
    const addRow = (cols, widths, isBold = false) => {
        doc.setFontSize(9);
        doc.setFont('helvetica', isBold ? 'bold' : 'normal');
        doc.setTextColor(50, 50, 80);
        let x = 14;
        cols.forEach((col, i) => { doc.text(String(col), x, y); x += widths[i]; });
        y += 7;
        if (y > 270) { doc.addPage(); y = 20; }
    };

    // ── Header ──────────────────────────────────────────────────────────────
    doc.setFillColor(30, 41, 59);
    doc.rect(0, 0, W, 30, 'F');
    doc.setFontSize(18);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.text('FINANCIAL REPORT', 14, 18);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(148, 163, 184);
    doc.text(`Period: ${props.summary.period_label}`, 14, 25);
    doc.text(`Generated: ${new Date().toLocaleDateString('en-AE')}`, W - 55, 25);
    y = 42;

    // ── Summary ─────────────────────────────────────────────────────────────
    doc.setFillColor(248, 250, 252);
    doc.roundedRect(14, y, (W - 28) / 2 - 3, 22, 3, 3, 'F');
    doc.setFontSize(8); doc.setTextColor(100, 116, 139); doc.setFont('helvetica', 'bold');
    doc.text('TOTAL SALES', 20, y + 8);
    doc.setFontSize(13); doc.setTextColor(16, 185, 129); doc.setFont('helvetica', 'bold');
    doc.text(fmt(props.summary.total_sales), 20, y + 18);

    const x2 = 14 + (W - 28) / 2 + 3;
    doc.setFillColor(248, 250, 252);
    doc.roundedRect(x2, y, (W - 28) / 2 - 3, 22, 3, 3, 'F');
    doc.setFontSize(8); doc.setTextColor(100, 116, 139); doc.setFont('helvetica', 'bold');
    doc.text('TOTAL EXPENSES', x2 + 6, y + 8);
    doc.setFontSize(13); doc.setTextColor(239, 68, 68); doc.setFont('helvetica', 'bold');
    doc.text(fmt(props.summary.total_expenses), x2 + 6, y + 18);
    y += 32;

    // ── Section 1: Sales Invoices ────────────────────────────────────────────
    addTitle('SECTION 1 — SALES INVOICES', 13, [30, 41, 59]); y += 2;
    addLine();
    const salesCols = ['Date', 'Customer', 'Total', 'Paid', 'Due'];
    const salesW    = [30, 65, 30, 30, 25];
    addRow(salesCols, salesW, true); addLine();
    props.ledger.filter(i => i.type === 'sale').forEach(item => {
        addRow([fmtDate(item.date), item.name, fmt(item.amount), fmt(item.paid_amount), fmt(item.due_amount)], salesW);
    });
    y += 5;

    // ── Section 2: Expenses ──────────────────────────────────────────────────
    if (y > 200) { doc.addPage(); y = 20; }
    addTitle('SECTION 2 — EXPENSES', 13, [30, 41, 59]); y += 2;
    addLine();
    const expCols = ['Date', 'Description', 'Amount'];
    const expW    = [30, 100, 40];
    addRow(expCols, expW, true); addLine();
    props.ledger.filter(i => i.type === 'expense').forEach(item => {
        addRow([fmtDate(item.date), item.name, fmt(item.amount)], expW);
    });
    y += 5;

    // ── Footer Summary ───────────────────────────────────────────────────────
    if (y > 220) { doc.addPage(); y = 20; }
    doc.setFillColor(30, 41, 59);
    doc.rect(14, y, W - 28, 28, 'F');
    doc.setFontSize(9); doc.setTextColor(148, 163, 184); doc.setFont('helvetica', 'bold');
    doc.text('FINANCIAL SUMMARY', 20, y + 8);
    doc.setFontSize(10); doc.setTextColor(255,255,255);
    doc.text(`Total Sales: ${fmt(props.summary.total_sales)}`, 20, y + 16);
    doc.text(`Total Expenses: ${fmt(props.summary.total_expenses)}`, 20, y + 23);
    doc.setTextColor(16, 185, 129);
    const net = props.summary.total_sales - props.summary.total_expenses;
    doc.text(`Net: ${fmt(net)}`, 130, y + 20);

    doc.save(`financial-report-${props.summary.start_date}.pdf`);
};
</script>

<template>
    <Head title="Financial Reports" />

    <div class="min-h-screen bg-[#f8fafc] pb-20 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="py-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Financial Reports</h1>
                <p class="mt-1 text-slate-500 font-medium">{{ summary.period_label }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Quick filters -->
                <div class="flex bg-white border border-slate-200 rounded-2xl p-1 shadow-sm gap-1">
                    <button @click="applyFilter('week')"
                        class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                        :class="activeFilter === 'week' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                    >Week</button>
                    <button @click="applyFilter('month')"
                        class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                        :class="activeFilter === 'month' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                    >Month</button>
                </div>

                <!-- Date range -->
                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">From</span>
                    <input type="date" v-model="dateFrom" class="text-xs font-bold text-slate-600 outline-none bg-transparent cursor-pointer" />
                </div>
                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">To</span>
                    <input type="date" v-model="dateTo" class="text-xs font-bold text-slate-600 outline-none bg-transparent cursor-pointer" />
                </div>
                <button @click="applyDateRange"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95"
                >Apply</button>

                <!-- Export PDF -->
                <button @click="exportPDF"
                    class="flex items-center gap-2 px-6 py-2.5 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl hover:bg-slate-800 transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    Download PDF
                </button>
            </div>
        </div>

        <!-- KPI Cards — only Total Sales + Total Expenses -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-12">
            <!-- Total Sales -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-3xl">trending_up</span>
                        </div>
                        <span class="px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest">Inflow</span>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Sales</p>
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tighter">{{ fmt(summary.total_sales) }}</h2>
                </div>
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-all duration-700"></div>
            </div>

            <!-- Total Expenses -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600">
                            <span class="material-symbols-outlined text-3xl">trending_down</span>
                        </div>
                        <span class="px-4 py-1.5 rounded-full bg-rose-50 text-rose-600 text-[10px] font-black uppercase tracking-widest">Outflow</span>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Expenses</p>
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tighter">{{ fmt(summary.total_expenses) }}</h2>
                </div>
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-rose-500/5 rounded-full blur-3xl group-hover:bg-rose-500/10 transition-all duration-700"></div>
            </div>
        </div>

        <!-- Cash Flow Chart -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 p-10 mb-12">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 mb-8">
                <div>
                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Cash Flow Analysis</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">Last 6 months overview</p>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Inflow (Collections)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Outflow (Expenses)</span>
                    </div>
                </div>
            </div>
            <div class="relative h-72">
                <canvas ref="chartCanvas"></canvas>
            </div>
        </div>

        <!-- Transaction Ledger Table -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="px-10 py-7 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Transaction Ledger</h3>
                    <p class="text-sm text-slate-400 font-medium mt-0.5">{{ ledger.length }} total entries</p>
                </div>
                <button @click="exportPDF"
                    class="flex items-center gap-2 px-5 py-3 border border-slate-200 rounded-2xl text-xs font-black text-slate-600 hover:bg-slate-50 transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-lg">print</span>
                    Export This View
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <th class="py-5 px-8">Date</th>
                            <th class="py-5 px-8">Transaction Name</th>
                            <th class="py-5 px-8">Type</th>
                            <th class="py-5 px-8">Total</th>
                            <th class="py-5 px-8">Paid</th>
                            <th class="py-5 px-8 text-right">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in paginatedLedger" :key="item.id + item.type"
                            class="group hover:bg-slate-50/50 transition-colors"
                        >
                            <td class="py-5 px-8 text-sm font-bold text-slate-900 whitespace-nowrap">{{ fmtDate(item.date) }}</td>
                            <td class="py-5 px-8">
                                <div class="text-sm font-bold text-slate-900">{{ item.name }}</div>
                            </td>
                            <td class="py-5 px-8">
                                <span
                                    class="inline-block px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                    :class="item.type === 'sale'
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-rose-50 text-rose-600'"
                                >{{ item.type }}</span>
                            </td>
                            <td class="py-5 px-8 font-black text-slate-900 whitespace-nowrap">{{ fmt(item.amount) }}</td>
                            <td class="py-5 px-8 font-bold text-emerald-600 whitespace-nowrap">{{ fmt(item.paid_amount) }}</td>
                            <td class="py-5 px-8 text-right font-bold whitespace-nowrap"
                                :class="item.due_amount > 0 ? 'text-rose-600' : 'text-slate-300'"
                            >{{ fmt(item.due_amount) }}</td>
                        </tr>
                        <tr v-if="ledger.length === 0">
                            <td colspan="6" class="py-20 text-center italic text-slate-400 font-bold">No transactions in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="ledger.length > 5" class="flex items-center justify-between px-8 py-4 border-t border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-slate-400">Rows per page:</span>
                    <select v-model.number="ledgerPerPage"
                        class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer"
                    >
                        <option v-for="s in PAGE_SIZES" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <span class="text-xs text-slate-400">{{ ledger.length }} total</span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="ledgerPage = Math.max(1, ledgerPage - 1)" :disabled="ledgerPage === 1"
                        class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all"
                    >&lsaquo;</button>
                    <button v-for="p in pageRange(ledgerPage, totalPages)" :key="p" @click="ledgerPage = p"
                        class="w-8 h-8 rounded-xl text-xs font-black transition-all"
                        :class="p === ledgerPage ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-500 hover:bg-slate-100'"
                    >{{ p }}</button>
                    <button @click="ledgerPage = Math.min(totalPages, ledgerPage + 1)" :disabled="ledgerPage === totalPages"
                        class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all"
                    >&rsaquo;</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tracking-tighter { letter-spacing: -0.05em; }
</style>
