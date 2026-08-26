const SPINNER_STYLE_ID = "amuma-3ds-spinner";

function ensureSpinnerStyles() {
    if (document.getElementById(SPINNER_STYLE_ID)) return;

    const style = document.createElement("style");

    style.id = SPINNER_STYLE_ID;

    style.textContent = `
        @keyframes amuma-3ds-spin {
            to { transform: rotate(360deg); }
        }
    `;

    document.head.appendChild(style);
}

export const use3DS = (
    onProcessingChange?: (processing: boolean) => void,
) => {
    const handle3DS = (
        url: string,
        title: string = "3DS Authentication",
    ) => {
        ensureSpinnerStyles();

        const overlay = document.createElement("div");

        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .6);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 9999;
        `;

        const modal = document.createElement("div");

        modal.style.cssText = `
            position: relative;
            width: 100%;
            max-width: 500px;
            height: min(620px, 90vh);
            background: white;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        `;

        const header = document.createElement("div");

        header.style.cssText = `
            height: 50px;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #0f172a;
        `;

        const titleEl = document.createElement("span");
        titleEl.innerHTML = `🔒 ${title}`;

        const closeBtn = document.createElement("button");

        closeBtn.type = "button";
        closeBtn.setAttribute("aria-label", "Close");
        closeBtn.innerHTML = "✕";

        closeBtn.style.cssText = `
            border: none;
            background: none;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            color: #64748b;
            padding: 4px 6px;
            border-radius: 6px;
        `;

        header.append(titleEl, closeBtn);

        const iframe = document.createElement("iframe");

        iframe.src = url;

        iframe.style.cssText = `
            flex: 1;
            width: 100%;
            border: none;
        `;

        // Covers the iframe while the payment is being finalised, so the card
        // form can't be interacted with after 3DS has already succeeded.
        const busy = document.createElement("div");

        busy.style.cssText = `
            position: absolute;
            inset: 50px 0 0 0;
            background: rgba(255, 255, 255, .96);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 24px;
            text-align: center;
        `;

        const spinner = document.createElement("div");

        spinner.style.cssText = `
            width: 34px;
            height: 34px;
            border: 3px solid #e2e8f0;
            border-top-color: #0e7c7b;
            border-radius: 50%;
            animation: amuma-3ds-spin .8s linear infinite;
        `;

        const busyTitle = document.createElement("p");

        busyTitle.textContent = "Completing your payment";
        busyTitle.style.cssText = `
            margin: 0;
            font-weight: 600;
            color: #0f172a;
        `;

        const busyHint = document.createElement("p");

        busyHint.textContent =
            "Please don't close this window or go back.";
        busyHint.style.cssText = `
            margin: 0;
            font-size: 13px;
            color: #64748b;
        `;

        busy.append(spinner, busyTitle, busyHint);

        modal.append(header, iframe, busy);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";

        let closed = false;
        let isProcessing = false;
        let completed = false;

        let completionCallback: (() => void) | null = null;
        let closeCallback: (() => void) | null = null;

        /**
         * Reflects the "payment is being finalised" state: the close affordances
         * are disabled and the iframe is covered.
         */
        const setProcessing = (value: boolean) => {
            isProcessing = value;

            onProcessingChange?.(value);

            closeBtn.disabled = value;
            closeBtn.style.cursor = value ? "not-allowed" : "pointer";
            closeBtn.style.opacity = value ? "0.4" : "1";

            closeBtn.title = value
                ? "Please wait while your payment is processing"
                : "Close";

            busy.style.display = value ? "flex" : "none";
        };

        const close = (trigger = false) => {
            if (closed) return;

            closed = true;

            window.removeEventListener("message", messageHandler);
            document.removeEventListener("keydown", keyHandler);

            document.body.style.overflow = previousOverflow;

            overlay.remove();

            if (trigger) {
                closeCallback?.();
            }
        };

        /** User-initiated close — blocked while the payment is being finalised. */
        const requestClose = () => {
            if (isProcessing) return;

            close(true);
        };


        const complete = () => {
            if (completed) return;

            completed = true;

            window.removeEventListener("message", messageHandler);

            setProcessing(true);

            completionCallback?.();
        };


        const SUCCESS_STATES = [
            "verified",
            "completed",
            "success",
            "succeeded",
            "authenticated",
            "captured",
            "3ds-complete",
        ];

        const FAILURE_STATES = [
            "failed",
            "failure",
            "expired",
            "cancelled",
            "canceled",
            "denied",
            "rejected",
            "3ds-failed",
        ];

        const messageHandler = (event: MessageEvent) => {
            let data: any = event.data;

            if (!data) return;

            if (typeof data === "string") {
                try {
                    data = JSON.parse(data);
                } catch {
                    data = { status: data };
                }
            }

            if (typeof data !== "object") return;

            const signal = String(
                data.status ?? data.event ?? data.type ?? "",
            )
                .trim()
                .toLowerCase();

            if (!signal) return;

            if (completed || closed) return;

            if (SUCCESS_STATES.includes(signal)) {
                complete();
                return;
            }

            if (FAILURE_STATES.includes(signal)) {
                setProcessing(false);
                close(true);
                return;
            }

            console.debug("[3DS] unhandled status:", signal, data);
        };

        const keyHandler = (event: KeyboardEvent) => {
            if (event.key === "Escape") {
                requestClose();
            }
        };

        window.addEventListener("message", messageHandler);
        document.addEventListener("keydown", keyHandler);

        closeBtn.addEventListener("click", requestClose);

        overlay.addEventListener("click", (event) => {
            if (event.target === overlay) {
                requestClose();
            }
        });

        return {
            close,

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
