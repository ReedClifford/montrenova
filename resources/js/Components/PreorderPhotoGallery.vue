<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { lockBodyScroll } from "@/Utils/scrollLock";

const props = defineProps({
    images: { type: Array, default: () => [] },
    primaryImage: { type: String, default: "" },
    title: { type: String, default: "Watch" },
    compact: Boolean,
    thumbnail: Boolean,
    badge: { type: String, default: "Preorder" },
});
const selected = ref(0);
const viewer = ref(null);
const expanded = ref(false);
const zoomed = ref(false);
const attempts = ref({});
const failed = ref({});
let releaseScroll = null;
let returnFocus = null;
let touchStart = null;
let swiped = false;

const photos = computed(() => {
    const items = props.images
        .map((image) =>
            typeof image === "string" ? { image_url: image } : image,
        )
        .filter(Boolean);
    if (!items.length && props.primaryImage)
        items.push({ image_url: props.primaryImage });
    const seen = new Set();
    return items
        .map((image, index) => ({
            key: image.id ?? index,
            urls: [
                ...new Set(
                    [
                        image.hd_url,
                        image.image_url,
                        image.primary_hd_url,
                        image.primary_image_url,
                        image.thumbnail_url,
                    ].filter(Boolean),
                ),
            ],
        }))
        .filter((image) => {
            if (!image.urls.length || seen.has(image.urls[0])) return false;
            seen.add(image.urls[0]);
            return true;
        });
});
const current = computed(() => photos.value[selected.value]);
const currentUrl = computed(
    () => current.value?.urls[attempts.value[selected.value] || 0],
);
const count = computed(() => photos.value.length);
const unavailable = computed(
    () => !current.value || failed.value[selected.value],
);
function imageError(index) {
    const next = (attempts.value[index] || 0) + 1;
    if (next < (photos.value[index]?.urls.length || 0))
        attempts.value = { ...attempts.value, [index]: next };
    else failed.value = { ...failed.value, [index]: true };
}
function source(index) {
    return photos.value[index]?.urls[attempts.value[index] || 0];
}
function select(index) {
    selected.value = (index + count.value) % count.value;
    zoomed.value = false;
}
function move(direction) {
    if (count.value > 1) select(selected.value + direction);
}
function openViewer(index = selected.value) {
    if (swiped) {
        swiped = false;
        return;
    }
    if (!count.value) return;
    select(index);
    expanded.value = true;
}
function closeViewer() {
    swiped = false;
    expanded.value = false;
}
watch(expanded, async (open) => {
    if (open) {
        returnFocus = document.activeElement;
        releaseScroll = lockBodyScroll();
        await nextTick();
        if (expanded.value && !viewer.value?.open) viewer.value?.showModal();
    } else {
        viewer.value?.close();
        releaseScroll?.();
        releaseScroll = null;
        zoomed.value = false;
        if (returnFocus?.isConnected)
            returnFocus.focus({ preventScroll: true });
        returnFocus = null;
    }
});
watch(
    () => [props.images, props.primaryImage],
    () => {
        selected.value = 0;
        attempts.value = {};
        failed.value = {};
        zoomed.value = false;
    },
);
onBeforeUnmount(() => {
    viewer.value?.close();
    releaseScroll?.();
});
function startTouch(event) {
    if (zoomed.value) return;
    swiped = false;
    const t = event.touches[0];
    touchStart = { x: t.clientX, y: t.clientY };
}
function endTouch(event) {
    if (!touchStart || zoomed.value) return;
    const t = event.changedTouches[0];
    const dx = t.clientX - touchStart.x;
    const dy = t.clientY - touchStart.y;
    touchStart = null;
    if (
        Math.abs(dx) > 50 &&
        Math.abs(dx) > Math.abs(dy) * 1.3 &&
        count.value > 1
    ) {
        swiped = !expanded.value;
        move(dx < 0 ? 1 : -1);
    }
}
defineExpose({ open: openViewer });
function viewerKey(event) {
    if (event.key === "ArrowLeft") {
        event.preventDefault();
        move(-1);
    }
    if (event.key === "ArrowRight") {
        event.preventDefault();
        move(1);
    }
}
</script>

