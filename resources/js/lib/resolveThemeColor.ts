/**
 * Chart.js draws on a <canvas>, whose 2D context does not reliably resolve
 * CSS custom properties or color-mix() the way DOM element styles do. This
 * resolves a theme CSS variable to a concrete rgba() string via the DOM
 * cascade, so canvas fillStyle always gets a color it can actually parse.
 */
export function resolveThemeColor(varName: string, alpha = 1): string {
    if (typeof document === 'undefined') {
        return `rgba(0, 0, 0, ${alpha})`;
    }

    const probe = document.createElement('span');
    probe.style.color = `var(${varName})`;
    probe.style.display = 'none';
    document.body.appendChild(probe);
    const resolved = getComputedStyle(probe).color;
    document.body.removeChild(probe);

    const channels = resolved.match(/[\d.]+/g);

    if (!channels || channels.length < 3) {
        return `rgba(0, 0, 0, ${alpha})`;
    }

    const [r, g, b] = channels;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
