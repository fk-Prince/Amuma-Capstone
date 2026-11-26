import type { FaceWorkerRequest } from "~/workers/faceMatch.worker";

export const FACE_MATCH_PASS = 50;

export type FaceMatchResult =
    | { status: "match"; score: number }
    | { status: "no_face"; which: "id" | "selfie" }
    | { status: "error" };

export type FaceScanResult =
    | { status: "face" }
    | { status: "no_face" }
    | { status: "no_id" }
    | { status: "error" };

type Source = File | string;

type DistributiveOmit<T, K extends PropertyKey> = T extends unknown ? Omit<T, K> : never;

let worker: Worker | null = null;
let nextId = 0;
const pending = new Map<number, { resolve: (value: any) => void; reject: (err: Error) => void }>();

function failAll(message: string) {
    for (const { reject } of pending.values()) reject(new Error(message));
    pending.clear();
}

function getWorker(): Worker {
    if (worker) return worker;

    worker = new Worker(new URL("../workers/faceMatch.worker.ts", import.meta.url), {
        type: "module",
    });

    worker.onmessage = (event) => {
        const { id, ok, result, error } = event.data;
        const call = pending.get(id);
        if (!call) return;

        pending.delete(id);
        if (ok) call.resolve(result);
        else call.reject(new Error(error));
    };

    worker.onerror = (event) => {
        event.preventDefault();
        worker?.terminate();
        worker = null;
        failAll(event.message || "Face check worker failed");
    };

    return worker;
}

const FACE_CHECK_TIMEOUT_MS = 15_000;

class FaceCheckTimeout extends Error { }

function call<T>(request: DistributiveOmit<FaceWorkerRequest, "id">, timeoutMs?: number): Promise<T> {
    if (import.meta.server || typeof Worker === "undefined") {
        return Promise.reject(new Error("Face checks need a browser"));
    }

    const id = ++nextId;

    return new Promise<T>((resolve, reject) => {
        const timer = timeoutMs
            ? setTimeout(() => {
                pending.delete(id);
                reject(new FaceCheckTimeout());
            }, timeoutMs)
            : undefined;

        pending.set(id, {
            resolve: (value) => {
                clearTimeout(timer);
                resolve(value);
            },
            reject: (err) => {
                clearTimeout(timer);
                reject(err);
            },
        });
        getWorker().postMessage({ ...request, id });
    });
}

const fileKeys = new WeakMap<File, string>();
let nextFileKey = 0;

function describe(source: Source) {
    if (typeof source === "string") {
        const url = new URL(source, window.location.href).href;
        return { key: url, source: url };
    }

    let key = fileKeys.get(source);
    if (!key) {
        key = `file:${++nextFileKey}`;
        fileKeys.set(source, key);
    }

    return { key, source };
}

let warming: Promise<void> | null = null;

export function warmUpFaceModels(): Promise<void> {
    warming ??= call<void>({ type: "warm" }).catch(() => {
        warming = null;
    });

    return warming;
}

export async function scanFace(source: Source): Promise<FaceScanResult | null> {
    try {
        return await call<FaceScanResult>(
            { type: "scan", ...describe(source) },
            FACE_CHECK_TIMEOUT_MS,
        );
    } catch (err) {
        if (err instanceof FaceCheckTimeout) return null;
        console.error("Face scan failed", err);
        return { status: "error" };
    }
}

export function faceScanMessage(result?: FaceScanResult | null): string {
    if (!result || result.status === "face") return "";
    if (result.status === "error") return "We couldn't check the photo. Try uploading it again.";
    if (result.status === "no_id") {
        return "We can't see the ID in the photo. Hold the ID next to your face with its photo facing the camera.";
    }
    return "";
}

export async function compareFaces(idFront: Source, selfie: Source): Promise<FaceMatchResult | null> {
    try {
        return await call<FaceMatchResult>(
            {
                type: "compare",
                idFront: describe(idFront),
                selfie: describe(selfie),
            },
            FACE_CHECK_TIMEOUT_MS,
        );
    } catch (err) {
        if (err instanceof FaceCheckTimeout) return null;
        console.error("Face match failed", err);
        return { status: "error" };
    }
}
