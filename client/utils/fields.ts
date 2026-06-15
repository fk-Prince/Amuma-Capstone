export const branchFields: Array<{ key: string; label: string; type: string }> = [
    { key: "name", label: "Branch Name", type: "text" },
    { key: "description", label: "Description", type: "text" },
    { key: "contact_number", label: "Contact Number", type: "text" },
    { key: "address", label: "Address", type: "computed" },
    { key: "hours", label: "Business Hours", type: "computed" },
    { key: "currency", label: "Currency", type: "text" },
];

export const agencyFields: Array<{ key: string; label: string; type: string }> = [
    { key: "name", label: "Agency Name", type: "text" },
    { key: "description", label: "Description", type: "text" },
    { key: "address", label: "Address", type: "computed" },
];