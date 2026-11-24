import * as faceapi from "@vladmandic/face-api";

type Source = Blob | string;

type Face = { score: number; area: number; descriptor: Float32Array };

export type FaceWorkerRequest =
    | { id: number; type: "warm" }
    | { id: number; type: "scan"; key: string; source: Source }
    | {
          id: number;
          type: "compare";
          idFront: { key: string; source: Source };
          selfie: { key: string; source: Source };
      };

const MODEL_URL = `${self.location.origin}/models/face`;
const MAX_SIDE = 960;
const CACHE_LIMIT = 8;

class Unavailable {}

faceapi.env.setEnv({
    Canvas: OffscreenCanvas,
    CanvasRenderingContext2D: OffscreenCanvasRenderingContext2D,
    Image: Unavailable,
    ImageData,
    Video: Unavailable,
    createCanvasElement: () => new OffscreenCanvas(1, 1),
    createImageElement: () => {
        throw new Error("Images are not available in a worker");
    },
    createVideoElement: () => {
        throw new Error("Video is not available in a worker");
    },
    fetch: (url: string, init?: RequestInit) => fetch(url, init),
    readFile: () => {
        throw new Error("Files are not available in a worker");
    },
} as unknown as faceapi.Environment);

const tf = faceapi.tf as unknown as {
    setBackend: (name: string) => Promise<boolean>;
    ready: () => Promise<void>;
};

let models: Promise<void> | null = null;

function loadModels(): Promise<void> {
    models ??= (async () => {
        let backend = false;
        try {
            backend = await tf.setBackend("webgl");
        } catch {
            backend = false;
        }
        if (!backend) await tf.setBackend("cpu");
        await tf.ready();

        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
    })().catch((err) => {
        models = null;
        throw err;
    });

    return models;
}

async function toCanvas(source: Source): Promise<OffscreenCanvas> {
    const blob =
        typeof source === "string"
            ? await (await fetch(source, { mode: "cors" })).blob()
            : source;
    const bitmap = await createImageBitmap(blob, {
        imageOrientation: "from-image",
    } as ImageBitmapOptions);

    const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
    const canvas = new OffscreenCanvas(
        Math.max(1, Math.round(bitmap.width * scale)),
        Math.max(1, Math.round(bitmap.height * scale)),
    );

    canvas.getContext("2d")!.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    return canvas;
}

async function runDetection(input: OffscreenCanvas): Promise<Face[]> {
    const faces = await faceapi
        .detectAllFaces(
            input as unknown as HTMLCanvasElement,
            new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }),
        )
        .withFaceLandmarks()
        .withFaceDescriptors();

    return faces
        .map((face) => ({
            score: face.detection.score,
            area: face.detection.box.area,
            descriptor: face.descriptor,
        }))
        .sort((a, b) => b.area - a.area);
}

const detections = new Map<string, Promise<Face[]>>();

function facesOf(key: string, source: Source): Promise<Face[]> {
    const existing = detections.get(key);
    if (existing) return existing;

    const created = (async () => {
        await loadModels();
        return runDetection(await toCanvas(source));
    })().catch((err) => {
        detections.delete(key);
        throw err;
    });

    detections.set(key, created);
    if (detections.size > CACHE_LIMIT) {
        detections.delete(detections.keys().next().value!);
    }

    return created;
}

const largest = (faces: Face[]) => faces.find((face) => face.score >= 0.4) ?? null;

async function warm() {
    await loadModels();
    await runDetection(new OffscreenCanvas(64, 64));
}

async function scan(key: string, source: Source) {
    const [face, idPhoto] = await facesOf(key, source);

    if (!face || face.score < 0.5) return { status: "no_face" };
    if (!idPhoto || idPhoto.area > face.area * 0.5) return { status: "no_id" };

    return { status: "face" };
}

async function compare(
    idFront: { key: string; source: Source },
    selfie: { key: string; source: Source },
) {
    const idFace = largest(await facesOf(idFront.key, idFront.source));
    if (!idFace) return { status: "no_face", which: "id" };

    const selfieFace = largest(await facesOf(selfie.key, selfie.source));
    if (!selfieFace) return { status: "no_face", which: "selfie" };

    const distance = faceapi.euclideanDistance(idFace.descriptor, selfieFace.descriptor);
    const score = Math.round(Math.max(0, Math.min(100, (1 - distance / 1.2) * 100)));

    return { status: "match", score };
}

const scope = self as unknown as {
    onmessage: ((event: MessageEvent<FaceWorkerRequest>) => void) | null;
    postMessage: (message: unknown) => void;
};

scope.onmessage = async (event) => {
    const request = event.data;

    try {
        const result =
            request.type === "warm"
                ? await warm()
                : request.type === "scan"
                  ? await scan(request.key, request.source)
                  : await compare(request.idFront, request.selfie);

        scope.postMessage({ id: request.id, ok: true, result });
    } catch (err) {
        scope.postMessage({
            id: request.id,
            ok: false,
            error: err instanceof Error ? err.message : String(err),
        });
    }
};
