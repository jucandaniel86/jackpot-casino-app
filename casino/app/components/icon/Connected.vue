<template>
  <svg
    class="svg-connected"
    focusable="false"
    aria-hidden="true"
    viewBox="0 0 24 24"
    data-testid="CheckCircleIcon"
    style="font-size: 14px; color: rgb(154, 255, 72)"
  >
    <path
      d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
    />
  </svg>
</template>
<style scoped>
.svg-connected {
  user-select: none;
  width: 1em;
  height: 1em;
  display: inline-block;
  fill: currentcolor;
  flex-shrink: 0;
  font-size: 1.5rem;
  transition: fill 200ms cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
