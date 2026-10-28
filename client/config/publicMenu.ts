import { Home, Package, CalendarCheck, BookOpen, Building2, Info, Star, LifeBuoy } from "lucide-vue-next";

export const navList = [
    { label: "Home", to: "/", icon: Home },
    { label: "Product", to: "/product", icon: Package },
    { label: "Booking", to: "/booking", icon: CalendarCheck },
    {
        label: "Resources",
        to: "/resources",
        icon: BookOpen,
        children: [
            {
                label: "Resources",
                to: "/resources",
                icon: BookOpen,
                description: "Healthcare guides, caregiver resources, FAQs, and support materials",
            },
            {
                label: "Customer Support",
                to: "/resources/support",
                icon: LifeBuoy,
                description: "Send a question or report a problem to the AMUMA team",
            },
        ],
    },
    {
        label: "Company",
        to: "/company",
        icon: Building2,
        children: [
            {
                label: "About Us",
                to: "/company",
                icon: Info,
                description: "Our story, partner agencies, and the team behind AMUMA",
            },
            {
                label: "Reviews",
                to: "/company/reviews",
                icon: Star,
                description: "Ratings and feedback from people who use AMUMA",
            },
        ],
    },
]


export const providerNavList: typeof navList = []
