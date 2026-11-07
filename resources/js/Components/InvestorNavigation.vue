<script setup>
defineProps({
    tabs: Array,
    active: String,
    user: Object,
    startDate: String,
    title: String,
});
const emit = defineEmits(["select"]);
const icons = {
    overview: "M3 10l9-7 9 7M5 9v12h14V9M9 21v-8h6v8",
    analytics: "M4 20V10m8 10V4m8 16v-7",
    investors:
        "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3a4 4 0 0 1 0 8m6 10v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0",
    watches:
        "M9 2h6l1 5M9 22h6l1-5M8 7h8a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2m4 3v3l2 1",
    expenses: "M6 3h12v18l-3-2-3 2-3-2-3 2V3m3 5h6m-6 4h6",
};
const formatDate = (value) =>
    new Intl.DateTimeFormat("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    }).format(new Date(value + "T00:00:00"));
</script>
<template>
    <aside class="investor-navigation" aria-label="Investor workspace">
        <div class="investor-nav-brand">
            <span>MN</span>
            <div>
                <strong>Montre Nova</strong><small>{{ title }}</small>
            </div>
        </div>
        <p class="nav-eyebrow">Workspace</p>
        <nav aria-label="Investor dashboard sections">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                :aria-current="active === tab.key ? 'page' : undefined"
                :class="{ active: active === tab.key }"
                @click="emit('select', tab.key)"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path :d="icons[tab.key]" /></svg
                ><span class="nav-label"
                    >{{ tab.label }}<small>{{ tab.description }}</small></span
                ><span v-if="tab.count !== undefined" class="nav-count">{{
                    tab.count
                }}</span>
            </button>
        </nav>
        <div class="investor-nav-account">
            <strong>{{ user?.name }}</strong
            ><span>{{
                user?.role?.startsWith("owner")
                    ? "Owner access"
                    : "Investor · View only"
            }}</span>
            <p>
                Reporting since <b>{{ formatDate(startDate) }}</b>
            </p>
        </div>
    </aside>
</template>
<style scoped>
.investor-navigation {
    position: fixed;
    inset: 0 auto 0 0;
    width: 252px;
    z-index: 40;
    background: #ffffffed;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(16px);
    color: #475569;
}
.investor-nav-brand {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 28px 22px;
    border-bottom: 1px solid #f1f5f9;
}
.investor-nav-brand > span {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #4f46e5;
    color: white;
    font-size: 12px;
    font-weight: 650;
}
.investor-nav-brand strong {
    display: block;
    color: #0f172a;
    font-size: 13px;
}
.investor-nav-brand small {
    font-size: 10px;
    color: #64748b;
    display: block;
    margin-top: 5px;
}
.nav-eyebrow {
    text-transform: uppercase;
    color: #94a3b8;
    letter-spacing: 0.16em;
    font-size: 10px;
    padding: 26px 24px 12px;
}
.investor-navigation nav {
    padding: 0 12px;
    display: grid;
    gap: 6px;
    overflow-y: auto;
}
.investor-navigation button {
    display: flex;
    align-items: center;
    gap: 13px;
    width: 100%;
    text-align: left;
    border: 1px solid transparent;
    padding: 14px 12px;
    border-radius: 12px;
    min-height: 58px;
    color: #64748b;
    font-size: 13px;
    transition:
        background 0.15s,
        color 0.15s;
}
.investor-navigation button:hover {
    background: #f8fafc;
    color: #334155;
}
.investor-navigation button.active {
    background: #eef2ff;
    border-color: #e0e7ff;
    color: #4338ca;
}
.investor-navigation svg {
    height: 21px;
    width: 21px;
    flex-shrink: 0;
}
.nav-label {
    min-width: 0;
    flex: 1;
    font-weight: 600;
}
.nav-label small {
    display: block;
    font-size: 10px;
    font-weight: 400;
    color: #94a3b8;
    line-height: 1.6;
    margin-top: 4px;
}
.nav-count {
    font-size: 10px;
    background: #f1f5f9;
    padding: 4px 7px;
    border-radius: 20px;
}
.active .nav-count {
    background: #e0e7ff;
    color: #4338ca;
}
.investor-nav-account {
    margin: auto 16px 18px;
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 11px;
}
.investor-nav-account strong {
    display: block;
    color: #334155;
    overflow-wrap: anywhere;
}
.investor-nav-account > span {
    display: block;
    font-size: 10px;
    color: #6366f1;
    margin-top: 5px;
}
.investor-nav-account p {
    border-top: 1px solid #e2e8f0;
    padding-top: 12px;
    margin-top: 12px;
    color: #94a3b8;
    font-size: 9px;
}
.investor-nav-account b {
    display: block;
    color: #64748b;
    margin-top: 4px;
}
.investor-navigation button:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: -3px;
}
@media (max-width: 1023px) {
    .investor-navigation {
        inset: auto 0 0;
        width: 100%;
        border-top: 1px solid #e2e8f0;
        border-right: 0;
        padding: 6px 6px max(6px, env(safe-area-inset-bottom));
        z-index: 50;
    }
    .investor-nav-brand,
    .nav-eyebrow,
    .investor-nav-account,
    .nav-label small,
    .nav-count {
        display: none;
    }
    .investor-navigation nav {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 2px;
        padding: 0;
        overflow: visible;
        max-width: 650px;
        width: 100%;
        margin: auto;
    }
    .investor-navigation button {
        flex-direction: column;
        gap: 6px;
        border: 0;
        padding: 10px 2px;
        min-height: 56px;
        text-align: center;
        border-radius: 9px;
        font-size: 10px;
    }
    .investor-navigation svg {
        height: 18px;
        width: 18px;
    }
    .nav-label {
        font-weight: 500;
    }
    .investor-navigation button.active {
        background: #eef2ff;
    }
}
@media (prefers-reduced-motion: reduce) {
    .investor-navigation button {
        transition: none;
    }
}
</style>
