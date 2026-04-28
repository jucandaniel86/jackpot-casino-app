<!-- eslint-disable vue/no-multiple-template-root -->
<!-- eslint-disable vue/no-v-html -->
<!-- eslint-disable vue/no-v-text-v-html-on-component -->
<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useAPIFetch } from '~/composables/useApiFetch'
import { useAppStore } from '~/core/store/app'
import type { ContainerType } from '~/core/types/Container'
import { useSeoSettings } from '~/composables/useSeoSettings'
import { useSidebarStore } from '~/core/store/sidebar'

useHead({
  title: 'Jackpot Casino',
})

definePageMeta({
  middleware: 'auth',
})

//models
const content = ref<ContainerType[]>([])
const seoSettings = ref<unknown>(null)

useSeoSettings(seoSettings)
const { name } = useDisplay()

const { setPageLoading } = useAppStore()
const { setSidebar } = useSidebarStore()

const loadPage = async (page: string) => {
  setPageLoading(true)
  const { data: data }: any = await useAsyncData('page', () => useAPIFetch('/page/' + page))

  if (data) {
    content.value = data.value.children.main
    setSidebar(data.value.children.leftSidebar)
    seoSettings.value = data.value.seo ?? null
  }
  setPageLoading(false)
}

loadPage('home')
</script>
<template>
  <Container :content="content" />
</template>
