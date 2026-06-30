<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Badge from '@/Components/Badge.vue';
import Pagination from '@/Components/Pagination.vue';
import { Chart, registerables } from 'chart.js';
import axios from 'axios';
Chart.register(...registerables);

defineOptions({ layout: MainLayout });

const props = defineProps({
    summary: Object,
    cash_flow: Array,
    aging: Object,
    top_expenses: Array,
    ledger: Object,
    filters: Object,
});

// ─── Filters ──────────────────────────────────────────────────────────────────
const dateFrom = ref(props.filters?.start_date || props.summary?.start_date || '');
const dateTo   = ref(props.filters?.end_date   || props.summary?.end_date   || '');
const activeFilter = ref(props.filters?.filter || 'month');

// Sync local refs when props update
watch(() => props.summary, (newSummary) => {
    if (newSummary) {
        dateFrom.value = props.filters?.start_date || newSummary.start_date || '';
        dateTo.value = props.filters?.end_date || newSummary.end_date || '';
    }
}, { immediate: true });

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        activeFilter.value = newFilters.filter || 'month';
    }
}, { immediate: true });

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

// Ledger Pagination is now server-side

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
const isDownloadingPDF = ref(false);

const exportPDF = async () => {
    if (isDownloadingPDF.value) return;
    isDownloadingPDF.value = true;
    try {
        const { jsPDF } = await import('jspdf');
        
        // Fetch all transactions for the selected period
        const response = await axios.get('/reports-all', {
            params: {
                filter: activeFilter.value,
                start_date: dateFrom.value,
                end_date: dateTo.value
            }
        });
        
        const allLedger = response.data.ledger || [];
        const summaryData = response.data.summary || props.summary;

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

            // Split text for each column and find max height
            let rowLines = [];
            let maxLinesCount = 1;
            
            cols.forEach((col, i) => {
                // Give a 5mm right margin to ensure text doesn't touch the next column
                let lines = doc.splitTextToSize(String(col), widths[i] - 5);
                rowLines.push(lines);
                if (lines.length > maxLinesCount) {
                    maxLinesCount = lines.length;
                }
            });

            let rowHeight = maxLinesCount * 4; // Approx 4mm per line

            if (y + rowHeight > 270) { 
                doc.addPage(); 
                y = 20; 
            }

            let x = 14;
            rowLines.forEach((lines, i) => { 
                doc.text(lines, x, y); 
                x += widths[i]; 
            });
            
            y += Math.max(6, rowHeight + 2);
            
            // Add subtle row border
            doc.setDrawColor(235, 235, 235);
            doc.setLineWidth(0.2);
            doc.line(14, y - 2, W - 14, y - 2);
            y += 2;
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
        doc.text(`Period: ${summaryData.period_label}`, 14, 25);
        doc.text(`Generated: ${new Date().toLocaleDateString('en-AE')}`, W - 55, 25);
        y = 42;

        // ── Summary (Compact Text Instead of Cards) ───────────────────────────
        doc.setFontSize(8); doc.setTextColor(100, 116, 139); doc.setFont('helvetica', 'bold');
        doc.text('SALES (TOTAL)', 14, y + 5);
        doc.text('SALES (PAID)', 50, y + 5);
        doc.text('SALES (DUE)', 85, y + 5);
        doc.text('EXPENSES', 120, y + 5);
        
        doc.setFontSize(10); doc.setFont('helvetica', 'bold');
        doc.setTextColor(30, 41, 59); doc.text(fmt(summaryData.total_sales), 14, y + 11);
        doc.setTextColor(16, 185, 129); doc.text(fmt(summaryData.total_sales_paid), 50, y + 11);
        doc.setTextColor(217, 119, 6); doc.text(fmt(summaryData.total_sales_due), 85, y + 11);
        doc.setTextColor(239, 68, 68); doc.text(fmt(summaryData.total_expenses), 120, y + 11);
        
        y += 20;

        // ── Cash & Bank Balances (Compact) ──────────────────────────────────────
        if (summaryData.banks && summaryData.banks.length > 0) {
            // Section Title
            doc.setFontSize(8); doc.setTextColor(100, 116, 139); doc.setFont('helvetica', 'bold');
            doc.text('CASH & BANK BALANCES', 14, y);
            y += 7;
            
            let bx = 14;
            summaryData.banks.forEach(bank => {
                let nameStr = bank.name.toUpperCase();
                
                // Wrap to next line if we exceed 4 columns (14, 59, 104, 149)
                if (bx > 150) { 
                    bx = 14; 
                    y += 14; 
                }
                
                // Bank Name
                doc.setFontSize(8); doc.setTextColor(100, 116, 139); doc.setFont('helvetica', 'bold');
                doc.text(nameStr.length > 20 ? nameStr.substring(0, 20) + '...' : nameStr, bx, y);
                
                // Balance
                doc.setFontSize(10); doc.setTextColor(30, 41, 59); doc.setFont('helvetica', 'bold');
                doc.text(fmt(bank.balance), bx, y + 6);
                
                bx += 45;
            });
            y += 15;
        }

        // ── Section 1: Transaction Ledger ────────────────────────────────────────
        addTitle('TRANSACTION LEDGER', 13, [30, 41, 59]); y += 2;
        
        // Solid line under title
        doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.5);
        doc.line(14, y, W - 14, y); y += 5;

        const cols = ['Date', 'Description', 'Ref #', 'Total', 'Paid', 'Due', 'Bank'];
        const widths = [20, 45, 20, 24, 24, 24, 25]; // Total: 182
        addRow(cols, widths, true);
        
        allLedger.forEach(item => {
            let total = item.total > 0 ? fmt(item.total) : '-';
            let paid = item.paid > 0 ? fmt(item.paid) : '-';
            let due = item.due > 0 ? fmt(item.due) : '-';
            let bName = item.bank_name || '-';
            addRow([fmtDate(item.date), item.name, item.reference, total, paid, due, bName], widths);
        });
        y += 5;

        doc.save(`financial-report-${summaryData.start_date}.pdf`);
    } catch (error) {
        console.error('Failed to generate PDF:', error);
        alert('An error occurred while generating the PDF. Please try again.');
    } finally {
        isDownloadingPDF.value = false;
    }
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
                <button @click="exportPDF" :disabled="isDownloadingPDF"
                    class="flex items-center gap-2 px-6 py-2.5 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl hover:bg-slate-800 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isDownloadingPDF" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                    <span v-else class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    {{ isDownloadingPDF ? 'Generating...' : 'Download PDF' }}
                </button>
            </div>
        </div>

        <!-- KPI Cards — Total Sales, Paid, Unpaid (Due), Total Expenses -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- Total Sales (Overall) -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-3xl p-6 border border-white shadow-lg shadow-slate-200/40 transition-all hover:-translate-y-0.5 hover:shadow-xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <span class="material-symbols-outlined text-xl">monetization_on</span>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase tracking-widest">Sales</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total Sales</p>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ fmt(summary.total_sales) }}</h2>
                </div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-all duration-700"></div>
            </div>

            <!-- Sales Paid -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-3xl p-6 border border-white shadow-lg shadow-slate-200/40 transition-all hover:-translate-y-0.5 hover:shadow-xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-xl">payments</span>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase tracking-widest">Paid</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Paid Sales</p>
                    <h2 class="text-2xl font-black text-emerald-600 tracking-tight">{{ fmt(summary.total_sales_paid) }}</h2>
                </div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-all duration-700"></div>
            </div>

            <!-- Sales Unpaid (Due) -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-3xl p-6 border border-white shadow-lg shadow-slate-200/40 transition-all hover:-translate-y-0.5 hover:shadow-xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <span class="material-symbols-outlined text-xl">pending_actions</span>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-600 text-[9px] font-black uppercase tracking-widest">Unpaid</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Unpaid (Due)</p>
                    <h2 class="text-2xl font-black text-amber-600 tracking-tight">{{ fmt(summary.total_sales_due) }}</h2>
                </div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-all duration-700"></div>
            </div>

            <!-- Total Expenses -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-3xl p-6 border border-white shadow-lg shadow-slate-200/40 transition-all hover:-translate-y-0.5 hover:shadow-xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600">
                            <span class="material-symbols-outlined text-xl">shopping_cart</span>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-600 text-[9px] font-black uppercase tracking-widest">Expenses</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total Expenses</p>
                    <h2 class="text-2xl font-black text-rose-600 tracking-tight">{{ fmt(summary.total_expenses) }}</h2>
                </div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-all duration-700"></div>
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
                    <p class="text-sm text-slate-400 font-medium mt-0.5">{{ ledger.total }} total entries</p>
                </div>
                <button @click="exportPDF" :disabled="isDownloadingPDF"
                    class="flex items-center gap-2 px-5 py-3 border border-slate-200 rounded-2xl text-xs font-black text-slate-600 hover:bg-slate-50 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isDownloadingPDF" class="animate-spin w-4 h-4 border-2 border-slate-600 border-t-transparent rounded-full"></span>
                    <span v-else class="material-symbols-outlined text-lg">print</span>
                    {{ isDownloadingPDF ? 'Generating...' : 'Export This View' }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-lg font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <th class="py-6 px-8">Date</th>
                            <th class="py-6 px-8">Transaction Name</th>
                            <th class="py-6 px-8">Reference #</th>
                            <th class="py-6 px-8">Type</th>
                            <th class="py-6 px-8 text-right">Total</th>
                            <th class="py-6 px-8 text-right">Paid</th>
                            <th class="py-6 px-8 text-right">Due</th>
                            <th class="py-6 px-8">Bank</th>
                            <th class="py-6 px-8 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in ledger.data" :key="item.id + item.type"
                            class="group hover:bg-slate-50/50 transition-colors"
                        >
                            <td class="py-6 px-8 text-xl font-bold text-slate-900 whitespace-nowrap">{{ fmtDate(item.date) }}</td>
                            <td class="py-6 px-8">
                                <div class="text-xl font-bold text-slate-900">{{ item.name }}</div>
                            </td>
                            <td class="py-6 px-8 text-xl font-black text-indigo-600 whitespace-nowrap">{{ item.reference }}</td>
                            <td class="py-6 px-8">
                                <span
                                    class="inline-block px-4 py-1.5 rounded-full text-sm font-black uppercase tracking-widest"
                                    :class="item.type === 'sale' ? 'bg-blue-50 text-blue-600' : (item.type === 'payment' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600')"
                                >{{ item.type }}</span>
                            </td>
                            <td class="py-6 px-8 font-black text-slate-900 whitespace-nowrap text-2xl text-right">{{ item.total > 0 ? fmt(item.total) : '—' }}</td>
                            <td class="py-6 px-8 font-bold text-emerald-600 whitespace-nowrap text-2xl text-right">{{ item.paid > 0 ? fmt(item.paid) : '—' }}</td>
                            <td class="py-6 px-8 text-right font-bold whitespace-nowrap text-2xl"
                                :class="item.due > 0 ? 'text-rose-600' : 'text-slate-300'"
                            >{{ item.due > 0 ? fmt(item.due) : '—' }}</td>
                            <td class="py-6 px-8 text-lg font-bold text-slate-500 whitespace-nowrap">{{ item.bank_name || '—' }}</td>
                            <td class="py-6 px-8 text-center">
                                <span v-if="item.status" class="inline-block px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest"
                                      :class="item.status === 'paid' ? 'bg-emerald-500 text-white' : (item.status === 'partial' ? 'bg-amber-500 text-white' : (item.status === 'pending' ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600'))">
                                    {{ item.status }}
                                </span>
                                <span v-else>—</span>
                            </td>
                        </tr>
                        <tr v-if="ledger.data.length === 0">
                            <td colspan="7" class="py-20 text-center italic text-slate-400 font-bold">No transactions in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-10 py-7 border-t border-slate-100">
                <Pagination :links="ledger.links" :meta="ledger" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.tracking-tighter { letter-spacing: -0.05em; }
</style>
