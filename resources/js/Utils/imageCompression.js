export const compressImageFile = async (
    file,
    {
        maxWidth = 1600,
        maxHeight = 1600,
        quality = 0.78,
        outputType = "image/jpeg",
    } = {},
) => {
    if (!file || !file.type?.startsWith("image/")) {
        return file;
    }

    const imageBitmap = await createImageBitmap(file);

    let { width, height } = imageBitmap;

    const scale = Math.min(maxWidth / width, maxHeight / height, 1);

    const targetWidth = Math.round(width * scale);
    const targetHeight = Math.round(height * scale);

    const canvas = document.createElement("canvas");
    canvas.width = targetWidth;
    canvas.height = targetHeight;

    const ctx = canvas.getContext("2d");

    ctx.drawImage(imageBitmap, 0, 0, targetWidth, targetHeight);

    const blob = await new Promise((resolve) => {
        canvas.toBlob(resolve, outputType, quality);
    });

    imageBitmap.close();

    if (!blob) {
        return file;
    }

    const originalName = file.name || "watch-photo.jpg";
    const cleanName = originalName.replace(/\.[^/.]+$/, "");
    const compressedName = `${cleanName}.jpg`;

    return new File([blob], compressedName, {
        type: outputType,
        lastModified: Date.now(),
    });
};

export const formatFileSize = (bytes) => {
    if (!bytes) return "0 KB";

    const kb = bytes / 1024;

    if (kb < 1024) {
        return `${kb.toFixed(0)} KB`;
    }

    return `${(kb / 1024).toFixed(2)} MB`;
};