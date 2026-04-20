<script setup lang="ts">
const emitters = defineEmits(['onClose', 'changeView'])
const { connect, changeView } = useWalletConnect()

const displayWalletConnect = ref(false)

const onChangeView = (_view: string) => emitters('changeView', _view)
watch(changeView, () => onChangeView(changeView.value))
</script>

<template>
  <v-row>
    <v-col cols="12" class="d-flex justify-center align-center pa-0 pt-2 position-relative mb-2">
      <IconLogo />

      <v-btn size="x-small" class="overlay-close" @click.prevent="emitters('onClose')"
        ><IconClose
      /></v-btn>
    </v-col>
    <v-col cols="12" v-if="displayWalletConnect">
      <p class="cta">Sign up with WalletConnect. Instant registration</p>
    </v-col>
    <v-col cols="12" class="d-flex justify-center" v-if="displayWalletConnect">
      <ConnectWalletButton address="">
        <template #connectWalletButton>
          <v-btn color="primary" @click.prevent="connect">
            <template #prepend><IconWalletConnect /></template>
            Wallet Connect
          </v-btn>
        </template>
        <template #spinner>
          <span class="rotate">
            <svg class="MuiCircularProgress-svg css-13o7eu2" viewBox="22 22 44 44">
              <circle
                class="MuiCircularProgress-circle MuiCircularProgress-circleIndeterminate css-14891ef"
                cx="44"
                cy="44"
                r="20.2"
                fill="none"
                stroke-width="3.6"
              />
            </svg>
          </span>
        </template>
      </ConnectWalletButton>
    </v-col>
    <v-col cols="12" class="pt-1 pb-1" v-if="displayWalletConnect">
      <p class="cta">-OR-</p>
    </v-col>
  </v-row>
</template>
<style>
@keyframes rotate {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.rotate {
  animation: rotate;
}
</style>
