<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { ContainerSection, type ContainerType } from '~/core/types/Container'

const props = defineProps<{ options: ContainerType }>()
const { display } = useContainerOptions(props.options)

const currentTab = ref(props.options.data.tabs[0].internalName)
const currentContent = ref(props.options.children)
const disabled = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const lastTab = ref<string>()

const onTabsChanged = async (tag: string) => {
  if (tag === lastTab.value) return

  disabled.value = true
  isLoading.value = true

  const data: any = await useAPIFetch(`${props.options.data.fetchUrl}${tag}`)

  lastTab.value = tag
  currentContent.value = data
  disabled.value = false
  isLoading.value = false
}
</script>
<template>
  <v-tabs
    v-if="display"
    :id="options.id"
    :key="options.id"
    v-model="currentTab"
    align-tabs="start"
    :disabled="disabled"
    class="mb-0"
  >
    <v-tab
      v-for="(tab, i) in options.data.tabs"
      :key="`Tab${i}`"
      :value="tab.internalName"
      @click.prevent="onTabsChanged(tab.internalName)"
    >
      <shared-icon-label :label="tab.tabName" :icon="tab.iconCode" :fill="'currentColor'" />
    </v-tab>
  </v-tabs>
  <div v-if="isLoading" class="d-flex mt-3 mb-3 justify-center align-center">
    <v-progress-circular color="purple" indeterminate />
  </div>

  <template v-for="(section, i) in currentContent" :key="`Container${i}`">
    <container-category
      v-if="section.container === ContainerSection.GAMES_CATEGORY"
      :key="section.id"
      :options="section"
    />
    <container-provider-logos
      v-if="section.container === ContainerSection.PROVIDER_LOGOS"
      :key="section.id"
      :options="section"
    />
    <container-categoryheadless
      v-if="section.container === ContainerSection.CATEGORY_HEADLESS"
      :key="section.id"
      :options="section"
    />
  </template>
</template>
