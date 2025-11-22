<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <p class="mb-8 text-sm text-gray-600">
                    Welcome to DocFlow AI. Use the shortcuts below to upload new documents
                    or review previously processed files.
                </p>

                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Upload Document card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">
                            Upload Document
                        </h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Upload an invoice or contract PDF/image and send it through the Qwen
                            extraction pipeline.
                        </p>
                        <Link
                            :href="route('documents.create')"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Go to Upload
                        </Link>
                    </div>

                    <!-- All Documents card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">
                            View All Documents
                        </h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Browse all processed documents, inspect extracted fields, and export
                            data as CSV or JSON.
                        </p>
                        <Link
                            :href="route('documents.index')"
                            class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2"
                        >
                            View Documents
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
