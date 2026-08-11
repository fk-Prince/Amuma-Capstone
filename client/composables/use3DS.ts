export const use3DS = (
    onProcessingChange?: (processing: boolean) => void,
) => {
    const handle3DS = (
        url: string,
        title: string = "3DS Authentication",
    ) => {
        const overlay = document.createElement("div");

        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            display:flex;
            align-items:center;
            justify-content:center;
            z-index:9999;
        `;

        const modal = document.createElement("div");

        modal.style.cssText = `
            width:500px;
            height:620px;
            background:white;
            border-radius:16px;
            overflow:hidden;
            display:flex;
            flex-direction:column;
            box-shadow:0 20px 60px rgba(0,0,0,.3);
        `;

        const header = document.createElement("div");

        header.style.cssText = `
            height:50px;
            padding:0 16px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            background:#f8fafc;
            border-bottom:1px solid #e2e8f0;
            font-weight:600;
        `;

        const titleEl = document.createElement("span");
        titleEl.innerHTML = `🔒 ${title}`;

        const closeBtn = document.createElement("button");

        closeBtn.innerHTML = "✕";

        closeBtn.style.cssText = `
            border:none;
            background:none;
            cursor:pointer;
            font-size:18px;
            color:#64748b;
        `;

        /**
         * Disable/enable X button based on payment processing.
         */
        const setProcessing = (value: boolean) => {
            onProcessingChange?.(value);

            closeBtn.disabled = value;

            closeBtn.style.cursor = value
                ? "not-allowed"
                : "pointer";

            closeBtn.style.opacity = value
                ? "0.4"
                : "1";

            closeBtn.title = value
                ? "Please wait while payment is processing"
                : "Close";
        };

        header.append(titleEl, closeBtn);

        const iframe = document.createElement("iframe");

        iframe.src = url;

        iframe.style.cssText = `
            flex:1;
            width:100%;
            border:none;
        `;

        modal.append(header, iframe);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        let closed = false;

        let completionCallback: (() => void) | null = null;
        let closeCallback: (() => void) | null = null;

        const close = (trigger = false) => {
            // Do not close while Xendit payment is processing
            if (closeBtn.disabled) {
                return;
            }

            if (closed) return;

            closed = true;

            window.removeEventListener(
                "message",
                messageHandler,
            );

            overlay.remove();

            if (trigger) {
                closeCallback?.();
            }
        };

        const complete = () => {
            /**
             * 3DS is completed.
             *
             * At this point cardPayment() will execute
             * createSubscription(), so disable the X button.
             */
            setProcessing(true);

            completionCallback?.();
        };

        const messageHandler = (event: MessageEvent) => {
            const data = event.data;

            if (!data) return;

            if (
                data.status === "success" ||
                data.status === "completed" ||
                data.event === "3ds-complete"
            ) {
                complete();
                return;
            }

            if (
                data.status === "failed" ||
                data.event === "3ds-failed"
            ) {
                setProcessing(false);
                close(true);
            }
        };

        window.addEventListener(
            "message",
            messageHandler,
        );

        closeBtn.addEventListener("click", () => {
            if (closeBtn.disabled) {
                return;
            }

            close(true);
        });

        return {
            close,

            /**
             * Manually update processing state
             * from cardPayment().
             */
            setProcessing,

            onComplete(cb: () => void) {
                completionCallback = cb;
            },

            onClose(cb: () => void) {
                closeCallback = cb;
            },
        };
    };

    return {
        handle3DS,
    };
};
// const { handle3DS } = use3DS();

// const modal = handle3DS(response.invoice_url);

// modal.onComplete(() => {
//     console.log("3DS success");
//     navigateTo("/product/subscription-summary?status=true");
// });

// modal.onClose(() => {
//     console.log("3DS closed");
// });