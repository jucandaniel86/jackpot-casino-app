<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { createError, definePageMeta, showError, useRoute, useRouter } from '#imports'
import { useAppStore } from '~/core/store/app'
import { useAPIFetch } from '~/composables/useApiFetch'
import { useSeoContainer } from '~/composables/useSeoContainer'

const { setPageLoading, setSidebar } = useAppStore()
const renderID = ref('')
const { back } = useRouter()
const data = ref()
const route = useRoute()

const loadPage = async (): Promise<void> => {
  setPageLoading(true)
  const route = useRoute()
  const page = route.params.slug
  const pageData: any = await useAPIFetch('/provider/' + page)

  if (pageData.status && pageData.status === 404) {
    setPageLoading(false)
    if (import.meta.client) {
      throw showError({
        statusCode: 404,
        statusMessage: 'Uh oh, Page not Found',
        message: "Sorry the page you were looking for doesn't exist or has been moved.",
        fatal: true,
      })
    } else {
      throw createError({
        statusCode: 404,
        statusMessage: 'Uh oh, Page not Found',
        message: "Sorry the page you were looking for doesn't exist or has been moved.",
        fatal: true,
      })
    }
  }

  if (pageData && pageData.seo) {
    useSeoContainer(pageData.seo)
  }

  data.value = pageData
  renderID.value = page + '_' + new Date().getTime()
  setSidebar(data.value.children.leftSidebar)
  setPageLoading(false)
}

watch(
  route,
  () => {
    loadPage()
  },
  { deep: true },
)

onMounted(() => {
  loadPage()
})

definePageMeta({
  middleware: 'auth',
})
</script>
<template>
  <div>
    <div class="d-flex justify-start align-center mt-2 mb-1">
      <v-btn
        prepend-icon="mdi-arrow-left"
        class="back-btn"
        density="compact"
        text
        @click.prevent="back"
        >Back</v-btn
      >
    </div>

    <Container v-if="data" :key="renderID" :content="data.children.main" />
  </div>
</template>
