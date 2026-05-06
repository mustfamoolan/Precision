<script setup>
import { ref, computed, watch } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { jsPDF } from 'jspdf';

defineOptions({ layout: MainLayout });

const props = defineProps({
    sales: Object,
    summary: Object,
    filters: Object,
    banks: Array,
    customers: {
        type: Array,
        default: () => []
    },
    inventory: {
        type: Array,
        default: () => []
    }
});

const showAddModal = ref(false);
const search = ref(props.filters.search || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedStatus = ref(props.filters.status || 'all');

const showPaymentModal = ref(false);
const paymentSale = ref(null);

const showHistoryModal = ref(false);
const historySale = ref(null);

const showItemsModal = ref(false);
const itemsSale = ref(null);

const editingSale = ref(null);

const form = useForm({
    date: new Date().toISOString().substr(0, 10),
    invoice_number: '',
    customer_name: '',
    amount: '',
    type: 'export',
    items_count: 1,
    paid_amount: '',
    container_number: '',
    shipping_status: 'On Board',
    bank_id: '',
    items: [],
    customer_address: '',
    subtotal: 0,
    vat: 0,
});

const paymentForm = useForm({
    payment_amount: '',
    payment_date: new Date().toISOString().substr(0, 10),
    bank_id: '',
});

const openPaymentModal = (sale) => {
    paymentSale.value = sale;
    paymentForm.payment_amount = sale.due_amount > 0 ? sale.due_amount : '';
    paymentForm.payment_date = new Date().toISOString().substr(0, 10);
    paymentForm.bank_id = sale.bank_id || '';
    showPaymentModal.value = true;
};

const openHistoryModal = (sale) => {
    historySale.value = sale;
    showHistoryModal.value = true;
};

const openItemsModal = (sale) => {
    itemsSale.value = sale;
    showItemsModal.value = true;
};

const openEditModal = (sale) => {
    editingSale.value = sale;
    form.date = sale.date;
    form.invoice_number = sale.invoice_number ? sale.invoice_number.replace('EXP-', '').replace('INV-', '') : '';
    form.customer_name = sale.customer_name;
    form.amount = sale.amount;
    form.type = 'export';
    form.items_count = sale.items_count;
    form.paid_amount = sale.paid_amount;
    form.container_number = sale.container_number ? sale.container_number.replace('CN-', '') : '';
    form.shipping_status = sale.shipping_status;
    form.bank_id = sale.bank_id || '';
    form.items = sale.items || [];
    form.customer_address = sale.customer_address || '';
    showAddModal.value = true;
};

const addItem = () => {
    form.items.push({ inventory_id: '', name: '', quantity: 1, rate: 0 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onInventorySelect = (index, invId) => {
    const inv = props.inventory.find(i => i.id == invId);
    if (inv) {
        form.items[index].name = inv.name;
        form.items[index].rate = inv.selling_price || 0;
    }
};

const openAddModal = () => {
    editingSale.value = null;
    form.reset();
    form.type = 'export';
    form.date = new Date().toISOString().substr(0, 10);
    showAddModal.value = true;
};

const submitPayment = () => {
    paymentForm.post(`/sales/${paymentSale.value.id}/payments`, {
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        }
    });
};

const confirmDelete = (id) => {
    if (confirm('Are you sure you want to delete this invoice?')) {
        router.delete(`/sales/${id}`);
    }
};

// Auto-fill address when customer is selected
watch(() => form.customer_name, (newName) => {
    const customer = props.customers.find(c => c.name === newName);
    if (customer) {
        form.customer_address = customer.address || '';
    }
});

const submit = () => {
    // Add prefix if not already there
    if (form.invoice_number && !form.invoice_number.startsWith('EXP-')) {
        form.invoice_number = 'EXP-' + form.invoice_number;
    }

    if (editingSale.value) {
        form.put(`/sales/${editingSale.value.id}`, {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/sales', {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    }
};

const handleSearch = () => {
    router.get('/sales', { 
        search: search.value,
        type: 'export',
        start_date: startDate.value,
        end_date: endDate.value,
        status: selectedStatus.value,
    }, { preserveState: true, preserveScroll: true });
};

watch(selectedStatus, () => handleSearch());

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value || 0);
};

const shippingStatuses = ['On Board', 'In Transit', 'Delivered'];
const statuses = [{label: 'All Status', value: 'all'}, {label: 'Paid', value: 'paid'}, {label: 'Partial', value: 'partial'}, {label: 'Pending', value: 'pending'}];

const exportInvoicePDF = (sale) => {
    const doc = new jsPDF();
    const W = doc.internal.pageSize.getWidth();
    const H = doc.internal.pageSize.getHeight();
    
    const renderArabic = (text, x, y, size, color = '#1e293b', align = 'left') => {
        if (!text) return;
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Scale for high resolution
        const scale = 8; 
        const fontSizePx = size * scale;
        ctx.font = `bold ${fontSizePx}px Arial, sans-serif`;
        
        const metrics = ctx.measureText(text);
        const textWidth = metrics.width;
        
        canvas.width = textWidth + (10 * scale);
        canvas.height = fontSizePx * 2;
        
        // Re-set font after resizing canvas
        ctx.fillStyle = color;
        ctx.font = `bold ${fontSizePx}px Arial, sans-serif`;
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 0, canvas.height / 2);
        
        const imgData = canvas.toDataURL('image/png');
        
        // Convert Pixels to MM (approx 1mm = 3.78px at 96dpi, but jsPDF uses 72dpi/pts)
        // A better way: the height in mm should be approx size * 0.3527
        const h = size * 0.45; // Adjusted height in mm
        const w = (textWidth / fontSizePx) * h;
        
        const finalX = align === 'right' ? (x - w) : x;
        doc.addImage(imgData, 'PNG', finalX, y - (h / 2), w, h);
    };

    const img = new Image();
    img.src = '/assets/images/invoice.png';
    
    img.onload = () => {
        doc.addImage(img, 'PNG', 0, 0, W, H);
        doc.setTextColor(30, 41, 59);
        
        // --- Header Section ---
        // Left: Bill To
        doc.setFontSize(9);
        doc.setFont('helvetica', 'bold');
        doc.text('Bill To', 20, 75);
        
        doc.setFontSize(11);
        const hasArabic = /[\u0600-\u06FF]/.test(sale.customer_name);
        if (hasArabic) {
            renderArabic(sale.customer_name, 20, 82, 12);
        } else {
            doc.text(sale.customer_name || 'Walking Customer', 20, 82);
        }
        
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        if (sale.customer_address) {
            const addrArabic = /[\u0600-\u06FF]/.test(sale.customer_address);
            if (addrArabic) {
                renderArabic(sale.customer_address, 20, 87, 9, '#64748b');
            } else {
                doc.text(sale.customer_address, 20, 87);
            }
        } else {
            doc.text('Al Ain, United Arab Emirates.', 20, 87);
        }
        doc.text('TRN: 100267536900003', 20, 92);

        // Right: Invoice Meta
        const metaX = W - 80;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.text('Invoice#', metaX, 75);
        doc.text('Invoice Date', metaX, 82);
        doc.text('Payment Terms', metaX, 89);
        
        doc.setFont('helvetica', 'normal');
        doc.text(sale.invoice_number, metaX + 30, 75);
        doc.text(sale.date, metaX + 30, 82);
        doc.text('Due on Receipt', metaX + 30, 89);

        // --- Table Section ---
        const tableY = 105;
        const rowHeight = 8;
        const totalRows = 12;

        // Header
        doc.setFillColor(248, 250, 252);
        doc.rect(20, tableY, W - 40, rowHeight, 'F');
        doc.setDrawColor(200, 200, 200);
        doc.rect(20, tableY, W - 40, rowHeight, 'D');

        doc.setFontSize(8);
        doc.setFont('helvetica', 'bold');
        doc.text('#', 22, tableY + 5.5);
        doc.text('Description', 30, tableY + 5.5);
        doc.text('Qty', W - 85, tableY + 5.5, { align: 'right' });
        doc.text('Rate', W - 65, tableY + 5.5, { align: 'right' });
        doc.text('VAT 5%', W - 45, tableY + 5.5, { align: 'right' });
        doc.text('Amount', W - 25, tableY + 5.5, { align: 'right' });

        doc.setFont('helvetica', 'normal');
        let currentY = tableY + rowHeight;
        let subtotal = 0;
        let totalVat = 0;

        for (let i = 0; i < totalRows; i++) {
            const item = sale.items && sale.items[i];
            doc.rect(20, currentY, W - 40, rowHeight, 'D');
            doc.line(28, currentY, 28, currentY + rowHeight);
            doc.line(W - 95, currentY, W - 95, currentY + rowHeight);
            doc.line(W - 75, currentY, W - 75, currentY + rowHeight);
            doc.line(W - 55, currentY, W - 55, currentY + rowHeight);
            doc.line(W - 35, currentY, W - 35, currentY + rowHeight);

            if (item) {
                const qty = parseFloat(item.quantity || 0);
                const rate = parseFloat(item.rate || 0);
                const lineTotal = qty * rate;
                const lineVat = lineTotal * 0.05;
                subtotal += lineTotal;
                totalVat += lineVat;

                doc.text(String(i + 1), 24, currentY + 5.5, { align: 'center' });
                
                const itemName = item.name || 'Product Item';
                if (/[\u0600-\u06FF]/.test(itemName)) {
                    renderArabic(itemName, 30, currentY + 5.5, 9);
                } else {
                    doc.text(itemName, 30, currentY + 5.5);
                }

                doc.text(qty.toFixed(2), W - 77, currentY + 5.5, { align: 'right' });
                doc.text(rate.toFixed(2), W - 57, currentY + 5.5, { align: 'right' });
                doc.text(lineVat.toFixed(2), W - 37, currentY + 5.5, { align: 'right' });
                doc.text((lineTotal + lineVat).toFixed(2), W - 22, currentY + 5.5, { align: 'right' });
            }
            currentY += rowHeight;
        }

        if (subtotal === 0 && sale.amount > 0) {
            subtotal = parseFloat(sale.amount) / 1.05;
            totalVat = subtotal * 0.05;
        }

        // --- Totals Section ---
        currentY += 5;
        const totalX = W - 70;
        doc.setFontSize(9);
        doc.text('Sub Total (VAT)', totalX, currentY);
        doc.text(totalVat.toFixed(2), W - 25, currentY, { align: 'right' });
        
        currentY += 7;
        doc.text('Total Taxable Amount', totalX, currentY);
        doc.text(subtotal.toFixed(2), W - 25, currentY, { align: 'right' });
        
        currentY += 7;
        doc.setFont('helvetica', 'bold');
        doc.text('Total', totalX, currentY);
        doc.text(`AED ${(subtotal + totalVat).toFixed(2)}`, W - 25, currentY, { align: 'right' });
        
        currentY += 7;
        doc.setDrawColor(200, 200, 200);
        doc.line(totalX, currentY - 2, W - 20, currentY - 2);
        doc.text('Balance Due', totalX, currentY + 3);
        doc.text(`AED ${parseFloat(sale.due_amount).toFixed(2)}`, W - 25, currentY + 3, { align: 'right' });

        // Footer Section
        const footerY = H - 60;
        doc.setFontSize(8);
        doc.setFont('helvetica', 'normal');
        doc.text('Customer Signature & Date _________________________', 20, footerY);
        doc.setFontSize(7);
        doc.text('Received the above material in good condition.', 65, footerY + 4);

        doc.save(`Invoice_${sale.invoice_number}.pdf`);
    };
    img.onerror = () => alert('Template image not found at /assets/images/invoice.png');
};



</script>

<template>
    <Head title="Export Invoices" />

    <div class="min-h-screen bg-[#f8fafc] pb-20 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="py-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Export Invoices</h1>
                <p class="mt-1 text-slate-500 font-medium">Manage export container sales</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Date range -->
                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">From</span>
                    <input type="date" v-model="startDate" class="text-xs font-bold text-slate-600 outline-none bg-transparent cursor-pointer" />
                </div>
                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">To</span>
                    <input type="date" v-model="endDate" class="text-xs font-bold text-slate-600 outline-none bg-transparent cursor-pointer" />
                </div>
                <button @click="handleSearch"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95"
                >Apply</button>
                <button @click="() => {startDate = ''; endDate = ''; handleSearch();}" 
                    class="px-3 py-2.5 bg-rose-50 text-rose-600 border border-rose-100 rounded-2xl hover:bg-rose-100 transition-all" title="Clear Dates"
                >
                    <span class="material-symbols-outlined text-sm block leading-none">close</span>
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6 mb-12">
            <!-- Total Export Sales -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600">
                            <span class="material-symbols-outlined text-3xl">public</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Sales</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_amount) }}</h2>
                </div>
            </div>

            <!-- Paid Amount -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-3xl">account_balance</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Paid Amount</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_paid) }}</h2>
                </div>
            </div>

            <!-- Pending Amount -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                            <span class="material-symbols-outlined text-3xl">schedule</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Pending</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_pending) }}</h2>
                </div>
            </div>

            <!-- Overdue Amount -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600">
                            <span class="material-symbols-outlined text-3xl">warning</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Overdue</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_overdue) }}</h2>
                </div>
            </div>

            <!-- Total Invoices -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <span class="material-symbols-outlined text-3xl">description</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Invoices</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ summary.total_count }}</h2>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden flex flex-col">
            <!-- Toolbar -->
            <div class="p-8 border-b border-slate-100 flex flex-wrap xl:flex-nowrap justify-between items-center gap-4">
                
                <div class="flex items-center gap-4 w-full xl:w-auto">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Export Records</h3>
                        <p class="text-sm text-slate-400 font-medium mt-0.5">{{ sales.total }} records found</p>
                    </div>

                    <div class="h-8 w-px bg-slate-200 mx-2 hidden sm:block"></div>

                    <select v-model="selectedStatus" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer w-40">
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                
                <div class="flex flex-1 max-w-md relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Search invoice, customer, container..." 
                        class="w-full pl-12 pr-4 py-3 bg-slate-50 rounded-2xl border border-slate-200 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-400 text-sm font-medium transition-all"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button @click="openAddModal()" class="flex items-center gap-2 px-6 py-3 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-2xl text-xs font-black uppercase tracking-widest shadow-sm hover:bg-indigo-100 transition-all active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add EXP Invoice
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-lg font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <th class="py-6 px-8">Invoice #</th>
                            <th class="py-6 px-8">Invoice Date</th>
                            <th class="py-6 px-8">Customer</th>
                            <th class="py-6 px-8">Container / Shipment</th>
                            <th class="py-6 px-8">Total Amount</th>
                            <th class="py-6 px-8">Paid Amount</th>
                            <th class="py-6 px-8 text-right">Remaining Due</th>
                            <th class="py-6 px-8">Status</th>
                            <th class="py-6 px-8 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="sale in sales.data" :key="sale.id" class="group hover:bg-slate-50/50 transition-colors">
                            <td class="py-6 px-8 text-xl font-bold text-slate-900 whitespace-nowrap">{{ sale.invoice_number }}</td>
                            <td class="py-6 px-8 text-xl font-bold text-slate-500 whitespace-nowrap">{{ sale.date }}</td>
                            <td class="py-6 px-8 text-xl font-medium text-slate-600 whitespace-nowrap">{{ sale.customer_name }}</td>
                            <td class="py-6 px-8 whitespace-nowrap">
                                <div class="flex items-center gap-2 text-xl font-bold text-slate-600" v-if="sale.container_number">
                                    <span class="material-symbols-outlined text-[18px] text-indigo-400">directions_boat</span>
                                    {{ sale.container_number }}
                                </div>
                                <span v-else class="text-base text-slate-400 uppercase tracking-widest">N/A</span>
                            </td>
                            <td class="py-6 px-8 text-2xl font-black text-slate-900 whitespace-nowrap">{{ formatCurrency(sale.amount).replace('AED', '') }}</td>
                            <td class="py-6 px-8 text-2xl font-bold text-emerald-600 whitespace-nowrap">{{ formatCurrency(sale.paid_amount).replace('AED', '') }}</td>
                            <td class="py-6 px-8 text-2xl font-bold text-right whitespace-nowrap" :class="sale.due_amount > 0 ? 'text-rose-600' : 'text-slate-300'">
                                {{ formatCurrency(sale.due_amount).replace('AED', '') }}
                            </td>
                            <td class="py-6 px-8 whitespace-nowrap">
                                <span class="text-base font-black uppercase tracking-widest px-4 py-2 rounded-md"
                                      :class="{
                                          'bg-emerald-50 text-emerald-600': sale.status === 'paid',
                                          'bg-orange-50 text-orange-600': sale.status === 'partial',
                                          'bg-rose-50 text-rose-600': sale.status === 'pending' || sale.status === 'unpaid'
                                      }">
                                    {{ sale.status }}
                                </span>
                            </td>
                            <td class="py-5 px-8 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="exportInvoicePDF(sale)" class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center hover:bg-slate-800 hover:scale-110 transition-all shadow-md" title="Download PDF">
                                        <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span>
                                    </button>
                                    <button @click="openItemsModal(sale)" class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 hover:bg-indigo-100 hover:scale-110 transition-all shadow-sm" title="View Items">
                                        <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                                    </button>
                                    <button @click="openHistoryModal(sale)" class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:scale-110 transition-all shadow-sm" title="Payment History">
                                        <span class="material-symbols-outlined text-[16px]">history</span>
                                    </button>
                                    <button @click="openPaymentModal(sale)" class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 hover:bg-emerald-100 hover:scale-110 transition-all shadow-sm" title="Record Payment">
                                        <span class="material-symbols-outlined text-[16px]">payments</span>
                                    </button>
                                    <button @click="openEditModal(sale)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-indigo-500 hover:border-indigo-200 hover:bg-indigo-50 transition-all shadow-sm" title="Edit">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </button>
                                    <button @click="confirmDelete(sale.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all shadow-sm" title="Delete">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="sales.data.length === 0">
                            <td colspan="9" class="py-20 text-center text-slate-400 italic text-sm">
                                <span class="material-symbols-outlined text-4xl block mb-2 opacity-50">search_off</span>
                                No export invoices found for the selected filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-slate-100">
                <Pagination :links="sales.links" :meta="sales" />
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <SideModal :show="showAddModal" :title="editingSale ? 'Edit EXP Invoice' : 'Add Export Invoice'" @close="showAddModal = false">
            <form @submit.prevent="submit" class="space-y-5 p-2">
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Date" :error="form.errors.date" required>
                        <TextInput v-model="form.date" type="date" />
                    </FormField>
                    
                    <FormField label="Invoice Serial #" :error="form.errors.invoice_number" required>
                        <TextInput 
                            v-model="form.invoice_number" 
                            prefix="EXP-" 
                            placeholder="Enter number (e.g. 101)" 
                        />
                    </FormField>
                </div>

                <FormField label="Customer Name" :error="form.errors.customer_name" required>
                    <SelectInput 
                        v-model="form.customer_name" 
                        :options="customers.map(c => ({label: c.name, value: c.name}))" 
                        placeholder="Select Customer..." 
                    />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Container / Shipment # (Optional)" :error="form.errors.container_number">
                        <TextInput v-model="form.container_number" prefix="CN-" placeholder="123456" />
                    </FormField>
                    <FormField label="Shipping Status" :error="form.errors.shipping_status" required>
                        <SelectInput v-model="form.shipping_status" :options="shippingStatuses.map(s => ({label: s, value: s}))" />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Total Amount (AED)" :error="form.errors.amount" required>
                        <TextInput v-model="form.amount" type="number" step="0.01" prefix="AED" placeholder="0.00" />
                    </FormField>
                    <FormField label="Paid Amount (AED)" :error="form.errors.paid_amount">
                        <TextInput v-model="form.paid_amount" type="number" step="0.01" prefix="AED" placeholder="0.00" />
                    </FormField>
                </div>

                <FormField label="Bank/Account (Optional)" :error="form.errors.bank_id">
                    <SelectInput 
                        v-model="form.bank_id" 
                        :options="[{label: 'None / Cash', value: ''}, ...banks.map(b => ({ label: b.name, value: b.id }))]" 
                    />
                </FormField>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex justify-between">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase">Calculated Total (Inc. 5% VAT)</p>
                        <p class="text-xl font-black text-slate-900">{{ formatCurrency(form.items.reduce((acc, i) => acc + (i.quantity * i.rate * 1.05), 0)) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Subtotal</p>
                        <p class="text-sm font-bold text-slate-600">{{ formatCurrency(form.items.reduce((acc, i) => acc + (i.quantity * i.rate), 0)) }}</p>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="border-t border-slate-100 pt-5 mt-5">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Included Items (Optional)</h4>
                        <button type="button" @click="addItem" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">+ Add Item</button>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(item, index) in form.items" :key="index" class="flex flex-wrap gap-3 items-end bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <div class="flex-1 min-w-[150px]">
                                <label class="text-[9px] font-black text-slate-400 uppercase mb-1 block">Product</label>
                                <SelectInput 
                                    v-model="item.inventory_id" 
                                    :options="inventory.map(i => ({ label: `${i.name} (${i.sku})`, value: i.id }))"
                                    @update:modelValue="(val) => onInventorySelect(index, val)"
                                    placeholder="Choose..."
                                />
                            </div>
                            <div class="w-16">
                                <label class="text-[9px] font-black text-slate-400 uppercase mb-1 block">Qty</label>
                                <TextInput v-model="item.quantity" type="number" min="1" />
                            </div>
                            <div class="w-24">
                                <label class="text-[9px] font-black text-slate-400 uppercase mb-1 block">Rate</label>
                                <TextInput v-model="item.rate" type="number" step="0.01" />
                            </div>
                            <div class="w-20 text-right pr-2">
                                <label class="text-[9px] font-black text-slate-400 uppercase mb-1 block">Total</label>
                                <p class="text-xs font-bold py-2">{{ formatCurrency(item.quantity * item.rate).replace('AED', '') }}</p>
                            </div>
                            <button type="button" @click="removeItem(index)" class="mb-2 text-rose-500 hover:text-rose-700">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                        <div v-if="form.items.length === 0" class="text-center py-4 text-[10px] text-slate-400 italic">No items added. Click + Add Item to include warehouse stock on the invoice.</div>
                    </div>
                </div>

                <div class="pt-6 flex justify-end gap-3 border-t border-slate-100 mt-6">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">
                        {{ editingSale ? 'Save Changes' : 'Create EXP Invoice' }}
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>

        <!-- Add Payment Modal -->
        <SideModal :show="showPaymentModal" title="Record Payment" @close="showPaymentModal = false">
            <form @submit.prevent="submitPayment" class="space-y-5 p-2">
                <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl mb-4">
                    <p class="text-xs font-bold text-indigo-800 uppercase tracking-widest mb-1">Invoice Info</p>
                    <p class="text-lg font-black text-indigo-900">{{ paymentSale?.invoice_number }}</p>
                    <p class="text-sm font-medium text-indigo-700 mt-1">Remaining Due: {{ formatCurrency(paymentSale?.due_amount) }}</p>
                </div>

                <FormField label="Payment Date" :error="paymentForm.errors.payment_date" required>
                    <TextInput v-model="paymentForm.payment_date" type="date" />
                </FormField>

                <FormField label="Payment Amount (AED)" :error="paymentForm.errors.payment_amount" required>
                    <TextInput v-model="paymentForm.payment_amount" type="number" step="0.01" prefix="AED" placeholder="0.00" />
                </FormField>

                <FormField label="Deposit to Account" :error="paymentForm.errors.bank_id" required>
                    <SelectInput 
                        v-model="paymentForm.bank_id" 
                        :options="banks.map(b => ({ label: b.name, value: b.id }))" 
                        placeholder="Select Bank/Cash Account..."
                    />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t border-slate-100 mt-6">
                    <SecondaryButton @click="showPaymentModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="paymentForm.processing" :disabled="paymentForm.processing">
                        Confirm Payment
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>

        <!-- Payment History Modal -->
        <SideModal :show="showHistoryModal" title="Payment History" @close="showHistoryModal = false">
            <div class="space-y-6">
                <div class="p-6 bg-slate-50 border border-slate-200 rounded-[2rem]">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Invoice Timeline</h4>
                    
                    <div v-if="!historySale?.payments?.length" class="text-center py-10 text-slate-400 italic">
                        No payments recorded yet.
                    </div>

                    <div v-else class="relative space-y-8">
                        <!-- Vertical Line -->
                        <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-slate-200"></div>

                        <div v-for="payment in historySale.payments" :key="payment.id" class="relative pl-12">
                            <!-- Dot -->
                            <div class="absolute left-[13px] top-1.5 w-2.5 h-2.5 rounded-full bg-indigo-600 border-2 border-white shadow-sm shadow-indigo-200"></div>
                            
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-black text-slate-900">{{ formatCurrency(payment.amount) }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ payment.date }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs font-bold text-indigo-600">
                                    <span class="material-symbols-outlined text-[14px]">account_balance</span>
                                    {{ payment.bank?.name || 'Cash Account' }}
                                </div>
                                <p v-if="payment.note" class="text-[11px] text-slate-500 mt-1 italic">{{ payment.note }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-5 bg-emerald-50 border border-emerald-100 rounded-2xl">
                        <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Total Paid</p>
                        <p class="text-xl font-black text-emerald-900">{{ formatCurrency(historySale?.paid_amount) }}</p>
                    </div>
                    <div class="p-5 bg-rose-50 border border-rose-100 rounded-2xl">
                        <p class="text-[9px] font-black text-rose-600 uppercase tracking-widest mb-1">Balance Due</p>
                        <p class="text-xl font-black text-rose-900">{{ formatCurrency(historySale?.due_amount) }}</p>
                    </div>
                </div>
            </div>
            <template #footer>
                <PrimaryButton @click="showHistoryModal = false" class="w-full">Close History</PrimaryButton>
            </template>
        </SideModal>

        <!-- View Items Modal -->
        <SideModal :show="showItemsModal" title="Invoice Items" @close="showItemsModal = false">
            <div class="space-y-6">
                <div class="p-6 bg-slate-50 border border-slate-200 rounded-[2rem]">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">List of Items</h4>
                    
                    <div v-if="!itemsSale?.items?.length" class="text-center py-10 text-slate-400 italic">
                        No items recorded for this invoice.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(item, index) in itemsSale.items" :key="index" class="relative group">
                            <!-- Background Decoration -->
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-3xl opacity-10 group-hover:opacity-20 transition duration-300"></div>
                            
                            <div class="relative flex justify-between items-center p-5 bg-white rounded-3xl border border-slate-100 shadow-sm transition-all hover:shadow-md">
                                <div class="flex items-center gap-5">
                                    <!-- Icon with Gradient -->
                                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-100">
                                        <span class="material-symbols-outlined text-2xl">package_2</span>
                                    </div>
                                    
                                    <div>
                                        <h5 class="text-lg font-black text-slate-900 leading-tight">{{ item.name || 'Unknown Product' }}</h5>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[9px] font-bold uppercase tracking-widest">
                                                ID: #{{ item.inventory_id }}
                                            </span>
                                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                            <p class="text-[11px] font-bold text-indigo-600 uppercase tracking-widest">Qty: {{ item.quantity }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Prominent Quantity Badge -->
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-2xl font-black text-slate-900 leading-none">{{ item.quantity }}</span>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <PrimaryButton @click="showItemsModal = false" class="w-full">Close</PrimaryButton>
            </template>
        </SideModal>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
.tracking-tighter { letter-spacing: -0.05em; }
</style>
