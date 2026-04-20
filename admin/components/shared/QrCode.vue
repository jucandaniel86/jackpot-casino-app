<script setup lang="ts">
import QRCode from "qrcode";
const props = defineProps<{ text: string }>();
const dataUrl = ref<string>("");

watchEffect(async () => {
  dataUrl.value = await QRCode.toDataURL(props.text, { margin: 1, width: 220 });
});
</script>

<template>
  <img v-if="dataUrl" :src="dataUrl" alt="qr" />
</template>
