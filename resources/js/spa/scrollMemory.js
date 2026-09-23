import { spaState } from './spaStore.js';

const scrollPositions = new Map();
const MAX_ENTRIES = 80;

export let isScrollRestoring = false;

function trimCache() {
    if (scrollPositions.size <= MAX_ENTRIES) return;
    const firstKey = scrollPositions.keys().next().value;
    scrollPositions.delete(firstKey);
}

function waitForNextPaint() {
    return new Promise((resolve) => {
        requestAnimationFrame(() => requestAnimationFrame(resolve));
    });
}

export { waitForNextPaint };

export function saveScrollPosition(fullPath) {
    if (!fullPath) return;

    scrollPositions.set(fullPath, {
        left: window.scrollX || window.pageXOffset || 0,
        top: window.scrollY || window.pageYOffset || 0,
    });
    trimCache();
}

export function getScrollPosition(fullPath) {
    const saved = scrollPositions.get(fullPath);
    return saved ? { ...saved } : null;
}

export function waitForSpaContent() {
    return new Promise((resolve) => {
        const tick = () => {
            if (spaState.loading || spaState.pageBusy) {
                requestAnimationFrame(tick);
                return;
            }

            waitForNextPaint().then(resolve);
        };

        tick();
    });
}

export async function applyScrollPosition(position) {
    if (!position) return;

    isScrollRestoring = true;

    try {
        await waitForSpaContent();

        for (let attempt = 0; attempt < 16; attempt++) {
            const maxTop = Math.max(
                0,
                document.documentElement.scrollHeight - window.innerHeight,
            );
            const top = Math.min(position.top, maxTop);

            window.scrollTo({
                left: position.left,
                top,
                behavior: 'auto',
            });

            if (Math.abs(window.scrollY - top) < 4) break;
            await waitForNextPaint();
        }
    } finally {
        isScrollRestoring = false;
    }
}
