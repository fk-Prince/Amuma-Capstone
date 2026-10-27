const PH_FLAG_SVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 15"><rect width="30" height="7.5" fill="#0038A8"/><rect y="7.5" width="30" height="7.5" fill="#CE1126"/><path d="M0 0 L12.99 7.5 L0 15 Z" fill="#FFFFFF"/><circle cx="4.33" cy="7.5" r="1.6" fill="#FCD116"/><circle cx="1.3" cy="1.9" r="0.6" fill="#FCD116"/><circle cx="1.3" cy="13.1" r="0.6" fill="#FCD116"/><circle cx="10.6" cy="7.5" r="0.6" fill="#FCD116"/></svg>`;

export const PH_FLAG = `data:image/svg+xml;utf8,${encodeURIComponent(PH_FLAG_SVG)}`;

export const FLAG_ICON_CLASS = "h-3.5 w-7 shrink-0 rounded-[2px] ring-1 ring-black/10";
