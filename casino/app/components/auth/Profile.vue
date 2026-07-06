<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'

const authStore = useAuthStore()
const { logout, setUser } = authStore
const { user, isLogged } = storeToRefs(authStore)
const router = useRouter()
const { t } = useI18n()
const { name } = useDisplay()

const goToSettings = () => router.push({ name: 'profile' })
const goToRedeem = () => router.replace({ query: { overlay: OverlaysTypes.REDEEM } })
const dailyRedeem = computed(() => user.value?.daily_redeem ?? null)
const showDailyRedeemCta = computed(() => Boolean(dailyRedeem.value?.can_claim))
const isDesktop = computed(() => ['md', 'lg', 'xl', 'xxl'].includes(name.value))
const dailyRedeemTitle = computed(
  () => dailyRedeem.value?.reward?.title || t('profile.dailyRedeemTitle'),
)
const dailyRedeemSubtitle = computed(
  () => dailyRedeem.value?.reward?.subtitle || t('profile.dailyRedeemSubtitle'),
)

const handleLogout = async () => {
  await logout()
  router.push('/')
}

const refreshProfileOffer = async () => {
  if (!isLogged.value || user.value?.daily_redeem !== undefined) {
    return
  }

  try {
    const profile = await useAPIFetch('/player/profile')
    if (profile?.user) {
      setUser(profile.user)
    }
  } catch (_error) {
    // The profile menu should remain usable even if this optional CTA cannot refresh.
  }
}

onMounted(() => {
  refreshProfileOffer()
})
</script>
<template>
  <div class="profile-actions">
    <button
      v-if="showDailyRedeemCta && isDesktop"
      type="button"
      class="profile-top-redeem"
      @click.prevent="goToRedeem"
    >
      <v-icon icon="mdi-gift-outline" size="18" />
      <span>{{ t('profile.dailyRedeemShortCta') }}</span>
    </button>

    <v-menu location="bottom right">
      <template #activator="{ props }">
        <v-btn v-bind="props" class="user-top-activator pa-0">
          <shared-icon :icon="'brand-ico-settings2'" class="svg-icon user-icon" />
        </v-btn>
      </template>
      <v-list class="user-profile-list">
        <button
          v-if="showDailyRedeemCta"
          type="button"
          class="profile-redeem-cta"
          @click.prevent="goToRedeem"
        >
          <span class="profile-redeem-cta__icon">
            <v-icon icon="mdi-gift-outline" size="20" />
          </span>
          <span class="profile-redeem-cta__body">
            <strong>{{ dailyRedeemTitle }}</strong>
            <small>{{ dailyRedeemSubtitle }}</small>
          </span>
          <v-icon icon="mdi-chevron-right" size="18" />
        </button>
        <v-list-item @click.prevent="goToSettings">
          <template #prepend>
            <shared-icon icon="brand-ico-settings2" class="svg-icon" />
          </template>
          <v-list-item-title>{{ t('header.settings') }}</v-list-item-title>
        </v-list-item>
        <v-list-item @click.prevent="handleLogout">
          <template #prepend>
            <shared-icon icon="brand-ico-Logout-black" class="svg-icon" />
          </template>
          <v-list-item-title>{{ t('header.logout') }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>
