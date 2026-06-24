import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { type SubscriptionRequest } from "~/types/subscription";
import { use3DS } from "./use3DS";
const { handle3DS } = use3DS();


export interface CardDetails {
    number: string
    expMonth: string,
    expYear: string,
    cvc: string,
    firstName: string,
    lastName: string,
    email: string
}


export async function cardPayment(
    card: CardDetails,
    closeModal: any,
    subData: SubscriptionRequest
): Promise<any> {
    const config = useRuntimeConfig();
    window.Xendit.setPublishableKey(config.public.xenditPublicKey);
    const res = await subscriptionService.retrieveSubscriptionDetail(subData);


    if (!res.status) return;

    const cardData = {
        amount: Number(res.total_amount),
        card_number: card.number.toString().replace(/\s/g, ""),
        card_exp_month: String(card.expMonth),
        card_exp_year: String(
            Math.floor(new Date().getFullYear() / 100) * 100 + Number(card.expYear)
        ),
        card_cvc: card.cvc,
        card_holder_first_name: card.firstName,
        card_holder_last_name: card.lastName,
        card_holder_email: card.email,
    };

    return new Promise((resolve, reject) => {
        window.Xendit.card.createToken(
            { ...cardData, is_multiple_use: false, should_authenticate: true },
            async (err: any, token: any) => {
                if (err) return reject(err);

                window.Xendit.card.createAuthentication(
                    { token_id: token.id, amount: cardData.amount },
                    async (err: any, auth: any) => {
                        if (err) return reject(err);

                        let popupClose: (() => void) | null = null;

                        const executePayment = async () => {
                            return await subscriptionService.createSubscription({
                                token_id: token.id,
                                authentication_id: auth.id,
                                ...subData,
                            });
                        };

                        const finish = async (result: any) => {
                            popupClose?.();
                            closeModal.value?.();
                            await navigateTo({
                                path: "/product/subscription-summary",
                                query: { status: result.status },
                            });
                            resolve(result);
                        };

                        try {
                            if (auth.status === "IN_REVIEW" && auth.payer_authentication_url) {
                                const { close, onComplete } = handle3DS(auth.payer_authentication_url);
                                popupClose = close;
                                closeModal.value = close;

                                onComplete(async () => {
                                    try {
                                        const result = await executePayment();
                                        await finish(result);
                                    } catch (err) {
                                        popupClose?.();
                                        reject(err);
                                    }
                                });
                                return;
                            }

                            if (auth.status === "VERIFIED") {
                                const result = await executePayment();
                                await finish(result);
                                return;
                            }

                            reject(new Error(`Unhandled auth status: ${auth.status}`));

                        } catch (err) {
                            popupClose?.();
                            reject(err);
                        }
                    }
                );
            }
        );
    });
}
export async function gcashPayment(
    closeModal: Ref<(() => void) | null>,
    subData: SubscriptionRequest
): Promise<any> {
    const res = await subscriptionService.createSubscription(subData);
    const url = res?.invoice_url;

    if (!url) {
        console.error('No invoice URL returned');
        return;
    }

    const { close, onComplete } = handle3DS(url, "GCash Payment");
    closeModal.value = close;

    return new Promise((resolve, reject) => {
        onComplete(async () => {
            try {
                close();
                closeModal.value?.();
                resolve(res);
            } catch (err) {
                reject(err);
            }
        });
    });
}