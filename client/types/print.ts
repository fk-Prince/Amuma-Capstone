export interface PrintBranch {
    name: string | null;
    image?: string | null;
    address?: string | null;
    contact_number?: string | null;
    email?: string | null;
    tin?: string | null;
    agency_name?: string | null;
}

export interface PrintLine {
    label: string;
    value?: string | null;
    strong?: boolean;
}
