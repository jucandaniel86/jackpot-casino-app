<script setup lang="ts">
const { success, error } = useAlerts()

const props = withDefaults(defineProps<{ address: string; size?: number }>(), {
  size: 160,
})

const qrUrl = computed(() => {
  const data = encodeURIComponent(props.address.trim())
  return `https://api.qrserver.com/v1/create-qr-code/?size=${props.size}x${props.size}&data=${data}`
})

const copyWalletAddress = async () => {
  if (!props.address) {
    error('No wallet address to copy')
    return
  }

  try {
    await navigator.clipboard.writeText(props.address)
    success('Wallet address copied')
  } catch {
    error('Failed to copy wallet address')
  }
}
</script>

<template>
  <div class="wallet-qr">
    <img :src="qrUrl" :alt="`QR code for ${props.address}`" loading="lazy" />
    <div class="wallet-qr-address" @click="copyWalletAddress">
      <span :title="props.address">{{ props.address }}</span>
      <div :style="{ display: 'flex', alignContent: 'center', height: '20px' }">
        <v-btn
          class="wallet-qr-copy-btn"
          flat
          icon
          variant="flat"
          width="20"
          height="20"
          size="x-small"
          :style="{ width: '26px !important', height: '26px !important' }"
        >
          <SharedIcon
            icon="copy"
            class="wallet-qr-copy-icon"
            :style="{ width: '16px', height: '16px' }"
          />
        </v-btn>
      </div>
    </div>
  </div>
</template>
