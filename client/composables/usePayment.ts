import { use3DS } from "~/composables/use3DS";

type CardPaymentOptions = {
    card: CardDetails;
    amount: number;
    createPayment: (data: {
        token_id: string;
        authentication_id: string;
    }) => Promise<any>;
    onSuccess?: (result: any) => Promise<void>;
    onClose?: () => void;
};

type GCashPaymentOptions = {
    createPayment: () => Promise<any>;
    closeModal?: Ref<(() => void) | null>;
    onSuccess?: (result: any) => Promise<void>;
    onClose?: () => void;
};


export async function cardPayment({
    card,
    amount,
    createPayment,
    onSuccess,
    onClose,
}: CardPaymentOptions) {
    const config = useRuntimeConfig();
    const { handle3DS } = use3DS();

    window.Xendit.setPublishableKey(
        config.public.xenditPublicKey,
    );

    const cardData = {
        amount,
        card_number: card.number.replace(/\s/g, ""),
        card_exp_month: String(card.expMonth),
        card_exp_year: String(
            Math.floor(new Date().getFullYear() / 100) * 100 +
            Number(card.expYear),
        ),
        card_cvc: card.cvc,
        card_holder_first_name: card.firstName,
        card_holder_last_name: card.lastName,
        card_holder_email: card.email,
    };


    return new Promise((resolve, reject) => {
        window.Xendit.card.createToken(
            {
                ...cardData,
                is_multiple_use: false,
                should_authenticate: true,
            },

            (err: any, token: any) => {
                if (err) {
                    reject(err);
                    return;
                }


                window.Xendit.card.createAuthentication(
                    {
                        token_id: token.id,
                        amount,
                    },

                    async (err: any, auth: any) => {
                        if (err) {
                            reject(err);
                            return;
                        }


                        let popupClose: (() => void) | null = null;


                        const executePayment = async () => {
                            return await createPayment({
                                token_id: token.id,
                                authentication_id: auth.id,
                            });
                        };


                        const finish = async (result: any) => {
                            popupClose?.();

                            if (onSuccess) {
                                await onSuccess(result);
                            }

                            resolve(result);
                        };


                        try {
                            if (
                                auth.status === "IN_REVIEW" &&
                                auth.payer_authentication_url
                            ) {
                                const {
                                    close,
                                    onComplete,
                                    onClose: on3DSClose,
                                } = handle3DS(
                                    auth.payer_authentication_url,
                                );


                                popupClose = close;


                                on3DSClose(() => {
                                    popupClose?.();

                                    onClose?.();

                                    reject(
                                        new Error("Payment Cancelled."),
                                    );
                                });


                                onComplete(async () => {
                                    try {
                                        const result =
                                            await executePayment();

                                        await finish(result);

                                    } catch (error) {
                                        popupClose?.();
                                        reject(error);
                                    }
                                });


                                return;
                            }


                            if (auth.status === "VERIFIED") {
                                const result =
                                    await executePayment();

                                await finish(result);

                                return;
                            }


                            reject(
                                new Error(
                                    `Unhandled auth status: ${auth.status}`,
                                ),
                            );

                        } catch (error) {
                            popupClose?.();
                            reject(error);
                        }
                    },
                );
            },
        );
    });
}


export async function gcashPayment({
    createPayment,
    closeModal,
    onSuccess,
    onClose,
}: GCashPaymentOptions) {
    const { handle3DS } = use3DS();

    const res = await createPayment();

    const url = res?.invoice_url;

    if (!url) {
        throw new Error("Payment URL not found.");
    }

    return new Promise((resolve, reject) => {
        const {
            close,
            onComplete,
            onClose: on3DSClose,
        } = handle3DS(
            url,
            "GCash Payment",
        );


        if (closeModal) {
            closeModal.value = close;
        }

        const finish = async (result: any) => {
            close();

            if (onSuccess) {
                await onSuccess(result);
            }

            resolve(result);
        };


        on3DSClose(() => {
            close();

            onClose?.();

            reject(
                new Error("Payment Cancelled."),
            );
        });


        onComplete(async () => {
            try {
                await finish(res);
            } catch (error) {
                close();
                reject(error);
            }
        });
    });
}