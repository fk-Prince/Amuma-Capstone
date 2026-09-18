import type { PermissionActionKey } from "~/utils/permissions";

export interface Permissions {
    module_name: string,
    actions: PermissionActionKey[],
}