<template>
    <div class="preorder-gallery" :class="{ compact, thumbnail }">
        <button
            v-if="thumbnail"
            type="button"
            class="photo-thumbnail-trigger"
            :aria-label="`View ${title} photos`"
            @click="openViewer(0)"
        >
            <img
                v-if="!unavailable"
                :src="currentUrl"
                :alt="title"
                loading="lazy"
                @error="imageError(selected)"
            /><span v-else class="thumbnail-placeholder">MN</span
            ><span class="thumbnail-photo-count">{{
                count
                    ? `${count} ${count === 1 ? "photo" : "photos"}`
                    : "No photo"
            }}</span>
        </button>
        <template v-else>
            <div
                class="gallery-frame"
                @touchstart.passive="startTouch"
                @touchend.passive="endTouch"
            >
                <button
                    v-if="!unavailable"
                    type="button"
                    class="gallery-image-trigger"
                    :aria-label="`Enlarge ${title} photo ${selected + 1}`"
                    @click="openViewer()"
                >
                    <img
                        :key="currentUrl"
                        :src="currentUrl"
                        :alt="`${title} — reference photo ${selected + 1}`"
                        draggable="false"
                        decoding="async"
                        fetchpriority="high"
                        @error="imageError(selected)"
                    />
                </button>
                <div v-else class="gallery-empty">
                    <span>MN</span>
                    <p>
                        {{
                            count
                                ? "This photo is unavailable."
                                : "Reference photos coming soon."
                        }}
                    </p>
                    <button v-if="count > 1" type="button" @click="move(1)">
                        Try the next photo
                    </button>
                </div>
                <span v-if="badge" class="gallery-badge">{{ badge }}</span>
                <span v-if="count" class="gallery-counter" aria-live="polite"
                    >{{ selected + 1 }} / {{ count }}</span
                >
                <button
                    v-if="count > 1"
                    type="button"
                    class="gallery-arrow previous"
                    aria-label="Previous watch photo"
                    @click="move(-1)"
                >
                    ‹</button
                ><button
                    v-if="count > 1"
                    type="button"
                    class="gallery-arrow next"
                    aria-label="Next watch photo"
                    @click="move(1)"
                >
                    ›
                </button>
                <span v-if="!unavailable" class="gallery-enlarge-hint"
                    ><svg
                        viewBox="0 0 24 24"
                        width="15"
                        height="15"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        aria-hidden="true"
                    >
                        <path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5" />
                    </svg>
                    Tap to enlarge</span
                >
            </div>
            <div
                v-if="count > 1"
                class="gallery-thumbnails"
                aria-label="Choose a reference photo"
            >
                <button
                    v-for="(photo, index) in photos"
                    :key="photo.key"
                    type="button"
                    :class="{ selected: selected === index }"
                    :aria-label="`Show watch photo ${index + 1}`"
                    :aria-pressed="selected === index"
                    @click="select(index)"
                >
                    <img
                        v-if="!failed[index]"
                        :src="source(index)"
                        :alt="`Reference ${index + 1}`"
                        loading="lazy"
                        @error="imageError(index)"
                    /><span v-else>{{ index + 1 }}</span>
                </button>
            </div>
        </template>
        <Teleport to="body"
            ><dialog
                ref="viewer"
                class="preorder-photo-viewer"
                :aria-label="`${title} photo viewer`"
                @cancel.prevent="closeViewer"
                @keydown="viewerKey"
            >
                <template v-if="expanded"
                    ><header class="photo-viewer-toolbar">
                        <div>
                            <p>Reference photos</p>
                            <h2>{{ title }}</h2>
                        </div>
                        <div class="viewer-toolbar-actions">
                            <button
                                type="button"
                                :disabled="unavailable"
                                :aria-pressed="zoomed"
                                @click="zoomed = !zoomed"
                            >
                                {{
                                    zoomed ? "− Fit photo" : "+ Zoom in"
                                }}</button
                            ><button
                                type="button"
                                class="close-viewer"
                                aria-label="Close photo viewer"
                                autofocus
                                @click="closeViewer"
                            >
                                ✕
                            </button>
                        </div>
                    </header>
                    <div class="photo-viewer-body">
                        <div
                            class="photo-viewer-stage"
                            :class="{ zoomed }"
                            @touchstart.passive="startTouch"
                            @touchend.passive="endTouch"
                            @dblclick="zoomed = !zoomed"
                        >
                            <img
                                v-if="!unavailable"
                                :key="currentUrl"
                                :src="currentUrl"
                                :alt="`${title} — reference photo ${selected + 1}`"
                                draggable="false"
                                @error="imageError(selected)"
                            />
                            <div v-else class="viewer-unavailable">
                                Photo unavailable. Choose another image below.
                            </div>
                        </div>
                        <template v-if="count > 1"
                            ><button
                                type="button"
                                class="viewer-arrow previous"
                                aria-label="Previous enlarged photo"
                                @click="move(-1)"
                            >
                                ‹</button
                            ><button
                                type="button"
                                class="viewer-arrow next"
                                aria-label="Next enlarged photo"
                                @click="move(1)"
                            >
                                ›
                            </button></template
                        >
                    </div>
                    <footer class="photo-viewer-footer">
                        <div class="viewer-footer-info">
                            <span aria-live="polite"
                                >{{ selected + 1 }} / {{ count }}</span
                            >
                            <p>
                                {{
                                    zoomed
                                        ? "Scroll or drag on touch to inspect details."
                                        : count > 1
                                          ? "Swipe or use the arrows to browse."
                                          : "Full reference photo · no cropping"
                                }}
                            </p>
                        </div>
                        <div v-if="count > 1" class="viewer-thumbnails">
                            <button
                                v-for="(photo, index) in photos"
                                :key="photo.key"
                                type="button"
                                :class="{ selected: selected === index }"
                                :aria-label="`View enlarged photo ${index + 1}`"
                                :aria-pressed="selected === index"
                                @click="select(index)"
                            >
                                <img
                                    v-if="!failed[index]"
                                    :src="source(index)"
                                    alt=""
                                    @error="imageError(index)"
                                /><span v-else>{{ index + 1 }}</span>
                            </button>
                        </div>
                    </footer></template
                >
            </dialog></Teleport
        >
    </div>
