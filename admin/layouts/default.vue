<script setup lang="ts">
	import LeftSideBar from '@/components/layouts/leftSideBar/index.vue'
	import TopBar from "@/components/layouts/topBar/index.vue";
import { useLayoutStore } from '~/store/app';

	const isHorizontal = true;
	const config = useRuntimeConfig();
	const layoutStore = useLayoutStore();
	const showGlobalLoader = ref(false);
	let loaderTimer: ReturnType<typeof setTimeout> | null = null;
	let hideTimer: ReturnType<typeof setTimeout> | null = null;

	const isGlobalLoading = computed(() => layoutStore.isGlobalLoading);

	watch(isGlobalLoading, (loading) => {
		if (loaderTimer) {
			clearTimeout(loaderTimer);
			loaderTimer = null;
		}
		if (hideTimer) {
			clearTimeout(hideTimer);
			hideTimer = null;
		}

		if (loading) {
			if (showGlobalLoader.value) {
				return;
			}

			loaderTimer = setTimeout(() => {
				showGlobalLoader.value = true;
			}, 150);
			return;
		}

		hideTimer = setTimeout(() => {
			showGlobalLoader.value = false;
		}, 200);
	});

	onBeforeUnmount(() => {
		if (loaderTimer) {
			clearTimeout(loaderTimer);
		}
		if (hideTimer) {
			clearTimeout(hideTimer);
		}
	});

</script>
<template>
	<v-app>
		<v-fade-transition>
			<div v-if="showGlobalLoader" class="global-preloader">
				<div class="global-preloader__veil">
					<v-progress-circular
						indeterminate
						color="primary"
						size="42"
						width="4"
					/>
				</div>
			</div>
		</v-fade-transition>
		<v-layout
      class="main-layout-wrapper justify-center position-relative"
      id="layout-wrapper"
    >
		<LeftSideBar />
		<TopBar />
		<v-main app>
			<v-container
				:class="!isHorizontal ? 'pt-0' : ''"
				:fluid="!isHorizontal"
				class="main-container h-100"
			>
				<slot />
			</v-container>
		</v-main>
		
		<v-footer app class="footer">
			<v-container fluid>
				<v-row>
					<v-col class="pa-0">
						{{ new Date().getFullYear() }} © .{{ config.public.appName }}
					</v-col>
			 	</v-row>
			</v-container>
		</v-footer>
	 </v-layout>
	</v-app>
</template>

<style scoped>
.global-preloader {
	position: fixed;
	inset: 0;
	z-index: 2000;
	pointer-events: none;
}

.global-preloader__veil {
	position: fixed;
	inset: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(255, 255, 255, 0.45);
	backdrop-filter: blur(2px);
}
</style>
