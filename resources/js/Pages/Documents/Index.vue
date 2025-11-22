<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    documents: Object,
    filters: Object,
});

// Local filter state
const search = ref(props.filters.search);
const category = ref(props.filters.category);
const status = ref(props.filters.status);

// Watch for filter changes and update URL
watch([search, category, status], () => {
    router.get(
        route('documents.index'),
        {
            search: search.value,
            category: category.value,
            status: status.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
}, { debounce: 300 });

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
</script>

<template>
    <Head title="Documents" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Documents
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('exports.invoices.csv')"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
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
                        Export All Invoices (CSV)
                    </Link>
                    <Link
                        :href="route('documents.create')"
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
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Upload Document
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <!-- Filters -->
                    <div class="border-b border-gray-200 bg-gray-50 p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <!-- Search -->
                            <div>
                                <label
                                    for="search"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Search Filename
                                </label>
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    placeholder="Search by filename..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>

                            <!-- Category Filter -->
                            <div>
                                <label
                                    for="category"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Category
                                </label>
                                <select
                                    id="category"
                                    v-model="category"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="all">All Categories</option>
                                    <option value="invoice">Invoice</option>
                                    <option value="contract">Contract</option>
                                    <option value="general">General</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label
                                    for="status"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Status
                                </label>
                                <select
                                    id="status"
                                    v-model="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="extracted">Extracted</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Filename
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Category
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Created
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Updated
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="document in documents.data"
                                    :key="document.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <Link
                                            :href="route('documents.show', document.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{ document.filename }}
                                        </Link>
                                        <div class="mt-1 flex gap-2">
                                            <span
                                                v-if="document.has_invoice"
                                                class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                                            >
                                                Invoice Data
                                            </span>
                                            <span
                                                v-if="document.has_contract"
                                                class="inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700"
                                            >
                                                Contract Data
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            :class="getCategoryColor(document.category)"
                                            class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize leading-5"
                                        >
                                            {{ document.category }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            :class="getStatusColor(document.status)"
                                            class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize leading-5"
                                        >
                                            {{ document.status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ document.created_at }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ document.updated_at }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <Link
                                                :href="route('documents.show', document.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                            <a
                                                :href="route('documents.export.json', document.id)"
                                                class="text-green-600 hover:text-green-900"
                                                target="_blank"
                                            >
                                                JSON
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="documents.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-6 py-12 text-center text-sm text-gray-500"
                                    >
                                        No documents found. Try adjusting your filters or
                                        <Link
                                            :href="route('documents.create')"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            upload a document
                                        </Link>.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="documents.links.length > 3"
                        class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <Link
                                    v-if="documents.prev_page_url"
                                    :href="documents.prev_page_url"
                                    class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="documents.next_page_url"
                                    :href="documents.next_page_url"
                                    class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing
                                        <span class="font-medium">{{ documents.from }}</span>
                                        to
                                        <span class="font-medium">{{ documents.to }}</span>
                                        of
                                        <span class="font-medium">{{ documents.total }}</span>
                                        results
                                    </p>
                                </div>
                                <div>
                                    <nav
                                        class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                                        aria-label="Pagination"
                                    >
                                        <Link
                                            v-for="(link, index) in documents.links"
                                            :key="index"
                                            :href="link.url"
                                            :class="[
                                                link.active
                                                    ? 'z-10 bg-indigo-600 text-white'
                                                    : 'bg-white text-gray-500 hover:bg-gray-50',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === documents.links.length - 1
                                                    ? 'rounded-r-md'
                                                    : '',
                                                !link.url ? 'cursor-not-allowed opacity-50' : '',
                                            ]"
                                            class="relative inline-flex items-center border border-gray-300 px-4 py-2 text-sm font-medium"
                                            v-html="link.label"
                                        />
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
