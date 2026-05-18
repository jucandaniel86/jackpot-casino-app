<script setup lang="ts">
import { useAppStore } from '~/core/store/app'

const email = ref('')
const isRedeeming = ref(false)
const { success: alertSuccess, error: alertError } = useAlerts()
const { setLoadWallets } = useAppStore()
const dailyCaption =
  'Daily Check in with Jackpot Casino. Share this post and claim 1000 JKP. #JackpotCasino #JKP'

const shareActions = [
  {
    label: 'Instagram',
    icon: 'mdi-instagram',
    href: 'https://www.instagram.com/',
    kind: 'link',
  },
  {
    label: 'Share Post',
    icon: 'mdi-share-variant',
    href: 'https://www.instagram.com/create/select/',
    kind: 'link',
  },
  {
    label: 'Copy Caption',
    icon: 'mdi-content-copy',
    kind: 'copy',
  },
]

const copyDailyCaption = async () => {
  if (!import.meta.client || !navigator.clipboard) return

  await navigator.clipboard.writeText(dailyCaption)
}

const redeemReward = async () => {
  if (isRedeeming.value) return

  isRedeeming.value = true

  const { data, success, error } = await useApiPostFetch('/redeem', {
    email: email.value,
  })

  isRedeeming.value = false

  if (!success) {
    alertError(error?.message || 'We could not redeem this reward.')
    return
  }

  if (!data?.success) {
    alertError(data?.message || 'We could not redeem this reward.')
    return
  }

  alertSuccess(data.message || 'You received 1000 JKP.')
  setLoadWallets(true)
}
</script>

<template>
  <v-container class="redeem-page">
    <section class="redeem-hero">
      <div class="redeem-hero__media">
        <img
          src="https://images.unsplash.com/photo-1606167668584-78701c57f13d?auto=format&fit=crop&w=900&q=80"
          alt="Casino chips on a game table"
        />
      </div>

      <div class="redeem-hero__content">
        <span class="redeem-hero__eyebrow">Limited reward</span>
        <h1>Redeem to get 1000 JKP</h1>
        <p>
          Subscribe with your email and keep your account ready for the next Jackpot reward drop.
        </p>

        <form class="redeem-hero__form" @submit.prevent="redeemReward">
          <v-text-field
            v-model="email"
            type="email"
            required
            placeholder="Enter your email address"
            variant="outlined"
            density="comfortable"
            hide-details
          />
          <v-btn color="purple" type="submit" :loading="isRedeeming">Subscribe</v-btn>
        </form>
      </div>
    </section>

    <section id="daily-check-in" class="daily-check-in">
      <div class="daily-check-in__content">
        <span class="redeem-hero__eyebrow">Daily Check in</span>
        <h2>Share today and claim 1000 JKP</h2>
        <p>
          Post your daily Jackpot moment on Instagram, tag the campaign, and come back to redeem
          another 1000 JKP reward.
        </p>

        <div class="daily-check-in__actions" aria-label="Daily check in share actions">
          <v-btn
            v-for="action in shareActions"
            :key="action.label"
            :href="action.href"
            :prepend-icon="action.icon"
            :target="action.kind === 'link' ? '_blank' : undefined"
            :rel="action.kind === 'link' ? 'noopener noreferrer' : undefined"
            :color="action.label === 'Instagram' ? 'purple' : 'primary'"
            @click="action.kind === 'copy' && copyDailyCaption()"
          >
            {{ action.label }}
          </v-btn>
        </div>
      </div>

      <div class="daily-check-in__media">
        <img
          src="https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?auto=format&fit=crop&w=900&q=80"
          alt="Phone showing social media content"
        />
      </div>
    </section>

    <section class="share-bonus">
      <div class="share-bonus__media">
        <img
          src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80"
          alt="Person sharing content from a laptop"
        />
      </div>

      <div class="share-bonus__content">
        <span class="redeem-hero__eyebrow">Bonus Share</span>
        <h2>Invite your friends to collect 1000 JKP</h2>
        <p>
          Share the campaign link with your friends, bring them into the Jackpot community, and
          unlock another reward after your post is verified.
        </p>

        <div class="daily-check-in__actions" aria-label="Bonus share actions">
          <v-btn color="purple" prepend-icon="mdi-share-variant">Share Campaign</v-btn>
          <v-btn color="primary" prepend-icon="mdi-link-variant">Copy Link</v-btn>
          <v-btn color="primary" prepend-icon="mdi-send">Send Invite</v-btn>
        </div>
      </div>
    </section>
  </v-container>
</template>

<style scoped>
.redeem-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding-top: 24px;
  padding-bottom: 24px;
}

.redeem-hero {
  display: grid;
  grid-template-columns: minmax(0, 0.95fr) minmax(320px, 1.05fr);
  gap: 28px;
  align-items: stretch;
  overflow: hidden;
  border: 1px solid var(--surface-border);
  border-radius: var(--premium-radius);
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.04), transparent 38%), var(--surface-mid);
  box-shadow: var(--premium-shadow);
}

