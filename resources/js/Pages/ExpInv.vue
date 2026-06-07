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
import TextArea from '@/Components/TextArea.vue';
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
const selectedBankId = ref(props.filters.bank_id || 'all');

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
    amount: 0,
    type: 'export',
    items_count: 1,
    paid_amount: 0,
    container_number: '',
    shipping_status: 'On Board',
    bank_id: '',
    items: [],
    customer_address: '',
    subtotal: 0,
    vat: 0,
    has_tax: true,
    currency: 'AED',
    trn: '100267536900003',
    notes: '',
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
    const items = (sale.items || []).map(item => {
        if (!item.brand_name && item.inventory_id) {
            const inv = props.inventory.find(i => i.id == item.inventory_id);
            if (inv && inv.brand) {
                return { ...item, brand_name: inv.brand.name };
            }
        }
        return item;
    });
    itemsSale.value = { ...sale, items };
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
    form.items = (sale.items || []).map(item => {
        if (!item.brand_name && item.inventory_id) {
            const inv = props.inventory.find(i => i.id == item.inventory_id);
            if (inv && inv.brand) {
                return { ...item, brand_name: inv.brand.name };
            }
        }
        return item;
    });
    form.customer_address = sale.customer_address || '';
    form.has_tax = sale.has_tax !== undefined ? !!sale.has_tax : true;
    form.currency = sale.currency || 'AED';
    form.trn = sale.trn || '100267536900003';
    form.notes = sale.notes || '';
    showAddModal.value = true;
};

const addItem = () => {
    form.items.push({ inventory_id: '', name: '', quantity: 1, rate: 0, currency: form.currency || 'AED' });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onInventorySelect = (index, invId) => {
    const inv = props.inventory.find(i => i.id == invId);
    if (inv) {
        form.items[index].name = inv.name;
        form.items[index].brand_name = inv.brand ? inv.brand.name : '';
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

// Auto-calculate Total Amount
watch(() => [form.items, form.has_tax], () => {
    let subtotal = form.items.reduce((acc, item) => acc + (parseFloat(item.quantity || 0) * parseFloat(item.rate || 0)), 0);
    form.subtotal = subtotal;
    
    if (form.has_tax) {
        form.vat = subtotal * 0.05;
        form.amount = subtotal + form.vat;
    } else {
        form.vat = 0;
        form.amount = subtotal;
    }
}, { deep: true });

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
        bank_id: selectedBankId.value,
    }, { preserveState: true, preserveScroll: true });
};

watch(selectedStatus, () => handleSearch());
watch(selectedBankId, () => handleSearch());

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value || 0);
};

const shippingStatuses = ['On Board', 'In Transit', 'Delivered'];
const statuses = [{label: 'All Status', value: 'all'}, {label: 'Paid', value: 'paid'}, {label: 'Partial', value: 'partial'}, {label: 'Pending', value: 'pending'}];

