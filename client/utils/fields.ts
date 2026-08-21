export const branchFields: Array<{ key: string; label: string; type: string }> = [
    { key: "name", label: "Branch Name", type: "text" },
    { key: "email", label: "Email", type: "computed" },
    { key: "description", label: "Description", type: "text" },
    { key: "contact_number", label: "Contact Number", type: "text" },
    { key: "address", label: "Primary Address", type: "computed" },
];

export const agencyFields = [
    { key: "name", label: "Agency Name", type: "text" },
    { key: "email", label: "Email", type: "computed" },
    { key: "description", label: "Description", type: "text" },
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



export const Field = (
    fieldProps: { label: string; value: any },
    { slots, attrs }: any,
) =>
    h(
        "p",
        {
            ...attrs,
            class: ["flex flex-col gap-0.5 capitalize", attrs.class],
        },
        [
            h(
                "span",
                { class: "text-xs text-[#6B8A87]" },
                fieldProps.label,
            ),
            h(
                "span",
                { class: "text-[#16302E] font-medium" },
                slots.value ? slots.value() : (fieldProps.value ?? "—"),
            ),
        ],
    );

Field.props = ["label", "value"];