.redeem-hero__media {
  position: relative;
  min-height: 360px;
  overflow: hidden;
  background: var(--surface-low);
}

.redeem-hero__media::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent 58%, rgba(24, 27, 34, 0.72));
}

.redeem-hero__media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: saturate(0.96) contrast(1.04);
}

.redeem-hero__content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  padding: 48px 40px 48px 12px;
  color: #fff;
}

.redeem-hero__eyebrow {
  width: fit-content;
  margin-bottom: 14px;
  padding: 7px 12px;
  border: 1px solid color-mix(in srgb, var(--base-color) 42%, transparent);
  border-radius: 999px;
  background: var(--base-color-soft);
  color: color-mix(in srgb, var(--base-color) 55%, #ffffff);
  font-size: 13px;
  font-weight: 800;
}

.redeem-hero__content h1 {
  margin: 0;
  font-size: 42px;
  line-height: 1.08;
  font-weight: 800;
}

.redeem-hero__content p {
  max-width: 520px;
  margin: 14px 0 28px;
  color: var(--text-color);
  font-size: 16px;
  line-height: 1.6;
}

.redeem-hero__form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 12px;
  max-width: 560px;
}

.redeem-hero__form :deep(.v-btn) {
  height: 48px !important;
  padding: 0 26px !important;
}

.daily-check-in {
  display: grid;
  grid-template-columns: minmax(320px, 1.05fr) minmax(0, 0.95fr);
  gap: 28px;
  align-items: center;
  overflow: hidden;
  border: 1px solid var(--surface-border);
  border-radius: var(--premium-radius);
  background:
    radial-gradient(circle at 88% 18%, var(--base-color-soft), transparent 28%),
    linear-gradient(135deg, rgba(255, 255, 255, 0.035), transparent 44%), var(--surface-low);
  box-shadow: var(--premium-shadow);
}

.daily-check-in__content {
  min-width: 0;
  padding: 40px;
  color: #fff;
}

.daily-check-in__content h2 {
  margin: 0;
  font-size: 34px;
  line-height: 1.16;
  font-weight: 800;
}

.daily-check-in__content p {
  max-width: 560px;
  margin: 14px 0 26px;
  color: var(--text-color);
  font-size: 16px;
  line-height: 1.6;
}

.daily-check-in__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.daily-check-in__actions :deep(.v-btn) {
  height: 44px !important;
  padding: 0 18px !important;
}

.daily-check-in__media {
  height: 100%;
  min-height: 300px;
  overflow: hidden;
  background: var(--surface-mid);
}

.daily-check-in__media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: saturate(0.94) contrast(1.05);
}

.share-bonus {
  display: grid;
  grid-template-columns: minmax(0, 0.95fr) minmax(320px, 1.05fr);
  gap: 28px;
  align-items: stretch;
  overflow: hidden;
  border: 1px solid var(--surface-border);
  border-radius: var(--premium-radius);
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.04), transparent 40%), var(--surface-mid);
  box-shadow: var(--premium-shadow);
}

.share-bonus__media {
  min-height: 300px;
  overflow: hidden;
  background: var(--surface-low);
}

.share-bonus__media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: saturate(0.94) contrast(1.05);
}

.share-bonus__content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  padding: 40px 40px 40px 12px;
  color: #fff;
}

.share-bonus__content h2 {
  margin: 0;
  font-size: 34px;
  line-height: 1.16;
  font-weight: 800;
}

.share-bonus__content p {
  max-width: 560px;
  margin: 14px 0 26px;
  color: var(--text-color);
  font-size: 16px;
  line-height: 1.6;
}

@media screen and (max-width: 900px) {
  .redeem-hero,
  .daily-check-in,
  .share-bonus {
    grid-template-columns: 1fr;
  }

  .redeem-hero__media {
    min-height: 240px;
  }

  .redeem-hero__media::after {
    background: linear-gradient(180deg, transparent 58%, rgba(24, 27, 34, 0.76));
  }

  .redeem-hero__content {
    padding: 0 22px 28px;
  }

  .daily-check-in__content {
    padding: 28px 22px 0;
  }

  .daily-check-in__media {
    min-height: 240px;
  }

  .share-bonus__media {
    min-height: 240px;
  }

  .share-bonus__content {
    padding: 0 22px 28px;
  }
}

@media screen and (max-width: 560px) {
  .redeem-page {
    padding-top: 16px;
  }

  .redeem-hero__content h1 {
    font-size: 32px;
  }

  .daily-check-in__content h2,
  .share-bonus__content h2 {
    font-size: 28px;
  }

  .redeem-hero__form {
    grid-template-columns: 1fr;
  }
}
</style>
