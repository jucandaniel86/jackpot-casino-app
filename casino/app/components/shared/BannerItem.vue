<!-- eslint-disable vue/no-dupe-keys -->
<script setup lang="ts">
import { ButtonActionTypesEnum } from '~/core/types/ActionButton'
import type { OverlaysTypes } from '~/core/types/Overlays'

export type BannerActionButton = {
  actionType: ButtonActionTypesEnum
  slug?: string
  url?: string
  overlay?: string
  isSameTab?: number
}
export type BannerItem = {
  contentRules: {
    LG: string
    MD: string
    SM: string
    XL: string
    XS: string
  }
  name: string
  action: BannerActionButton
}

const { name } = useDisplay()
const props = defineProps<{ option: BannerItem }>()
const router = useRouter()
const { openOverlay } = useUtils()

const currentImage = computed(() => {
  switch (name.value) {
    case 'lg':
      return props.option.contentRules.LG
    case 'md':
      return props.option.contentRules.MD
    case 'sm':
      return props.option.contentRules.SM
    case 'xl':
      return props.option.contentRules.XL
    case 'xs':
      return props.option.contentRules.XS
    default:
      return props.option.contentRules.LG
  }
})

const handleClickAction = () => {
  switch (props.option.action.actionType) {
    case ButtonActionTypesEnum.OPEN_INTERNAL_PAGE: {
      if (props.option.action.slug) {
        return router.push(props.option.action.slug)
      }
      return null
    }
    case ButtonActionTypesEnum.OPEN_OVERLAY: {
      if (props.option.action.overlay) {
        openOverlay(props.option.action.overlay as OverlaysTypes)
      }
      return null
    }
    case ButtonActionTypesEnum.OPEN_EXTERNAL_PAGE: {
      let whereToOpen = '_self'
      if (!props.option.action.isSameTab) {
        whereToOpen = '_blank'
      }
      if (props.option.action.url) {
        return window.open(props.option.action.url, whereToOpen)
      }
      return null
    }
    default:
      return null
  }
}
</script>
<template>
  <div class="d-flex justify-center align-center w-100" @click.prevent="handleClickAction">
    <v-img :src="currentImage" height="350" />
  </div>
</template>