const exportInvoicePDF = async (sale) => {
    const items = (sale.items || []).map(item => {
        if (!item.brand_name && item.inventory_id) {
            const inv = props.inventory.find(i => i.id == item.inventory_id);
            if (inv && inv.brand) {
                return { ...item, brand_name: inv.brand.name };
            }
        }
        return item;
    });
    const loadImgBase64 = (src) => new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.src = src + '?v=' + new Date().getTime(); // Bust cache to ensure fresh load
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);
            resolve({
                data: canvas.toDataURL('image/png'),
                width: img.width,
                height: img.height
            });
        };
        img.onerror = () => resolve(null);
    });

    const [logoData, watermarkData] = await Promise.all([
        loadImgBase64('/assets/images/logo.png'),
        loadImgBase64('/assets/images/logoblack.png')
    ]);

    const doc = new jsPDF();
    const W = doc.internal.pageSize.getWidth();
    const H = doc.internal.pageSize.getHeight();
    
    const renderArabic = (text, x, y, size, color = '#1e293b', align = 'left', isBold = true) => {
        if (!text) return;
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        const scale = 8; 
        const fontSizePx = size * scale;
        ctx.font = `${isBold ? 'bold' : 'normal'} ${fontSizePx}px Arial, sans-serif`;
        
        const metrics = ctx.measureText(text);
        const textWidth = metrics.width;
        
        canvas.width = textWidth + (10 * scale);
        canvas.height = fontSizePx * 2;
        
        ctx.fillStyle = color;
        ctx.font = `${isBold ? 'bold' : 'normal'} ${fontSizePx}px Arial, sans-serif`;
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 0, canvas.height / 2);
        
        const imgData = canvas.toDataURL('image/png');
        const h = size * 0.45; 
        const w = (textWidth / fontSizePx) * h;
        
        const finalX = align === 'right' ? (x - w) : x;
        doc.addImage(imgData, 'PNG', finalX, y - (h / 2), w, h, undefined, 'FAST');
    };

    // Colors
    const colorOrange = [234, 88, 12];
    const colorDark = [30, 41, 59];
    const colorGray = [100, 116, 139];
    const colorLightGray = [148, 163, 184];
    const colorBorder = [226, 232, 240];

    // Helper functions for colors
    const setTextColor = (color) => doc.setTextColor(color[0], color[1], color[2]);
    const setDrawColor = (color) => doc.setDrawColor(color[0], color[1], color[2]);

    const isTaxEnabled = sale.has_tax === true || sale.has_tax === 1 || sale.has_tax === '1';

    // 1. Top Header (Outside Box)
    // Left: Company Info
    let logoW = 0;
    if (logoData) {
        let h = 16;
        let w = (h * logoData.width) / logoData.height;
        logoW = w;
        doc.addImage(logoData.data, 'PNG', 15, 17, w, h, undefined, 'FAST');
    }
    const leftTextX = logoData ? 15 + logoW + 4 : 15;

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(20);
    setTextColor(colorOrange);
    doc.text('AL SHAMLY TRADING', leftTextX, 20);
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text('www.alshamly.ae', leftTextX, 26);
    doc.text('Inquiry@alshamly.ae', leftTextX, 31);
    doc.text('+971 4 228 6643', leftTextX, 36);

    // Right: Business Address
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text('P.O BOX 261831 JEBEL ALI DUBAI', W - 15, 25, { align: 'right' });
    doc.text('U.A.E', W - 15, 31, { align: 'right' });
    doc.text('TRN: 100267536900003', W - 15, 36, { align: 'right' });

    // 2. The Big Rounded Box
    const boxY = 45;
    const boxH = H - 75; // ends at H - 30
    setDrawColor(colorBorder);
    doc.setLineWidth(0.5);
    doc.roundedRect(15, boxY, W - 30, boxH, 3, 3, 'S');

    // Watermark
    if (watermarkData) {
        let wmH = 130;
        let wmW = (wmH * watermarkData.width) / watermarkData.height;
        try {
            doc.setGState(new doc.GState({opacity: 0.3}));
        } catch (e) {}
        doc.addImage(watermarkData.data, 'PNG', (W - wmW) / 2, (H - wmH) / 2, wmW, wmH, undefined, 'FAST');
        try {
            doc.setGState(new doc.GState({opacity: 1.0}));
        } catch (e) {}
    }

    // 3. Metadata Section
    const metaY = boxY + 15;
    
    // Col 1: Billed To
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text('Billed to', 20, metaY);
    
    const hasArabic = /[\u0600-\u06FF]/.test(sale.customer_name);
    if (hasArabic) {
        renderArabic(sale.customer_name, 20, metaY + 6, 10, '#1e293b', 'left', true);
    } else {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        setTextColor(colorDark);
        doc.text(sale.customer_name || 'Walking Customer', 20, metaY + 6);
    }

    if (sale.customer_address) {
        const addrArabic = /[\u0600-\u06FF]/.test(sale.customer_address);
        if (addrArabic) {
            renderArabic(sale.customer_address, 20, metaY + 11, 9, '#64748b', 'left', false);
        } else {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            setTextColor(colorGray);
            doc.text(sale.customer_address, 20, metaY + 11);
        }
    } else {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        setTextColor(colorGray);
        doc.text('United Arab Emirates', 20, metaY + 11);
    }

    if (isTaxEnabled && sale.trn) {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        setTextColor(colorGray);
        doc.text('TRN: ' + sale.trn, 20, metaY + 16);
    }

    // Subject (Under Billed To)
    const subjY = metaY + 30;
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text('Subject', 20, subjY);
    doc.setFont('helvetica', 'bold');
    setTextColor(colorDark);
    doc.text('Export Sale', 20, subjY + 6);

    // Col 2: Invoice Info
    const col2X = W / 2 - 20;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text('Invoice number', col2X, metaY);
    doc.setFont('helvetica', 'bold');
    setTextColor(colorDark);
    doc.text(sale.invoice_number, col2X, metaY + 6);

    doc.setFont('helvetica', 'normal');
    setTextColor(colorGray);
    doc.text('Reference', col2X, metaY + 14);
    doc.setFont('helvetica', 'bold');
    setTextColor(colorDark);
    doc.text(sale.container_number || 'N/A', col2X, metaY + 20);

    doc.setFont('helvetica', 'normal');
    setTextColor(colorGray);
    doc.text('Invoice date', col2X, subjY);
    doc.setFont('helvetica', 'bold');
    setTextColor(colorDark);
    doc.text(sale.date, col2X, subjY + 6);

    // Col 3: Amount & Due Date
    const col3X = W - 20;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text(`Invoice of (${sale.currency || 'AED'})`, col3X, metaY, { align: 'right' });
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(18);
    setTextColor(colorOrange);
    doc.text(`${parseFloat(sale.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}`, col3X, metaY + 8, { align: 'right' });

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text('Payment Terms', col3X, subjY, { align: 'right' });
    doc.setFont('helvetica', 'bold');
    setTextColor(colorDark);
    doc.text('Due on Receipt', col3X, subjY + 6, { align: 'right' });

    // 4. Table Header
    const tableHeaderY = subjY + 18;
    setDrawColor(colorBorder);
    doc.setLineWidth(0.2);
    doc.line(15, tableHeaderY, W - 15, tableHeaderY);
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    setTextColor(colorLightGray);
    // Table Headers
    const textY = tableHeaderY + 5;
    doc.text('#', 20, textY);
    doc.text('Description', 30, textY);
    doc.text('Qty', W - 85, textY, { align: 'right' });
    if (isTaxEnabled) {
        doc.text('Rate', W - 60, textY, { align: 'right' });
        doc.text('VAT 5%', W - 40, textY, { align: 'right' });
    } else {
        doc.text('Rate', W - 50, textY, { align: 'right' });
    }
    doc.text('Amount', W - 20, textY, { align: 'right' });

    const tableHeaderBottomY = tableHeaderY + 8;
    doc.line(15, tableHeaderBottomY, W - 15, tableHeaderBottomY);

    // 5. Table Rows
    let currentY = tableHeaderBottomY + 8;
    let subtotal = 0;
    let totalVat = 0;

    const itemsCount = items ? items.length : 0;
    const totalRows = Math.max(10, itemsCount);

    if (itemsCount === 0) {
        // Fallback if no items but has amount
        subtotal = parseFloat(sale.amount || 0);
        if (isTaxEnabled) {
            subtotal = subtotal / 1.05;
            totalVat = subtotal * 0.05;
        }
    }

    for (let i = 0; i < totalRows; i++) {
        if (i < itemsCount) {
            setTextColor(colorDark);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            doc.text(String(i + 1), 20, currentY);

            const item = items[i];
            const qty = parseFloat(item.quantity || 0);
            const rate = parseFloat(item.rate || 0);
            const lineTotal = qty * rate;
            const lineVat = isTaxEnabled ? (lineTotal * 0.05) : 0;
            subtotal += lineTotal;
            totalVat += lineVat;

            const itemName = item.name || 'Product Item';
            const brandStr = item.brand_name ? ` (${item.brand_name})` : '';
            const displayName = itemName + brandStr;
            if (/[\u0600-\u06FF]/.test(displayName)) {
                renderArabic(displayName, 30, currentY, 9, '#1e293b', 'left', true);
            } else {
                setTextColor(colorDark);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(9);
                doc.text(displayName, 30, currentY);
            }

            setTextColor(colorDark);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            doc.text(qty.toString(), W - 85, currentY, { align: 'right' });
            
            // Format numbers with commas
            const rateStr = rate.toLocaleString('en-US', {minimumFractionDigits: 2});
            const amtStr = (isTaxEnabled ? (lineTotal + lineVat) : lineTotal).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            if (isTaxEnabled) {
                const vatStr = lineVat.toLocaleString('en-US', {minimumFractionDigits: 2});
                doc.text(rateStr, W - 60, currentY, { align: 'right' });
                doc.text(vatStr, W - 40, currentY, { align: 'right' });
            } else {
                doc.text(rateStr, W - 50, currentY, { align: 'right' });
            }
            doc.text(amtStr, W - 20, currentY, { align: 'right' });
        }

        currentY += 8; // Spacing between rows

        // Draw a light separator line between rows
        if (i < totalRows - 1) {
            setDrawColor([241, 245, 249]); // Very light gray (slate-100)
            doc.setLineWidth(0.1);
            doc.line(15, currentY - 4, W - 15, currentY - 4);
        }
    }

    // 6. Totals Section
    currentY += 5;
    doc.line(15, currentY, W - 15, currentY);
    
    currentY += 8;
    const totalsLeftX = W / 2 + 10;
    const notesY = currentY;
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    setTextColor(colorDark);
    
    if (isTaxEnabled) {
        doc.text('Subtotal', totalsLeftX, currentY);
        doc.text(subtotal.toLocaleString('en-US', {minimumFractionDigits: 2}), W - 20, currentY, { align: 'right' });
        
        currentY += 7;
        doc.text('Tax (5%)', totalsLeftX, currentY);
        doc.text(totalVat.toLocaleString('en-US', {minimumFractionDigits: 2}), W - 20, currentY, { align: 'right' });
        currentY += 7;
    }
    
    currentY += 3;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    const finalTotal = isTaxEnabled ? (subtotal + totalVat) : subtotal;
    doc.text('Total', totalsLeftX, currentY);
    doc.text(finalTotal.toLocaleString('en-US', {minimumFractionDigits: 2}), W - 20, currentY, { align: 'right' });

    currentY += 8;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    setTextColor(colorGray);
    doc.text('Paid Amount', totalsLeftX, currentY);
    doc.text(parseFloat(sale.paid_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}), W - 20, currentY, { align: 'right' });

    currentY += 8;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    setTextColor(colorDark);
    doc.text('Balance Due', totalsLeftX, currentY);
    doc.text(parseFloat(sale.due_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}), W - 20, currentY, { align: 'right' });

    // Notes Section (Left side with Box Design - Updated to match invoice style)
    if (sale.notes) {
        const notesBoxW = (W / 2) - 25;
        const notesBoxH = 35;
        
        // Background & Border
        doc.setFillColor(252, 252, 253); 
        doc.roundedRect(18, notesY - 5, notesBoxW, notesBoxH, 2, 2, 'F');
        
        setDrawColor(colorBorder);
        doc.setLineWidth(0.1);
        doc.roundedRect(18, notesY - 5, notesBoxW, notesBoxH, 2, 2, 'S');

        const isArabic = /[\u0600-\u06FF]/.test(sale.notes);
        if (isArabic) {
            renderArabic('Notes', 22, notesY - 1, 9, '#1e293b', 'left', true);
            renderArabic(sale.notes, 22, notesY + 8, 9, '#334155', 'left', false);
        } else {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            setTextColor(colorDark);
            doc.text('Notes / Remarks', 22, notesY - 1);
            
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            setTextColor(colorDark);
            const splitNotes = doc.splitTextToSize(sale.notes, notesBoxW - 8);
            doc.text(splitNotes, 22, notesY + 5);
        }
    }

    // 7. Footer inside box
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    setTextColor(colorDark);
    doc.text('Thanks for the business.', 20, boxY + boxH - 10);

    // 8. Footer outside box
    const footerY = boxY + boxH + 10;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    setTextColor(colorGray);
    doc.text('Terms & Conditions', 15, footerY);
    doc.text('Received the above material in good condition. Customer Signature: _________________________', 15, footerY + 5);

    doc.save(`Invoice_${sale.invoice_number}.pdf`);
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

                    <select v-model="selectedBankId" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer w-48">
                        <option value="all">All Accounts</option>
                        <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
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

                <div class="flex items-center gap-2" v-if="$page.props.auth.user.role !== 'viewer'">
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
                                    <template v-if="$page.props.auth.user.role !== 'viewer'">
                                        <button @click="openPaymentModal(sale)" class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 hover:bg-emerald-100 hover:scale-110 transition-all shadow-sm" title="Record Payment">
                                            <span class="material-symbols-outlined text-[16px]">payments</span>
                                        </button>
                                        <button @click="openEditModal(sale)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-indigo-500 hover:border-indigo-200 hover:bg-indigo-50 transition-all shadow-sm" title="Edit">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        <button @click="confirmDelete(sale.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all shadow-sm" title="Delete">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </template>
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
                    <div class="flex items-center gap-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.has_tax" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ml-3 text-sm font-black text-slate-700 uppercase tracking-widest">Enable 5% VAT</span>
                        </label>
                    </div>
                    
                    <FormField label="Invoice Currency" :error="form.errors.currency">
                        <SelectInput v-model="form.currency" :options="[{label: 'AED - UAE Dirham', value: 'AED'}, {label: 'USD - US Dollar', value: 'USD'}, {label: 'IQD - Iraqi Dinar', value: 'IQD'}]" />
                    </FormField>
                </div>

                <div v-if="form.has_tax" class="animate-in fade-in slide-in-from-top duration-300">
                    <FormField label="Tax Registration Number (TRN)" :error="form.errors.trn">
                        <TextInput v-model="form.trn" placeholder="100267536900003" />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Container / Shipment # (Optional)" :error="form.errors.container_number">
                        <TextInput v-model="form.container_number" prefix="CN-" placeholder="123456" />
                    </FormField>
                    <FormField label="Shipping Status" :error="form.errors.shipping_status" required>
                        <SelectInput v-model="form.shipping_status" :options="shippingStatuses.map(s => ({label: s, value: s}))" />
                    </FormField>
                </div>

                <FormField label="Bank/Account (Optional)" :error="form.errors.bank_id">
                    <SelectInput 
                        v-model="form.bank_id" 
                        :options="[{label: 'None / Cash', value: ''}, ...banks.map(b => ({ label: b.name, value: b.id }))]" 
                    />
                </FormField>

                <FormField label="Notes / Remarks (Optional)" :error="form.errors.notes">
                    <TextArea v-model="form.notes" placeholder="Enter any additional notes..." rows="3" />
                </FormField>

                <!-- Items Section -->
                <div class="border-t border-slate-100 pt-5 mt-5">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Included Items (Optional)</h4>
                        <button type="button" @click="addItem" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">+ Add Item</button>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(item, index) in form.items" :key="index" class="flex flex-wrap gap-3 items-end bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-sm">
                            <div class="flex-1 min-w-[200px]">
                                <label class="text-[9px] font-black text-slate-400 uppercase mb-1 block">Product</label>
                                <SelectInput 
                                    v-model="item.inventory_id" 
                                    :options="inventory.map(i => ({ label: `${i.name}` + (i.sku ? ` (${i.sku})` : '') + (i.brand ? ` - ${i.brand.name}` : ''), value: i.id }))"
                                    @update:modelValue="(val) => onInventorySelect(index, val)"
                                    placeholder="Choose Product..."
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
                                <p class="text-xs font-black py-2 text-slate-900">{{ (item.quantity * item.rate).toFixed(2) }}</p>
                                <p v-if="form.has_tax" class="text-[8px] font-bold text-indigo-500 uppercase tracking-tighter mt-1">+5% VAT</p>
                            </div>
                            <button type="button" @click="removeItem(index)" class="mb-2 w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:bg-rose-50 transition-colors">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                        <div v-if="form.items.length === 0" class="text-center py-6 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200 text-[10px] text-slate-400 font-bold uppercase tracking-widest">No items added. Click + Add Item.</div>
                    </div>
                </div>

                <!-- Financial Summary Card (Light Theme) -->
                <div class="mt-8 p-6 bg-slate-50 rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden relative group">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:scale-110 transition-transform duration-500">
                        <span class="material-symbols-outlined text-8xl text-slate-900">payments</span>
                    </div>
                    
                    <div class="relative z-10 space-y-4">
                        <!-- Only show breakdown if tax is enabled -->
                        <div v-if="form.has_tax" class="flex justify-between items-center border-b border-slate-200 pb-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subtotal (Net)</p>
                                <p class="text-lg font-bold text-slate-900">{{ form.subtotal.toFixed(2) }} <span class="text-xs font-medium opacity-50">{{ form.currency }}</span></p>
                                <p class="text-[8px] font-bold text-indigo-600 uppercase mt-1">TRN: {{ form.trn || '100267536900003' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">VAT (5%)</p>
                                <p class="text-lg font-bold text-indigo-600">+ {{ form.vat.toFixed(2) }} <span class="text-xs font-medium opacity-50">{{ form.currency }}</span></p>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">{{ form.has_tax ? 'Grand Total (Inc. VAT)' : 'Total Amount' }}</p>
                                <p class="text-4xl font-black tracking-tighter" :class="form.has_tax ? 'text-slate-900' : 'text-indigo-600'">
                                    {{ form.amount.toFixed(2) }} <span class="text-sm font-bold opacity-40">{{ form.currency }}</span>
                                </p>
                            </div>
                            <div class="w-40">
                                <FormField label="Paid Amount" :error="form.errors.paid_amount" label-class="text-slate-500">
                                    <TextInput 
                                        v-model="form.paid_amount" 
                                        type="number" 
                                        step="0.01" 
                                        class="!bg-white !border-slate-200 !text-slate-900 !placeholder-slate-300" 
                                        placeholder="0.00" 
                                    />
                                </FormField>
                            </div>
                        </div>
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
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[9px] font-bold uppercase tracking-widest">
                                                ID: #{{ item.inventory_id }}
                                            </span>
                                            <span v-if="item.brand_name" class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold uppercase tracking-widest">
                                                Brand: {{ item.brand_name }}
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