</template>

<style scoped>
.preorder-gallery {
    width: 100%;
    min-width: 0;
    color: #e4e4e7;
}
.gallery-frame {
    position: relative;
    aspect-ratio: 4/5;
    max-height: 640px;
    width: 100%;
    min-height: 280px;
    border: 1px solid #ffffff12;
    border-radius: 18px;
    background: radial-gradient(
        ellipse at center,
        #252529 0%,
        #161619 65%,
        #111113 100%
    );
    overflow: hidden;
    touch-action: pan-y;
}
.gallery-image-trigger {
    display: block !important;
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0 !important;
    border-radius: 0 !important;
    padding: 28px !important;
    background: transparent !important;
    cursor: zoom-in !important;
}
.gallery-image-trigger img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    user-select: none;
}
.gallery-badge,
.gallery-counter {
    position: absolute;
    z-index: 2;
    top: 16px;
    font-size: 10px;
    line-height: 1.6;
    pointer-events: none;
}
.gallery-badge {
    left: 16px;
    border: 1px solid #e4d1ac25;
    background: #191817d9;
    color: #e4d1ac;
    padding: 6px 11px;
    border-radius: 24px;
    backdrop-filter: blur(10px);
}
.gallery-counter {
    right: 16px;
    background: #09090bd9;
    color: #e4e4e7;
    padding: 6px 10px;
    border-radius: 24px;
    font-variant-numeric: tabular-nums;
}
.gallery-arrow,
.viewer-arrow {
    position: absolute !important;
    z-index: 3;
    top: 50%;
    transform: translateY(-50%);
    display: grid !important;
    place-items: center;
    width: 44px;
    height: 44px;
    padding: 0 !important;
    min-height: 44px !important;
    border: 1px solid #ffffff25 !important;
    border-radius: 50% !important;
    background: #111113cc !important;
    color: #f4f4f5 !important;
    font-size: 30px !important;
    font-weight: 400 !important;
    line-height: 1;
    backdrop-filter: blur(12px);
    cursor: pointer;
}
.previous {
    left: 12px;
}
.next {
    right: 12px;
}
.gallery-enlarge-hint {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 8px;
    pointer-events: none;
    white-space: nowrap;
    border: 1px solid #ffffff18;
    background: #0b0b0dd9;
    color: #d4d4d8;
    font-size: 10px;
    padding: 7px 12px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}
