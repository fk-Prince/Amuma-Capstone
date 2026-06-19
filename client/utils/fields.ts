export const branchFields: Array<{ key: string; label: string; type: string }> = [
    { key: "name", label: "Branch Name", type: "text" },
    { key: "description", label: "Description", type: "text" },
    { key: "contact_number", label: "Contact Number", type: "text" },
    { key: "address", label: "Primary Address", type: "computed" },
];

export const agencyFields = [
    { key: "agency_name", label: "Agency Name", type: "text" },
    { key: "agency_description", label: "Description", type: "text" },
    { key: "address", label: "Primary Address", type: "computed" },
];