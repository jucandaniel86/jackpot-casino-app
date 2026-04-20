<script setup lang="ts">
type FormAmountType = {
  max?: number
  currency: string
  error?: string
}
//props
const props = defineProps<FormAmountType>()

//models
const model = ref<number>(0.0001)

//emitters
const emits = defineEmits(['onChange'])

//methods

//watchers
watch(model, () => {
  emits('onChange', model.value)
})
</script>
<template>
  <div class="mb-2">
    <div class="d-flex position-relative amount-wraper" :class="{ error: props.error }">
      <input v-model="model" type="number" />
      <div class="d-flex position-absolute align-center right-0 top-0 ga-2">
        <SharedIcon
          :icon="`currency-ico-${String(props.currency).toLowerCase()}`"
          class="svg-icon mt-1 mb-1"
        />
        <button v-if="props.max" class="max-button">MAX</button>
      </div>
    </div>
    <div v-if="props.error" class="error-message">{{ props.error }}</div>
  </div>
</template>
<style scoped>
.amount-wraper {
  height: 42px;
  background-color: #21242e;
  color: #e8e8e8;
  padding: 8px;
  overflow: hidden;
  font-weight: 400;
  border-radius: 6px;
}
.amount-wraper:hover {
  background-color: #2c303f;
}

.amount-wraper.error {
  border: 1px solid #ff2e2e;
}

.amount-wraper input {
  color: rgb(172, 176, 195);
  width: 100%;
}
.amount-wraper input:focus,
.amount-wraper input:hover {
  outline: none;
}
.max-button {
  background: rgb(110, 110, 110);
  cursor: pointer;
  border-radius: 9px;
  font-size: 14px;
  font-weight: 700;
  height: 42px;
  width: 80px;
  padding: 10px 13px;
}
.error-message {
  color: rgb(255, 46, 46);
  font-size: 14px;
  font-weight: 400;
  padding: 4px 8px 0px 8px;
  word-break: break-word;
}
</style>