.gallery-thumbnails {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 14px 3px 4px;
    scrollbar-width: thin;
    scrollbar-color: #55555c transparent;
}
.gallery-thumbnails button,
.viewer-thumbnails button {
    display: grid !important;
    place-items: center;
    flex: 0 0 66px;
    width: 66px;
    height: 78px;
    padding: 5px !important;
    min-height: 0 !important;
    border: 1px solid #ffffff18 !important;
    border-radius: 9px !important;
    background: #151518 !important;
    color: #a1a1aa !important;
    opacity: 0.6;
    cursor: pointer;
}
.gallery-thumbnails button.selected,
.viewer-thumbnails button.selected {
    border-color: #d5bd93 !important;
    opacity: 1;
    box-shadow: 0 0 0 1px #d5bd9330;
}
.gallery-thumbnails img,
.viewer-thumbnails img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    min-height: 0;
}
.gallery-empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 40px;
    text-align: center;
}
.gallery-empty > span {
    font-size: 36px;
    color: #827761;
    letter-spacing: 0.1em;
}
.gallery-empty p {
    font-size: 12px;
    color: #a1a1aa;
}
.gallery-empty button {
    font-size: 12px;
    color: #d5bd93;
    min-height: 44px;
}
.compact .gallery-frame {
    aspect-ratio: 4/3;
    max-height: 360px;
    min-height: 220px;
    border-radius: 12px;
}
.compact .gallery-image-trigger {
    padding: 22px !important;
}
.compact .gallery-thumbnails button {
    flex-basis: 52px;
    width: 52px;
    height: 60px;
}
.photo-thumbnail-trigger {
    display: block !important;
    position: relative !important;
    flex-shrink: 0;
    width: 96px;
    height: 112px;
    padding: 8px 6px 22px !important;
    border: 1px solid #ffffff18 !important;
    border-radius: 12px !important;
    background: #18181b !important;
    overflow: hidden;
    cursor: zoom-in !important;
}
.photo-thumbnail-trigger img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.thumbnail-placeholder {
    color: #827761;
    font-size: 22px;
}
.thumbnail-photo-count {
    position: absolute;
    inset: auto 0 0;
    padding: 4px 3px;
    background: #0008;
    color: #d4d4d8;
    font-size: 9px;
    text-align: center;
    line-height: 1.5;
}
.thumbnail {
    width: auto;
    flex-shrink: 0;
}
.preorder-gallery button:focus-visible,
.preorder-photo-viewer button:focus-visible {
    outline: 2px solid #d5bd93;
    outline-offset: 3px;
}
.preorder-photo-viewer {
    position: fixed;
    inset: 0;
    width: 100vw;
    max-width: none;
    height: 100dvh;
    max-height: none;
    margin: 0;
    padding: 0;
    border: 0;
    background: #0c0c0f;
    color: #e4e4e7;
    overflow: hidden;
    z-index: 10000;
}
.preorder-photo-viewer::backdrop {
    background: #000e;
}
.preorder-photo-viewer[open] {
    display: flex;
    flex-direction: column;
}
.photo-viewer-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px 24px;
    border-bottom: 1px solid #ffffff14;
    flex-shrink: 0;
    background: #101013;
}
.photo-viewer-toolbar p {
    font-size: 10px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #a1a1aa;
}
.photo-viewer-toolbar h2 {
    font-size: 15px;
    font-weight: 500;
    line-height: 1.6;
    max-width: 65vw;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 3px;
    color: #e4e4e7;
}
.viewer-toolbar-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.viewer-toolbar-actions button {
    display: grid;
    place-items: center;
    min-height: 44px;
    padding: 8px 14px;
    white-space: nowrap;
    background: #ffffff07;
    border: 1px solid #ffffff18;
    border-radius: 9px;
    font-size: 12px;
    color: #e4e4e7;
}
.viewer-toolbar-actions .close-viewer {
    width: 44px;
    padding: 0;
    font-size: 18px;
}
.viewer-toolbar-actions button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.photo-viewer-body {
    position: relative;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}
