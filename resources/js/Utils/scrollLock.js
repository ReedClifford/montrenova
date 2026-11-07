const locks = new Set();
let previousOverflow = "";

export function lockBodyScroll() {
    if (typeof document === "undefined") return () => {};
    const token = Symbol("scroll-lock");
    if (!locks.size) previousOverflow = document.body.style.overflow;
    locks.add(token);
    document.body.style.overflow = "hidden";
    return () => {
        locks.delete(token);
        if (!locks.size) document.body.style.overflow = previousOverflow;
    };
}
