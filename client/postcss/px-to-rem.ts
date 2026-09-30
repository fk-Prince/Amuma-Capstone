const PX = /"[^"]*"|'[^']*'|url\([^)]*\)|(-?\d*\.?\d+)px\b/g;

export default function pxToRem({ rootValue = 16, minPixelValue = 2 } = {}) {
    const convert = (match: string, value?: string) => {
        if (!value) return match;

        const px = parseFloat(value);
        if (Math.abs(px) < minPixelValue) return match;

        return `${Number((px / rootValue).toFixed(5))}rem`;
    };

    return {
        postcssPlugin: "px-to-rem",
        prepare(result: { opts: { from?: string } }) {
            if (/node_modules/.test(result.opts.from ?? "")) return {};

            return {
                Declaration(decl: { value: string }) {
                    if (decl.value.includes("px")) {
                        decl.value = decl.value.replace(PX, convert);
                    }
                },
            };
        },
    };
}
