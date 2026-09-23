<template>
    <article class="pcard flex h-full animate-pulse flex-col">
        <div class="pcard__media"></div>
        <div class="pcard__body gap-2">
            <div class="h-3 w-20 rounded bg-slate-200"></div>
            <div class="h-4 w-full rounded bg-slate-200"></div>
            <div class="h-4 w-4/5 rounded bg-slate-200"></div>
            <div class="mt-auto flex items-center justify-between pt-1">
                <div class="h-5 w-16 rounded bg-slate-200"></div>
                <div class="h-4 w-14 rounded bg-slate-200"></div>
            </div>
        </div>
        <div class="px-3 pb-3 lg:hidden">
            <div class="h-9 w-full rounded bg-slate-200"></div>
        </div>
    </article>
</template>

<script>
export default { name: 'ProductCardSkeleton' };
</script>
