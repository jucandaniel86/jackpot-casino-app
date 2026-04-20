<script setup lang="ts">
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
const props = defineProps<SectionContainerT>();

//models
const MAX_OFFERS = 3;

const loading = ref<boolean>(false);
const offers = ref<any[]>([]);
const chosenOffers = ref<any[]>([]);

//emitters
const emits = defineEmits(["onUpdate"]);

//methods
const getOffers = async (): Promise<void> => {
  loading.value = true;
  const { data } = await useAPIFetch("/sections/home-boxes");
  if (data) {
    offers.value = data.data;
  }
  loading.value = false;
};

watch(
  chosenOffers,
  () => {
    emits("onUpdate", {
      offers: chosenOffers.value,
    });
  },
  { deep: true }
);

onMounted(() => {
  getOffers();
  if (typeof props.item.data?.offers !== "undefined") {
    chosenOffers.value = props.item.data?.offers;
  }
});
</script>
<template>
  <v-row>
    <v-col cols="12">
      <v-alert type="info" density="compact">Max 3 items</v-alert>
    </v-col>
    <v-col cols="6" v-for="(item, i) in offers" :key="`Offer${i}`">
      <v-card class="pb-3" border flat>
        <v-card-title class="d-flex align-center ga-1">
          <v-checkbox
            :value="item.id"
            v-model="chosenOffers"
            density="compact"
            hide-details
            :disabled="
              chosenOffers.length >= MAX_OFFERS &&
              chosenOffers.indexOf(item.id) === -1
            "
          />
          <div
            class="mb-2 w-75 pr-2"
            style="
              font-size: 1rem;
              overflow-wrap: break-word;
              text-overflow: ellipsis;
              overflow: hidden;
            "
          >
            {{ item.content }}
          </div>
        </v-card-title>
        <v-img :src="item.thumbnail"></v-img>
      </v-card>
    </v-col>
  </v-row>
</template>
