import { Home, Package, CalendarCheck, BookOpen, Building2, Info, Star } from "lucide-vue-next";

export const navList = [
    { label: "Home", to: "/", icon: Home },
    { label: "Product", to: "/product", icon: Package },
    { label: "Booking", to: "/booking", icon: CalendarCheck },
    { label: "Resources", to: "/resources", icon: BookOpen },
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
