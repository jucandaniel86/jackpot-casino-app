<script setup lang="ts">
type LogoT = {
  isMobile?: boolean
  class?: string
}

const props = withDefaults(defineProps<LogoT>(), {
  isMobile: false,
})
</script>
<template>
  <svg
    v-if="!props.isMobile"
    style="width: 140px"
    id="Layer_1"
    version="1.1"
    xmlns="http://www.w3.org/2000/svg"
    x="0px"
    y="0px"
    viewBox="0 0 336 97"
    enable-background="new 0 0 336 97"
    xml:space="preserve"
  >
    <g>
      <linearGradient
        id="SVGID_1_"
        gradientUnits="userSpaceOnUse"
        x1="-31.9471"
        y1="50.3715"
        x2="365.1684"
        y2="44.3241"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_1_)"
        d="M43.2,60.9c0,8.7-4.7,13.1-11.2,13.1c-5.9,0-10.5-3.2-15.1-8.7L0,81c6.7,8.9,16.6,15.7,33,15.7 c10.7,0,19.7-3.3,25.9-9.5c6.3-6.3,9.3-14.5,9.3-26.2V1.9h-25V60.9z"
      />
      <linearGradient
        id="SVGID_2_"
        gradientUnits="userSpaceOnUse"
        x1="-32.034"
        y1="51.5027"
        x2="361.5826"
        y2="45.5085"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_2_)"
        d="M204.9,73.4c-13.2,0-22.7-10.9-22.7-24.9v-0.3c0-13.6,9.5-24.8,22.7-24.8c9,0,15.3,4.4,20.7,10.9 l19.7-15.1C236.9,8,224.6,0,205.4,0c-16.7,0-30.7,7.3-39.5,18.8C156.8,7.4,142.7,0,125.7,0c-29,0-50.5,21.7-50.5,48.5v0.3 c0,26.8,21.2,48.2,50.2,48.2c16.9,0,31.2-7.4,40.3-18.8c8.8,11.6,22.9,18.8,38.8,18.8c20.7,0,32.6-8.9,41.5-21.1l-19.7-13.9 C220.6,68.9,214.7,73.4,204.9,73.4z M128.1,56.7c-0.2-1.1-1.5-1.8-1.4-0.3c0.4,4.3-0.2,5.4,4.7,16.4h-12.7c2.8-4,4.5-10.8,4.7-16 c0-1.8-1.4-1.7-1.4-0.4c-0.1,6.6-5.7,9.9-9.8,9.1c-6.3-1.2-8.3-7.4-8.2-11c0.2-11.3,11.6-19.6,21.9-34c7.6,13,20,23.3,20.8,33.4 C147.6,67.3,130.5,72.3,128.1,56.7z"
      />
      <linearGradient
        id="SVGID_3_"
        gradientUnits="userSpaceOnUse"
        x1="-21.2674"
        y1="53.2639"
        x2="357.8262"
        y2="47.4909"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <rect x="255.2" y="1.9" fill="url(#SVGID_3_)" width="24" height="94" />
      <linearGradient
        id="SVGID_4_"
        gradientUnits="userSpaceOnUse"
        x1="-32.0416"
        y1="27.6324"
        x2="361.0758"
        y2="21.6458"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_4_)"
        d="M335.1,2.3v21c0,6.8-1.8,11.5-4.8,14.4c-2.6,2.6-6,3.9-10.4,3.9h-0.1c-6.9,0-11.4-3.6-13.8-9.1 l-15.6,10.6V29l13.5-8.9v-5.4h-13.5V2.3H335.1z M313.6,22.8c0,4.1,2,6.6,5.3,6.6h0.1c3.6,0,5.4-2.6,5.4-6.7v-8.1h-10.8V22.8z"
      />
      <linearGradient
        id="SVGID_5_"
        gradientUnits="userSpaceOnUse"
        x1="-31.3293"
        y1="76.6107"
        x2="361.8289"
        y2="70.6236"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_5_)"
        d="M312.7,46.9h-0.1c-12.8,0-23.1,10.8-23.1,24.4c0,13.7,10.4,24.6,23.2,24.6h0.1 c12.8,0,23.1-10.8,23.1-24.4C336,57.8,325.5,46.9,312.7,46.9z M312.9,83.3c-2.5-4.3-5.2-8.3-9.2-12c4-3.9,6.7-8.1,9.2-12.2 c2.2,4.5,5.4,8.4,8.8,12.2C318.1,75,315.2,79,312.9,83.3z"
      />
    </g>
  </svg>

  <svg
    v-else
    style="width: 140px"
    version="1.1"
    xmlns="http://www.w3.org/2000/svg"
    x="0px"
    y="0px"
    viewBox="0 0 336 97"
    enable-background="new 0 0 336 97"
    xml:space="preserve"
  >
    <g>
      <linearGradient
        id="SVGID_1_"
        gradientUnits="userSpaceOnUse"
        x1="-31.9471"
        y1="50.3715"
        x2="365.1684"
        y2="44.3241"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_1_)"
        d="M43.2,60.9c0,8.7-4.7,13.1-11.2,13.1c-5.9,0-10.5-3.2-15.1-8.7L0,81c6.7,8.9,16.6,15.7,33,15.7 c10.7,0,19.7-3.3,25.9-9.5c6.3-6.3,9.3-14.5,9.3-26.2V1.9h-25V60.9z"
      />
      <linearGradient
        id="SVGID_2_"
        gradientUnits="userSpaceOnUse"
        x1="-32.034"
        y1="51.5027"
        x2="361.5826"
        y2="45.5085"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_2_)"
        d="M204.9,73.4c-13.2,0-22.7-10.9-22.7-24.9v-0.3c0-13.6,9.5-24.8,22.7-24.8c9,0,15.3,4.4,20.7,10.9 l19.7-15.1C236.9,8,224.6,0,205.4,0c-16.7,0-30.7,7.3-39.5,18.8C156.8,7.4,142.7,0,125.7,0c-29,0-50.5,21.7-50.5,48.5v0.3 c0,26.8,21.2,48.2,50.2,48.2c16.9,0,31.2-7.4,40.3-18.8c8.8,11.6,22.9,18.8,38.8,18.8c20.7,0,32.6-8.9,41.5-21.1l-19.7-13.9 C220.6,68.9,214.7,73.4,204.9,73.4z M128.1,56.7c-0.2-1.1-1.5-1.8-1.4-0.3c0.4,4.3-0.2,5.4,4.7,16.4h-12.7c2.8-4,4.5-10.8,4.7-16 c0-1.8-1.4-1.7-1.4-0.4c-0.1,6.6-5.7,9.9-9.8,9.1c-6.3-1.2-8.3-7.4-8.2-11c0.2-11.3,11.6-19.6,21.9-34c7.6,13,20,23.3,20.8,33.4 C147.6,67.3,130.5,72.3,128.1,56.7z"
      />
      <linearGradient
        id="SVGID_3_"
        gradientUnits="userSpaceOnUse"
        x1="-21.2674"
        y1="53.2639"
        x2="357.8262"
        y2="47.4909"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <rect x="255.2" y="1.9" fill="url(#SVGID_3_)" width="24" height="94" />
      <linearGradient
        id="SVGID_4_"
        gradientUnits="userSpaceOnUse"
        x1="-32.0416"
        y1="27.6324"
        x2="361.0758"
        y2="21.6458"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_4_)"
        d="M335.1,2.3v21c0,6.8-1.8,11.5-4.8,14.4c-2.6,2.6-6,3.9-10.4,3.9h-0.1c-6.9,0-11.4-3.6-13.8-9.1 l-15.6,10.6V29l13.5-8.9v-5.4h-13.5V2.3H335.1z M313.6,22.8c0,4.1,2,6.6,5.3,6.6h0.1c3.6,0,5.4-2.6,5.4-6.7v-8.1h-10.8V22.8z"
      />
      <linearGradient
        id="SVGID_5_"
        gradientUnits="userSpaceOnUse"
        x1="-31.3293"
        y1="76.6107"
        x2="361.8289"
        y2="70.6236"
      >
        <stop offset="0" style="stop-color: #ecc77f" />
        <stop offset="1" style="stop-color: #cda66e" />
      </linearGradient>
      <path
        fill="url(#SVGID_5_)"
        d="M312.7,46.9h-0.1c-12.8,0-23.1,10.8-23.1,24.4c0,13.7,10.4,24.6,23.2,24.6h0.1 c12.8,0,23.1-10.8,23.1-24.4C336,57.8,325.5,46.9,312.7,46.9z M312.9,83.3c-2.5-4.3-5.2-8.3-9.2-12c4-3.9,6.7-8.1,9.2-12.2 c2.2,4.5,5.4,8.4,8.8,12.2C318.1,75,315.2,79,312.9,83.3z"
      />
    </g>
  </svg>
</template>
