const controllers = new Set<AbortController>();

export const RequestManager = {
    create() {
        const controller = new AbortController();
        controllers.add(controller);

        return controller;
    },

    remove(controller: AbortController) {
        controllers.delete(controller);
    },

    abortAll() {
        controllers.forEach(controller => controller.abort());
        controllers.clear();
    }
};