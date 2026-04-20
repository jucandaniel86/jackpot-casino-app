<!-- nt-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { ContainerSection, type ContainerType } from '~/core/types/Container'

type ContainerComponentT = {
  content: ContainerType[]
}
const props = defineProps<ContainerComponentT>()
const currentContent = ref(props.content)

watch(props, () => {
  currentContent.value = props.content
})
</script>
<template>
  <div class="root-container">
    <template v-for="(section, i) in currentContent" :key="`Container${i}`">
      <container-column
        v-if="section.container === ContainerSection.COLUMN"
        :key="section.id"
        :options="section"
      />
      <container-flex
        v-if="section.container === ContainerSection.FLEX_COLUMN"
        :key="section.id"
        :options="section"
      />
      <container-button
        v-if="section.container === ContainerSection.BUTTON"
        :key="section.id"
        :options="section"
      />
      <container-flex-row
        v-if="section.container === ContainerSection.FLEX_ROW"
        :key="section.id"
        :options="section"
      />
      <container-slider
        v-if="section.container === ContainerSection.SLIDER"
        :key="section.id"
        :options="section"
      />
      <container-category
        v-if="section.container === ContainerSection.GAMES_CATEGORY"
        :key="section.id"
        :options="section"
      />
      <container-betfeed
        v-if="section.container === ContainerSection.BET_FEED"
        :key="section.id"
        :options="section"
      />
      <container-seo
        v-if="section.container === ContainerSection.SEO"
        :key="section.id"
        :options="section"
      />
      <container-html
        v-if="section.container === ContainerSection.HTML"
        :key="section.id"
        :options="section"
      />
      <container-banner
        v-if="section.container === ContainerSection.BANNER"
        :key="section.id"
        :options="section"
      />
      <container-search
        v-if="section.container === ContainerSection.SEARCH"
        :key="section.id"
        :options="section"
      />
      <container-tabs
        v-if="section.container === ContainerSection.TABS"
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
      <container-categorydetails
        v-if="section.container === ContainerSection.CATEGORY_DETAILS"
        :key="section.id"
        :options="section"
      />
      <container-accordion
        v-if="section.container === ContainerSection.ACCORDION"
        :key="section.id"
        :options="section"
      />
      <container-promotions
        v-if="section.container === ContainerSection.PROMOTIONS"
        :key="section.id"
        :options="section"
      />
      <container-promotion-image
        v-if="section.container === ContainerSection.PROMO_IMAGE"
        :key="section.id"
        :options="section"
      />
      <container-promotion-title
        v-if="section.container === ContainerSection.PROMO_TITLE"
        :key="section.id"
        :options="section"
      />
      <container-promotion-subtitle
        v-if="section.container === ContainerSection.PROMO_SUBTITLE"
        :key="section.id"
        :options="section"
      />
      <container-promotion-description
        v-if="section.container === ContainerSection.PROMO_DESCRIPTION"
        :key="section.id"
        :options="section"
      />
      <container-promotion-button
        v-if="section.container === ContainerSection.PROMO_BUTTON"
        :key="section.id"
        :options="section"
      />
      <container-logos
        v-if="section.container === ContainerSection.LOGOS"
        :key="section.id"
        :options="section"
      />
      <container-bonus
        v-if="section.container === ContainerSection.BONUS"
        :key="section.id"
        :options="section"
      />
      <container-offer
        v-if="section.container === ContainerSection.OFFER"
        :key="section.id"
        :options="section"
      />
      <container-player-favourites
        v-if="section.container === ContainerSection.PLAYER_FAVORITES"
        :key="section.id"
        :options="section"
      />
    </template>
  </div>
</template>
