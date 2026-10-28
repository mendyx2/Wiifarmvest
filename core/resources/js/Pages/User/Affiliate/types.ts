/** One affiliate order row (AffiliateController::presentOrder). */
export type AffiliateOrderRow = {
    id: number;
    code: string;
    buyer: string;
    total: string;
    payment: string;
    status: { label: string; class: string };
    date: string;
};
