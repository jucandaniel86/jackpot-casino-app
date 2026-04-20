<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useAppStore } from '~/core/store/app'
import { useSidebarStore } from '~/core/store/sidebar'
import { useSeoSettings } from '~/composables/useSeoSettings'

const { setPageLoading } = useAppStore()
const { setSidebar } = useSidebarStore()
const renderID = ref('')
const data = ref()
const route = useRouter()
const seoSettings = computed(() => data.value?.seo ?? null)
const { name } = useDisplay()

useSeoSettings(seoSettings)

const isMobile = computed(() => ['sm', 'xs'].indexOf(name) !== -1)

const loadPage = async (): Promise<void> => {
  setPageLoading(true)
  const route = useRoute()
  const page = route.params.slug
  const pageData: any = await useAPIFetch('/page/' + page)

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

  data.value = pageData
  renderID.value = page + '_' + new Date().getTime()
  if (data.value.children.leftSidebar) {
    setSidebar(data.value.children.leftSidebar)
  }
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
  <Container v-if="data" :key="renderID" :content="data.children.main" />
</template>