.photo-viewer-stage {
    width: 100%;
    height: 100%;
    overflow: auto;
    overscroll-behavior: contain;
    touch-action: pan-x pan-y pinch-zoom;
    scrollbar-width: thin;
    scrollbar-color: #55555c #111113;
}
.photo-viewer-stage img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 24px 60px;
    user-select: none;
}
.photo-viewer-stage.zoomed img {
    width: 200%;
    height: 200%;
    max-width: none;
    padding: 20px;
    cursor: zoom-out;
}
.viewer-arrow {
    width: 48px;
    height: 48px;
}
.photo-viewer-footer {
    flex-shrink: 0;
    padding: 12px 24px 16px;
    border-top: 1px solid #ffffff12;
    background: #101013;
}
.viewer-footer-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    font-size: 11px;
    color: #a1a1aa;
}
.viewer-footer-info > span {
    color: #e4d1ac;
    white-space: nowrap;
}
.viewer-thumbnails {
    display: flex;
    justify-content: center;
    gap: 10px;
    overflow-x: auto;
    padding: 12px 2px 2px;
    scrollbar-width: thin;
}
.viewer-thumbnails button {
    flex-basis: 48px;
    width: 48px;
    height: 56px;
}
.viewer-unavailable {
    height: 100%;
    display: grid;
    place-items: center;
    font-size: 13px;
    color: #a1a1aa;
    padding: 30px;
    text-align: center;
}
@media (max-width: 600px) {
    .gallery-frame {
        aspect-ratio: 4/5;
        max-height: 65svh;
        min-height: 270px;
        border-radius: 14px;
    }
    .gallery-image-trigger {
        padding: 18px !important;
    }
    .gallery-badge,
    .gallery-counter {
        top: 12px;
    }
    .gallery-badge {
        left: 12px;
    }
    .gallery-counter {
        right: 12px;
    }
    .gallery-arrow {
        width: 40px;
        height: 40px;
        min-height: 40px !important;
    }
    .gallery-thumbnails button {
        flex-basis: 58px;
        width: 58px;
        height: 66px;
    }
    .photo-viewer-toolbar {
        padding: 12px 14px;
        gap: 10px;
    }
    .photo-viewer-toolbar h2 {
        font-size: 12px;
        max-width: 45vw;
    }
    .photo-viewer-toolbar p {
        font-size: 8px;
    }
    .viewer-toolbar-actions {
        gap: 7px;
    }
    .viewer-toolbar-actions button {
        padding: 8px 9px;
        font-size: 11px;
    }
    .viewer-toolbar-actions .close-viewer {
        width: 40px;
    }
    .photo-viewer-stage img {
        padding: 16px 12px 20px;
    }
    .viewer-arrow {
        top: auto;
        bottom: 10px;
        transform: none;
        background: #111113e8 !important;
    }
    .photo-viewer-footer {
        padding: 10px 14px max(14px, env(safe-area-inset-bottom));
    }
    .viewer-footer-info p {
        font-size: 10px;
        text-align: right;
        max-width: 75%;
    }
    .compact .gallery-frame {
        min-height: 220px;
        max-height: 300px;
    }
    .photo-thumbnail-trigger {
        width: 82px;
        height: 100px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .preorder-photo-viewer,
    .preorder-gallery * {
        scroll-behavior: auto !important;
        transition: none !important;
    }
}
</style>
