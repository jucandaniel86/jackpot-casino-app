<script setup lang="ts">
import { ButtonActionTypesEnum, type ActionButtonType } from '~/core/types/ActionButton'
import type { OverlaysTypes } from '~/core/types/Overlays'

const props = defineProps<ActionButtonType>()

const router = useRouter()
const { openOverlay } = useUtils()

const handleAction = () => {
  switch (props.action.type) {
    case ButtonActionTypesEnum.OPEN_INTERNAL_PAGE: {
      if (props.action.slug) {
        return router.push(props.action.slug)
      }
      return null
    }
    case ButtonActionTypesEnum.OPEN_OVERLAY: {
      if (props.action.overlay) {
        openOverlay(props.action.overlay as OverlaysTypes)
      }
      return null
    }
    case ButtonActionTypesEnum.OPEN_EXTERNAL_PAGE: {
      let whereToOpen = '_self'
      if (!props.action.isSameTab) {
        whereToOpen = '_blank'
      }
      if (props.action.url) {
        return window.open(props.action.url, whereToOpen)
      }
      return null
    }
    default:
      return null
  }
}
</script>
<template>
  <v-btn :color="props.color" class="w-50" @click.prevent="handleAction">{{ props.title }}</v-btn>
</template>
