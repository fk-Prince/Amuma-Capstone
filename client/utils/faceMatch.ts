export const FACE_MATCH_PASS = 50;

const MODEL_URL = "/models/face";

export type FaceMatchResult =
    | { status: "match"; score: number }
    | { status: "no_face"; which: "id" | "selfie" }
    | { status: "error" };

export type FaceScanResult =
    | { status: "face" }
    | { status: "no_face" }
    | { status: "no_id" }
    | { status: "error" };

type FaceApi = typeof import("@vladmandic/face-api");

let detector: Promise<FaceApi> | null = null;
let recognizer: Promise<FaceApi> | null = null;

function loadDetector(): Promise<FaceApi> {
    detector ??= (async () => {
        const faceapi = await import("@vladmandic/face-api");
        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        return faceapi;
    })().catch((err) => {
        detector = null;
        throw err;
    });

    return detector;
}

function loadFaceApi(): Promise<FaceApi> {
    recognizer ??= (async () => {
        const faceapi = await loadDetector();

        await Promise.all([
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);

        return faceapi;
    })().catch((err) => {
        recognizer = null;
        throw err;
    });

    return recognizer;
}

async function toImage(source: File | string): Promise<HTMLImageElement> {
    const url = typeof source === "string" ? source : URL.createObjectURL(source);
    const image = new Image();

    image.crossOrigin = "anonymous";
    image.src = url;

    try {
        await image.decode();
    } finally {
        if (typeof source !== "string") URL.revokeObjectURL(url);
    }

    return image;
}

async function largestFace(faceapi: FaceApi, image: HTMLImageElement) {
    const faces = await faceapi
        .detectAllFaces(image, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.4 }))
        .withFaceLandmarks()
        .withFaceDescriptors();

    if (!faces.length) return null;

    return faces.reduce((best, face) =>
        face.detection.box.area > best.detection.box.area ? face : best,
    );
}

export async function scanFace(source: File | string | HTMLVideoElement): Promise<FaceScanResult> {
    try {
        const faceapi = await loadDetector();
        const image = source instanceof HTMLVideoElement ? source : await toImage(source);
        const faces = (
            await faceapi.detectAllFaces(image, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
        ).sort((a, b) => b.box.area - a.box.area);

        const [face, idPhoto] = faces;

        if (!face || face.score < 0.5) return { status: "no_face" };
        if (!idPhoto || idPhoto.box.area > face.box.area * 0.5) return { status: "no_id" };

        return { status: "face" };
    } catch (err) {
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
    return "We couldn't find a face in the photo. Retake it with your face and ID both visible.";
}

export async function compareFaces(
    idFront: File | string,
    selfie: File | string,
): Promise<FaceMatchResult> {
    try {
        const faceapi = await loadFaceApi();
        const [idImage, selfieImage] = await Promise.all([toImage(idFront), toImage(selfie)]);

        const idFace = await largestFace(faceapi, idImage);
        if (!idFace) return { status: "no_face", which: "id" };

        const selfieFace = await largestFace(faceapi, selfieImage);
        if (!selfieFace) return { status: "no_face", which: "selfie" };

        const distance = faceapi.euclideanDistance(idFace.descriptor, selfieFace.descriptor);
        const score = Math.round(Math.max(0, Math.min(100, (1 - distance / 1.2) * 100)));

        return { status: "match", score };
    } catch (err) {
        console.error("Face match failed", err);
        return { status: "error" };
    }
}
