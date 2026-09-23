import { reactive } from 'vue';

export const spaState = reactive({
    loading: false,
    pageData: null,
    meta: null,
    pendingRouteName: null,
    pageBusy: false,
});

export function setSpaPage(payload) {
    spaState.pageData = payload?.data ?? null;
    spaState.meta = payload?.meta ?? null;
}

export function setSpaLoading(loading, routeName = null) {
    spaState.loading = loading;
    spaState.pendingRouteName = routeName;
}
