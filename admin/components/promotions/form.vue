<script setup lang="ts">
const props = defineProps({
  saveLoading: {
    required: true,
    default: false,
  },
});

//models
const form = ref({
  title: "",
  subtitle: "",
  thumbnail_file: [],
  banner_file: [],
  description: "",
  smallDescription: "",
  howItWorks: "",
  terms: "",
  primaryAction: {},
  active: 0,
  seo: {
    title: "",
    description: "",
    displayTitle: "",
    displayDescription: "",
    indexed: 1,
  },
});
const loading = ref<boolean>(false);
const item = ref();

//composables
const route = useRoute();
const { verifyNullObject } = useUtils();

//emits
const emits = defineEmits(["onSave"]);

//methods
const getPromotion = async (): Promise<void> => {
  loading.value = true;
  const { data, success } = await useAPIFetch("/promotions/get", {
    id: route.params.id,
  });
  if (success) {
    form.value = {
      ...form.value,
      ...data.data.item,
			active: parseInt(data.data.item.active)
    };
	  if (
      !verifyNullObject(data.data.item.seo, [
        "title",
        "description",
        "displayTitle",
        "displayDescription",
        "indexed",
      ])
    ) {
      form.value.seo = {
        title: "",
        description: "",
        displayTitle: "",
        displayDescription: "",
        indexed: 1,
      };
    }

    if (
      !verifyNullObject(data.data.item.primaryAction, [
        "action",
        "title",
        "color",
      ])
    ) {
      form.value.primaryAction = {};
    }

    item.value = data.data.item;
  }
  loading.value = false;
};

const save = () => emits("onSave", form.value);
const handlePrimaryActionUpdate = (content: any) =>
  (form.value.primaryAction = content);

//computed
const StatusColor = computed(() => {
  switch (item.value.status) {
    case "DRAFT":
      return "orange";
    case "PRIVATE":
      return "red";
    case "PUBLISHED":
      return "green";
    default:
      "orange";
  }
});

//mounted
onMounted(() => {
  if (route.params.id) {
    getPromotion();
  }
});
</script>
<template>
	<div v-if="loading" class="w-100 h-100 d-flex justify-center align-center">
		<v-progress-circular  indeterminate />
	</div>
  <v-row v-else>
    <v-col cols="8">
      <div class="page">
        <v-card density="compact" elevation="0">
          <v-card-title>Promotion Card Options</v-card-title> 
          <v-card-text>
            <v-text-field
              v-model="form.title"
              hide-details
              density="compact"
              label="Title"
            />
            <v-text-field
              v-model="form.subtitle"
              hide-details
              density="compact"
              label="Subtitle"
            />
            <v-textarea
              v-model="form.smallDescription"
              hide-details
              denisty="compact"
              label="Small Description"
            />
            <v-img v-if="item && item.thumbnailUrl" :src="item.thumbnailUrl" />
            <v-file-input
              v-model="form.thumbnail_file"
              label="Thumbnail"
              accept="image/*"
              show-size
              hide-details
              density="compact"
            ></v-file-input>

            <SharedActionButton
              :color="'purple'"
              label="Action Button"
              :default-options="form.primaryAction"
              v-if="!loading"
              @model:update="handlePrimaryActionUpdate"
            />
          </v-card-text>
        </v-card>
        <v-card class="mt-1" density="compact" elevation="0">
          <v-card-title>Banner</v-card-title>
          <v-card-text>
            <v-img
              v-if="item && item.bannerUrl && item.bannerUrl !== ''"
              :src="item.bannerUrl"
            />
            <v-file-input
              v-model="form.banner_file"
              label="Thumbnail"
              accept="image/*"
              show-size
              hide-details
              density="compact"
            ></v-file-input>
          </v-card-text>
        </v-card>

        <v-card class="mt-1" density="compact" elevation="0">
          <v-card-title>Description</v-card-title>
          <v-card-text>
            <SharedEditor
              v-if="!loading"
              v-model="form.description"
              :content="form.description"
            /> 
          </v-card-text>
        </v-card>

        <v-card class="mt-1" density="compact" elevation="0">
          <v-card-title>How it Works</v-card-title>
          <v-card-text>
            <SharedEditor
              v-if="!loading"
              v-model="form.howItWorks"
              :content="form.howItWorks"
            />
          </v-card-text>
        </v-card>

        <v-card class="mt-1" density="compact" elevation="0">
          <v-card-title>Terms</v-card-title>
          <v-card-text>
            <SharedEditor
              v-if="!loading"
              v-model="form.terms"
              :content="form.terms"
            />
          </v-card-text>
        </v-card>
      </div>
    </v-col>
    <v-col cols="4">
      <div class="position-sticky top-0">
        <v-card v-if="item" class="mb-2" elevation="0">
          <v-card-title class="text-h6"
            ><v-icon icon="mdi-cog" /> Settings</v-card-title
          >
          <v-card-text>  
            Status: <v-chip :color="StatusColor" :text="item.status" />
            <v-checkbox
              v-model="form.active"
              label="Active"
              hide-details
              density="compact"
              :false-value="0"
              :true-value="1"
            />
          </v-card-text>
        </v-card>

        <SharedSeo v-model="form.seo" />
        <div class="d-flex align-center justify-center w-100 mt-2">
          <v-btn
            :disabled="props.saveLoading"
            @click="save"
            variant="flat"
            color="blue"
            prepend-icon="mdi-content-save"
            class="w-75"
            >Save</v-btn
          >
        </div>
      </div>
    </v-col>
  </v-row>
</template>
<style scoped>
.page {
  height: 500px;
  overflow-y: scroll;
}
</style>
