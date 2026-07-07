<script setup>
import { ref } from "vue";
import { notificationService } from "~/api/notification/NotificationService";

const file = ref(null);
const loading = ref(false);
const result = ref(null);

const onFileChange = (e) => {
    file.value = e.target.files[0];
};

const uploadImage = async () => {
    if (!file.value) return alert("Please select an image");

    loading.value = true;
    const formData = new FormData();
    formData.append("name", "Sample Product");
    formData.append("image", file.value);
    console.log(file.value);
    try {
        const res = await notificationService.createsample(formData);
        console.log(res);
        result.value = res;
    } catch (err) {
        console.error(err);
        alert("Upload failed");
    }

    loading.value = false;
};
</script>

<template>
    <div style="padding: 20px">
        <h2>Upload Image</h2>

        <input type="file" accept="image/*" @change="onFileChange" />

        <br /><br />

        <button @click="uploadImage" :disabled="loading">
            {{ loading ? "Uploading..." : "Upload" }}
        </button>

        <div v-if="result" style="margin-top: 20px">
            <p><b>Name:</b> {{ result.name }}</p>
            <img :src="result.image" width="200" />
        </div>
    </div>
</template>
