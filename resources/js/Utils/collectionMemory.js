const key = "montrenova.collection.v1";
export function readCollection() {
    try {
        const saved = JSON.parse(sessionStorage.getItem(key));
        const url = new URL(saved?.url, window.location.origin);
        if (url.origin !== window.location.origin || url.pathname !== "/")
            return null;
        return saved;
    } catch {
        return null;
    }
}
export function saveCollection(state) {
    try {
        sessionStorage.setItem(key, JSON.stringify(state));
    } catch {
        /* Browsing still works without storage. */
    }
}
export function collectionHref() {
    return readCollection()?.url || "/#collection";
}
export function requestCollectionRestore() {
    try {
        sessionStorage.setItem(key + ".restore", "1");
    } catch {
        /* Optional enhancement. */
    }
}
export function consumeCollectionRestore() {
    try {
        const restore = sessionStorage.getItem(key + ".restore") === "1";
        sessionStorage.removeItem(key + ".restore");
        return restore;
    } catch {
        return false;
    }
}
