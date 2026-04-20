<!-- eslint-disable vue/no-v-html -->
<script setup lang="ts">
const icons = ref()

const getIcons = async (): Promise<void> => {
  const { data } = await useAPIFetch('/icons')
  icons.value = data
}

onMounted(() => {
  getIcons()
})
</script>
<template>
  <div style="display: none">
    <svg xmlns="http://www.w3.org/2000/svg">
      <symbol id="copy" viewBox="0 0 24 24">
        <path
          d="M16 1H6C4.9 1 4 1.9 4 3V17H6V3H16V1ZM19 5H10C8.9 5 8 5.9 8 7V21C8 22.1 8.9 23 10 23H19C20.1 23 21 22.1 21 21V7C21 5.9 20.1 5 19 5ZM19 21H10V7H19V21Z"
        />
      </symbol>
    </svg>
    <span v-for="icon in icons" :key="icon.label" v-html="icon.icon"> </span>
  </div>
</template>
<style scoped>
.cls-18880 {
  fill-rule: evenodd;
  stroke-width: 0px;
}
.cls-110656 {
  fill-rule: evenodd;
}
.cls-17326 {
  fill-rule: evenodd;
}
.cls-1777 {
  fill-rule: evenodd;
}
</style>
