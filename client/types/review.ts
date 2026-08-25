import type { User } from "./auth";

export interface Review {
    review_id: number;
    branch_id: number;
    rate: number;
    description: string;
    image?: string | null;
    created_at: string;
    updated_at: string;
    user: User
}

export interface ReviewListResponse {
    data: Review[];
    average_rating: number | null;
    with_comments_count: number | null;
    rating_breakdown: 1 | 2 | 3 | 4 | 5;
    meta?: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
}