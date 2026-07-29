// export const use3DS = () => {
//     const handle3DS = (url: string, title: string = "3DS Authentication") => {
//         const overlay = document.createElement("div");
//         overlay.style.cssText = `
//             position: fixed;
//             inset: 0;
//             background: rgba(0, 0, 0, 0.6);
//             display: flex;
//             align-items: center;
//             justify-content: center;
//             z-index: 9999;
//         `;

//         const modal = document.createElement("div");
//         modal.style.cssText = `
//             background: white;
//             border-radius: 12px;
//             overflow: hidden;
//             display: flex;
//             flex-direction: column;
//             width: 500px;
//             height: 620px;
//             box-shadow: 0 20px 60px rgba(0,0,0,0.3);
//             position: relative;
//         `;

//         const header = document.createElement("div");
//         header.style.cssText = `
//             padding: 12px 16px;
//             background: #f9f9f9;
//             border-bottom: 1px solid #eee;
//             font-size: 14px;
//             font-weight: 600;
//             color: #333;
//             display: flex;
//             justify-content: space-between;
//             align-items: center;
//         `;

//         const titleEl = document.createElement("span");
//         titleEl.innerHTML = `🔒 ${title}`;


//         const closeBtn = document.createElement("button");
//         closeBtn.innerHTML = "✕";
//         closeBtn.style.cssText = `
//             border: none;
//             background: transparent;
//             font-size: 18px;
//             cursor: pointer;
//             color: #666;
//         `;

//         header.appendChild(titleEl);
//         header.appendChild(closeBtn);


//         const iframe = document.createElement("iframe");
//         iframe.src = url;
//         iframe.style.cssText = `flex: 1; border: none; width: 100%;`;

//         modal.appendChild(header);
//         modal.appendChild(iframe);
//         overlay.appendChild(modal);
//         document.body.appendChild(overlay);

//         let closed = false;

//         const close = () => {
//             if (!closed && document.body.contains(overlay)) {
//                 closed = true;
//                 document.body.removeChild(overlay);
//             }
//         };


//         closeBtn.addEventListener("click", close);

//         let completionCallback: (() => void) | null = null;

//         const onComplete = (cb: () => void) => {
//             completionCallback = cb;
//         };

//         iframe.addEventListener("load", () => {
//             try {
//                 const iframeUrl = iframe.contentWindow?.location.href;

//                 if (
//                     iframeUrl?.includes("xendit.co") ||
//                     iframeUrl?.includes("3ds")
//                 ) {
//                     return;
//                 }

//                 if (completionCallback) {
//                     completionCallback();
//                 }
//             } catch {
//                 console.log("Xendit Domain");
//             }
//         });

//         return { close, onComplete };
//     };

//     return { handle3DS };
// };

export const use3DS = () => {
    const handle3DS = (
        url: string,
        title: string = "3DS Authentication",
    ) => {
        const overlay = document.createElement("div");

        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;


        const modal = document.createElement("div");

        modal.style.cssText = `
            background: white;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            width: 500px;
            height: 620px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
        `;


        const header = document.createElement("div");

        header.style.cssText = `
            padding: 12px 16px;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;


        const titleEl = document.createElement("span");
        titleEl.innerHTML = `🔒 ${title}`;


        const closeBtn = document.createElement("button");

        closeBtn.innerHTML = "✕";

        closeBtn.style.cssText = `
            border: none;
            background: transparent;
            font-size: 18px;
            cursor: pointer;
            color: #666;
        `;


        header.appendChild(titleEl);
        header.appendChild(closeBtn);


        const iframe = document.createElement("iframe");

        iframe.src = url;

        iframe.style.cssText = `
            flex: 1;
            border: none;
            width: 100%;
        `;


        modal.appendChild(header);
        modal.appendChild(iframe);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        let closed = false;
        let completionCallback: (() => void) | null = null;
        let closeCallback: (() => void) | null = null;


        const close = (triggerCallback = false) => {
            if (closed) return;

            closed = true;

            if (document.body.contains(overlay)) {
                document.body.removeChild(overlay);
            }

            if (triggerCallback) {
                closeCallback?.();
            }
        };


        const onComplete = (cb: () => void) => {
            completionCallback = cb;
        };


        const onClose = (cb: () => void) => {
            closeCallback = cb;
        };


        closeBtn.addEventListener("click", () => {
            close(true);
        });


        iframe.addEventListener("load", () => {
            try {
                const iframeUrl =
                    iframe.contentWindow?.location.href;
                if (
                    iframeUrl?.includes("xendit.co") ||
                    iframeUrl?.includes("3ds")
                ) {
                    return;
                }


                completionCallback?.();

            } catch {
            }
        });


        return {
            close,
            onComplete,
            onClose,
        };
    };


    return {
        handle3DS,
    };
};
