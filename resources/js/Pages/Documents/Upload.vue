<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    file: null,
});

const fileInput = ref(null);
const selectedFileName = ref('');

const handleFileChange = (event) => {
    form.file = event.target.files[0];
    selectedFileName.value = event.target.files[0]?.name || '';
};

const submit = () => {
    form.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            selectedFileName.value = '';
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <Head title="Upload Document" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Upload Document
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Success Message -->
                        <div
                            v-if="$page.props.flash?.success"
                            class="mb-4 rounded-md bg-green-50 p-4"
                        >
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-green-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ $page.props.flash.success }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Form -->
                        <form @submit.prevent="submit">
                            <div class="space-y-6">
                                <!-- File Input -->
                                <div>
                                    <label
                                        for="file"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Select Document
                                    </label>
                                    <div class="mt-1">
                                        <input
                                            id="file"
                                            ref="fileInput"
                                            type="file"
                                            accept=".pdf,.png,.jpg,.jpeg"
                                            @change="handleFileChange"
                                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none"
                                        />
                                        <p class="mt-1 text-sm text-gray-500">
                                            PDF, PNG, JPG, or JPEG (max 10MB)
                                        </p>
                                    </div>
                                    <div
                                        v-if="form.errors.file"
                                        class="mt-2 text-sm text-red-600"
                                    >
                                        {{ form.errors.file }}
                                    </div>
                                    <div
                                        v-if="selectedFileName"
                                        class="mt-2 text-sm text-gray-600"
                                    >
                                        Selected: <strong>{{ selectedFileName }}</strong>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex items-center justify-between">
                                    <button
                                        type="submit"
                                        :disabled="form.processing || !form.file"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="form.processing">Uploading...</span>
                                        <span v-else>Upload Document</span>
                                    </button>
                                </div>

                                <!-- Processing Indicator -->
                                <div
                                    v-if="form.processing"
                                    class="rounded-md bg-blue-50 p-4"
                                >
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg
                                                class="h-5 w-5 text-blue-400 animate-spin"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                ></circle>
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                ></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-blue-800">
                                                Uploading your document...
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Info Box -->
                        <div class="mt-8 rounded-md bg-gray-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-gray-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm text-gray-700">
                                        After uploading, your document will be automatically processed
                                        using AI to extract structured data. You can view the results
                                        in your documents list.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
