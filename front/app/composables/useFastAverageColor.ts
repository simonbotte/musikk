import { FastAverageColor } from "fast-average-color";
// import { getAverageColor } from 'fast-average-color-node';

export const useFastAverageColor = () => {
    const getColor = async (src: string) => {
        if (import.meta.server) {
            const { getAverageColor } = await import('fast-average-color-node')
            return getAverageColor(src, {
                ignoredColor: [[255, 255, 255, 255], [0, 0, 0, 255]],
            });
        }

        if (import.meta.client) {
            const fac = new FastAverageColor();
            const color = fac.getColorAsync(src, {
                ignoredColor: [[255, 255, 255, 255], [0, 0, 0, 255]],});
            return color;
        }
        
    }
    
    return {
        getColor,
    }
}