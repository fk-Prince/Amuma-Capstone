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


export const patientFields = [
    { key: "first_name", label: "First Name", type: "text", tab: 1 },
    { key: "last_name", label: "Last Name", type: "text", tab: 1 },
    { key: "gender", label: "Gender", type: "text", tab: 1 },
    { key: "date_of_birth", label: "Date of Birth", type: "text", tab: 2 },
    { key: "phone_number", label: "Phone Number", type: "text", tab: 2 },
    { key: "height", label: "Height (cm)", type: "text", tab: 3 },
    { key: "weight", label: "Weight (kg)", type: "text", tab: 3 },
    { key: "blood_type", label: "Blood Type", type: "text", tab: 3 },
]

