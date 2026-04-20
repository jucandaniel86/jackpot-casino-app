<script setup lang="ts">
defineProps(['error'])
const handleError = () => clearError({ redirect: '/' })
</script>

<template>
  <div class="container">
    <div class="column-left">
      <h2>{{ error?.statusMessage }}</h2>
      <p>{{ error?.message }}</p>
      <v-btn color="purple" @click.prevent="handleError">Back to homepage</v-btn>
    </div>
    <div class="column-right">
      <v-img src="@/assets/imgs/404-Page.png" />
    </div>
  </div>
</template>
<style scoped>
* {
  margin: 0px;
  padding: 0px;
  font-size: 22px;
  background-color: #18181b;
  color: #fff;
  box-sizing: border-box;
  padding-bottom: 20px;
}
.container {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  padding: 0px 100px;
}
.column-left {
  width: 50%;
}
.column-right {
  width: 50%;
  display: flex;
  justify-content: center;
}
h1,
h2 {
  color: #fff;
  font-size: 40px;
  margin-bottom: 20px;
}
</style>
