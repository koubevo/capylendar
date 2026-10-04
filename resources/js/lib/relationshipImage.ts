export async function createRelationshipImage(
    card: HTMLElement,
): Promise<Blob> {
    const image = card.querySelector('img');
    if (!image) {
        throw new Error('Chybí obrázek kapybar.');
    }

    await Promise.all([document.fonts.ready, image.decode()]);

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    if (!context) {
        throw new Error('Prohlížeč neumí vytvořit obrázek.');
    }

    const width = 640;
    const padding = 24;
    const imageSize = 128;
    const textWidth = width - padding * 3 - imageSize;
    let y = padding;
    const lines: { text: string; font: string; color: string; y: number }[] =
        [];

    card.querySelectorAll('p').forEach((paragraph, index) => {
        const style = getComputedStyle(paragraph);
        const font = `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
        const lineHeight =
            parseFloat(style.lineHeight) || parseFloat(style.fontSize) * 1.5;
        context.font = font;
        let line = '';

        const appendLine = () => {
            lines.push({ text: line, font, color: style.color, y });
            y += lineHeight;
            line = '';
        };

        for (const word of (paragraph.textContent ?? '').trim().split(/\s+/)) {
            const candidate = line ? `${line} ${word}` : word;
            if (context.measureText(candidate).width <= textWidth) {
                line = candidate;
                continue;
            }
            if (line) {
                appendLine();
            }
            if (context.measureText(word).width <= textWidth) {
                line = word;
                continue;
            }
            for (const character of word) {
                if (context.measureText(line + character).width > textWidth) {
                    appendLine();
                }
                line += character;
            }
        }
        appendLine();
        if (index < 2) {
            y += 12;
        }
    });

    const height = Math.max(190, y + padding);
    canvas.width = width * 2;
    canvas.height = Math.ceil(height * 2);
    context.scale(2, 2);

    context.fillStyle = getComputedStyle(card).backgroundColor;
    context.fillRect(0, 0, width, height);
    context.strokeStyle =
        getComputedStyle(card).getPropertyValue('--ui-border');
    context.lineWidth = 1;
    context.beginPath();
    context.roundRect(0.5, 0.5, width - 1, height - 1, 8);
    context.stroke();

    context.textBaseline = 'top';
    for (const line of lines) {
        context.font = line.font;
        context.fillStyle = line.color;
        context.fillText(line.text, padding, line.y);
    }

    const ratio = Math.min(
        imageSize / image.naturalWidth,
        imageSize / image.naturalHeight,
    );
    const imageWidth = image.naturalWidth * ratio;
    const imageHeight = image.naturalHeight * ratio;
    context.drawImage(
        image,
        width - padding - imageSize + (imageSize - imageWidth) / 2,
        (height - imageHeight) / 2,
        imageWidth,
        imageHeight,
    );

    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
            } else {
                reject(new Error('Obrázek se nepodařilo vytvořit.'));
            }
        }, 'image/png');
    });
}

export function canCopyRelationshipImage(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.isSecureContext &&
        !!navigator.clipboard?.write &&
        typeof ClipboardItem !== 'undefined' &&
        (!ClipboardItem.supports || ClipboardItem.supports('image/png'))
    );
}

export function canShareRelationshipImage(file: File): boolean {
    return (
        typeof window !== 'undefined' &&
        window.isSecureContext &&
        !!navigator.share &&
        !!navigator.canShare &&
        navigator.canShare({ files: [file] })
    );
}
