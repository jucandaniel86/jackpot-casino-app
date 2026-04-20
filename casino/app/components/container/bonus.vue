<!-- eslint-disable vue/no-v-html -->
<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'
import { OverlaysTypes } from '~/core/types/Overlays'

const { options } = defineProps<{ options: ContainerType }>()
const { display } = useContainerOptions(options)
const { openOverlay } = useUtils()

const currencies = computed(() => options.data.currencies)
const payments = computed(() => options.data.payment)

//methods
const openWalletOverlay = () => openOverlay(OverlaysTypes.WALLET)
</script>
<template>
  <div v-if="display" :id="options.id" class="bonus-container-wrap">
    <div class="title-container">
      <div class="d-flex align-center flex-wrap">
        <img alt="Bonus Icon" :src="options.data.bonusIcon" style="width: auto; height: 35px" />
        <span class="bonus-title" v-html="options.data.title" />
        <span style="padding-right: 60px" class="d-flex mt-1 justify-center align-center ga-1">
          <img
            v-for="(payment, i) in payments"
            :key="`Payment${i}`"
            :src="payment"
            :style="{
              width: '42px',
              height: '28px',
            }"
          />
        </span>
        <span class="mt-1 d-flex justify-center align-center ga-2">
          <img
            v-for="(currency, i) in currencies"
            :key="`Currency${i}`"
            :src="currency"
            style="width: auto; height: auto"
          />
        </span>
      </div>
    </div>
    <div class="deposit-now-wrapper">
      <v-btn color="purple" @click.prevent="openWalletOverlay">Deposit Now</v-btn>
    </div>
  </div>
</template>
<style scoped>
.bonus-container-wrap {
  display: flex;
  align-items: center;
  justify-content: space-evenly;
  padding: 10px !important;
  border-radius: 8px !important;
  box-sizing: border-box !important;
  border: 1px solid rgb(73, 79, 101) !important;
  background: linear-gradient(
    rgba(27, 42, 65, 0.7) -0.26%,
    rgba(71, 81, 112, 0.7) 99.74%
  ) !important;
  color: #fff;
  overflow-wrap: break-word;
}

.bonus-container-wrap .title-container {
  width: auto;
  box-sizing: border-box;
  display: grid;
  grid-template-columns: 100%;
  grid-template-rows: 100%;
  flex: 1 1 0%;
}

.bonus-title {
  font-size: 18px;
  font-weight: 800;
  padding-right: 30px;
  height: 22px;
}

.deposit-now-wrapper {
  width: auto;
  box-sizing: border-box;
  display: grid;
  grid-template-columns: 100%;
  grid-template-rows: 100%;
}
</style>
