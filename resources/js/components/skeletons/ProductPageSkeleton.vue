<template>
    <div class="product-page animate-pulse bg-[#F2F7FA]">
        <div class="border-b border-gray-200 bg-white py-3">
            <div class="mx-auto flex max-w-site gap-2 px-4 sm:px-6 lg:px-8">
                <div class="h-4 w-48 bg-gray-200"></div>
            </div>
        </div>

        <div class="mx-auto max-w-site px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-2">
                <div class="aspect-square bg-gray-200"></div>
                <div class="space-y-4">
                    <div class="h-8 w-3/4 bg-gray-200"></div>
                    <div class="h-4 w-1/3 bg-gray-100"></div>
                    <div class="h-10 w-1/2 bg-gray-200"></div>
                    <div class="h-12 w-full bg-gray-200"></div>
                    <div class="h-24 w-full bg-gray-100"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default { name: 'ProductPageSkeleton' };
</script>
