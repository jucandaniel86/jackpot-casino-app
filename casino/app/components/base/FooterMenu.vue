<script setup lang="ts">
import { useSidebarStore } from '~/core/store/sidebar'

//composables
const router = useRouter()
const { toggleSidebar } = useSidebarStore()
const { t } = useI18n()

//methods
const openCasino = () => router.push({ path: '/casino' })

const openHelp = () => router.push({ path: '/help' })
</script>
<template>
  <div class="FooterMenu">
    <div class="content">
      <button class="FooterMenuItem" @click="toggleSidebar">
        <SharedIcon icon="brand-ico-menu2" />
        <p>{{ t('footer.menu') }}</p>
      </button>
      <button class="FooterMenuItem" @click="openCasino">
        <SharedIcon icon="brand-ico-casino2" />
        <p>{{ t('footer.casino') }}</p>
      </button>
      <button class="FooterMenuItem">
        <SharedIcon icon="brand-ico-buy-crypto2" />
        <p>{{ t('footer.buy') }}</p>
      </button>
      <button class="FooterMenuItem" @click="openHelp">
        <SharedIcon icon="brand-ico-faq" />
        <p>{{ t('footer.help') }}</p>
      </button>
    </div>
  </div>
</template>
