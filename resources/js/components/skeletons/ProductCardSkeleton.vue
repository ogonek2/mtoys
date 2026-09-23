<template>
    <article class="pcard flex h-full animate-pulse flex-col">
        <div class="pcard__media aspect-[4/5] sm:aspect-square"></div>
        <div class="flex flex-1 flex-col gap-2 p-3">
            <div class="h-3 w-full rounded bg-gray-100"></div>
            <div class="h-3 w-3/4 rounded bg-gray-100"></div>
            <div class="mt-1 h-4 w-2/5 rounded bg-gray-100"></div>
            <div class="mt-auto grid grid-cols-[1fr_auto] gap-1.5 pt-1">
                <div class="h-9 rounded-full bg-gray-100"></div>
                <div class="h-9 w-20 rounded-full bg-gray-200"></div>
            </div>
        </div>
    </article>
</template>

<script>
export default { name: 'ProductCardSkeleton' };
</script>
