<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    document: Object,
    invoice: Object,
    contract: Object,
    llm_json: Object,
});

const activeTab = ref('overview');

// Badge color helpers
const getCategoryColor = (cat) => {
    const colors = {
        invoice: 'bg-blue-100 text-blue-800',
        contract: 'bg-purple-100 text-purple-800',
        general: 'bg-gray-100 text-gray-800',
    };
    return colors[cat] || 'bg-gray-100 text-gray-800';
};

const getStatusColor = (stat) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        extracted: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    };
    return colors[stat] || 'bg-gray-100 text-gray-800';
};

const formatBytes = (bytes) => {
    if (!bytes) return 'N/A';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const formatCurrency = (amount, currency = 'USD') => {
    if (amount === null || amount === undefined) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};
</script>

<template>
    <Head :title="document.filename" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        :href="route('documents.index')"
                        class="mb-2 inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                        <svg
                            class="mr-1 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                        Back to Documents
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ document.filename }}
                    </h2>
                </div>
                <div class="flex gap-2">
                    <a
                        :href="route('documents.export.json', document.id)"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                        target="_blank"
                    >
                        <svg
                            class="-ml-0.5 mr-1.5 h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Export JSON
                    </a>
                    <Link
                        :href="route('documents.download', document.id)"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        <svg
                            class="-ml-0.5 mr-1.5 h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"
                            />
                        </svg>
                        Download Original
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Document Header -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="mb-4 flex items-center gap-3">
                                    <span
                                        :class="getCategoryColor(document.category)"
                                        class="inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize"
                                    >
                                        {{ document.category }}
                                    </span>
                                    <span
                                        :class="getStatusColor(document.status)"
                                        class="inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize"
                                    >
                                        {{ document.status }}
                                    </span>
                                </div>
                                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">File Size</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ formatBytes(document.size_bytes) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">MIME Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ document.mime_type }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ document.created_at }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div
                            v-if="document.error_message"
                            class="mt-4 rounded-md bg-red-50 p-4"
                        >
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-red-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        Extraction Error
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        {{ document.error_message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex" aria-label="Tabs">
                            <button
                                @click="activeTab = 'overview'"
                                :class="[
                                    activeTab === 'overview'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'w-1/4 border-b-2 px-1 py-4 text-center text-sm font-medium',
                                ]"
                            >
                                Overview
                            </button>
                            <button
                                v-if="invoice"
                                @click="activeTab = 'invoice'"
                                :class="[
                                    activeTab === 'invoice'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'w-1/4 border-b-2 px-1 py-4 text-center text-sm font-medium',
                                ]"
                            >
                                Invoice
                            </button>
                            <button
                                v-if="contract"
                                @click="activeTab = 'contract'"
                                :class="[
                                    activeTab === 'contract'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'w-1/4 border-b-2 px-1 py-4 text-center text-sm font-medium',
                                ]"
                            >
                                Contract
                            </button>
                            <button
                                @click="activeTab = 'json'"
                                :class="[
                                    activeTab === 'json'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'w-1/4 border-b-2 px-1 py-4 text-center text-sm font-medium',
                                ]"
                            >
                                Raw JSON
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Overview Tab -->
                        <div v-if="activeTab === 'overview'">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">
                                Document Information
                            </h3>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Filename</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ document.filename }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                                    <dd class="mt-1 text-sm capitalize text-gray-900">
                                        {{ document.category }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1 text-sm capitalize text-gray-900">
                                        {{ document.status }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Size</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ formatBytes(document.size_bytes) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">MIME Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ document.mime_type }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ document.created_at }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Updated At</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ document.updated_at }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Invoice Tab -->
                        <div v-if="activeTab === 'invoice' && invoice">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">
                                Invoice Details
                            </h3>

                            <!-- Invoice Header -->
                            <div class="mb-6 rounded-lg bg-gray-50 p-4">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">
                                            Vendor Name
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ invoice.vendor_name || 'N/A' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">
                                            Invoice Number
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ invoice.invoice_number || 'N/A' }}
                                        </dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">
                                            Vendor Address
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ invoice.vendor_address || 'N/A' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">
                                            Invoice Date
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ invoice.invoice_date || 'N/A' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ invoice.due_date || 'N/A' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Line Items -->
                            <h4 class="mb-3 text-base font-medium text-gray-900">Line Items</h4>
                            <div class="overflow-hidden rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                            >
                                                Description
                                            </th>
                                            <th
                                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                                            >
                                                Quantity
                                            </th>
                                            <th
                                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                                            >
                                                Unit Price
                                            </th>
                                            <th
                                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                                            >
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="line in invoice.lines" :key="line.id">
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ line.description || 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-900">
                                                {{ line.quantity }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-900">
                                                {{ formatCurrency(line.unit_price, invoice.currency) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-900">
                                                {{ formatCurrency(line.line_total, invoice.currency) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totals -->
                            <div class="mt-6 rounded-lg bg-gray-50 p-4">
                                <dl class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <dt class="font-medium text-gray-500">Subtotal</dt>
                                        <dd class="text-gray-900">
                                            {{ formatCurrency(invoice.subtotal, invoice.currency) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <dt class="font-medium text-gray-500">Tax</dt>
                                        <dd class="text-gray-900">
                                            {{ formatCurrency(invoice.tax, invoice.currency) }}
                                        </dd>
                                    </div>
                                    <div
                                        class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold"
                                    >
                                        <dt class="text-gray-900">Total</dt>
                                        <dd class="text-gray-900">
                                            {{ formatCurrency(invoice.total, invoice.currency) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Contract Tab -->
                        <div v-if="activeTab === 'contract' && contract">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">
                                Contract Details
                            </h3>

                            <div class="space-y-6">
                                <div class="rounded-lg bg-gray-50 p-4">
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">
                                                Party A
                                            </dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ contract.party_a || 'N/A' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">
                                                Party B
                                            </dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ contract.party_b || 'N/A' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">
                                                Effective Date
                                            </dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ contract.effective_date || 'N/A' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">
                                                Expiration Date
                                            </dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                {{ contract.expiration_date || 'N/A' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div v-if="contract.contract_summary">
                                    <h4 class="mb-2 text-base font-medium text-gray-900">
                                        Summary
                                    </h4>
                                    <div
                                        class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700"
                                    >
                                        {{ contract.contract_summary }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raw JSON Tab -->
                        <div v-if="activeTab === 'json'">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">
                                Raw LLM JSON Output
                            </h3>
                            <div class="overflow-x-auto rounded-lg bg-gray-900 p-4">
                                <pre
                                    class="text-xs text-gray-100"
                                ><code>{{ JSON.stringify(llm_json, null, 2) }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
