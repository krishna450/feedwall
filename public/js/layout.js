export function radialPosition(index) {
    const angle = index * 0.5; // radians
    const radius = 50 + index * 20;

    return {
        x: radius * Math.cos(angle),
        y: radius * Math.sin(angle)
    };
}
