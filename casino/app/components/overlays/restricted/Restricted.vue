<script setup lang="ts">
import { OverlaysTypes } from '~/core/types/Overlays'

const emits = defineEmits(['on-close', 'change-view'])

const changeView = (view: string) => emits('change-view', view)

const onClose = () => emits('on-close')
</script>

<template>
  <v-card class="restricted-card mx-auto w-100" max-width="460" max-height="100%" variant="flat">
    <v-card-text class="restricted-card__body">
      <div class="restricted-card__icon">
        <v-icon icon="mdi-shield-lock-outline" size="44" />
      </div>

      <div class="restricted-card__eyebrow">Restricted access</div>
      <h2 class="restricted-card__title">Login required</h2>
      <p class="restricted-card__copy">
        You need to be logged in to continue. Sign in to unlock this area and keep your session
        secure.
      </p>

      <div class="restricted-card__actions">
        <v-btn
          color="primary"
          size="large"
          variant="flat"
          block
          class="restricted-card__login"
          @click="changeView(OverlaysTypes.LOGIN)"
        >
          <v-icon start icon="mdi-login" />
          Login
        </v-btn>
        <v-btn
          size="large"
          variant="text"
          block
          class="restricted-card__secondary"
          @click="onClose"
        >
          Close
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.restricted-card {
  position: relative;
  overflow: hidden;
  border-radius: 18px !important;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02)), #171717 !important;
  box-shadow:
    0 24px 80px rgba(0, 0, 0, 0.38),
    inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.restricted-card__close {
  position: absolute;
  top: 14px;
  right: 14px;
  z-index: 2;
  background: rgba(255, 255, 255, 0.1) !important;
  color: #fff !important;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.16);
}

.restricted-card__close:hover {
  background: rgba(255, 255, 255, 0.16) !important;
  color: #ffd166 !important;
}

.restricted-card__body {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 44px 34px 34px !important;
  text-align: center;
}

.restricted-card__icon {
  display: flex;
  width: 84px;
  height: 84px;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #ffd166;
  background: rgba(255, 209, 102, 0.12);
  box-shadow:
    inset 0 0 0 1px rgba(255, 209, 102, 0.22),
    0 18px 44px rgba(255, 209, 102, 0.12);
}

.restricted-card__eyebrow {
  color: #ffd166;
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.16em;
  margin-top: 24px;
  text-transform: uppercase;
}

.restricted-card__title {
  color: #fff;
  font-size: 32px;
  font-weight: 950;
  line-height: 1;
  margin: 10px 0 0;
}

.restricted-card__copy {
  color: rgba(255, 255, 255, 0.68);
  font-size: 15px;
  line-height: 1.6;
  margin: 16px 0 0;
  max-width: 340px;
}

.restricted-card__actions {
  display: grid;
  gap: 10px;
  margin-top: 30px;
  width: 100%;
}

.restricted-card__login {
  border-radius: 12px !important;
  font-weight: 900 !important;
  text-transform: none !important;
}

.restricted-card__secondary {
  color: rgba(255, 255, 255, 0.72) !important;
  text-transform: none !important;
}

@media (max-width: 539px) {
  .restricted-card {
    border-radius: 14px !important;
  }

  .restricted-card__body {
    padding: 38px 24px 28px !important;
  }

  .restricted-card__title {
    font-size: 28px;
  }
}
</style>
