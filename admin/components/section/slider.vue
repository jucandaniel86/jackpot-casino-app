<script setup lang="ts">
import { ButtonActionTypesEnum } from "~/core/types/ActionButton";
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
//props
const props = defineProps<SectionContainerT>();
const ACTION_TYPES = [
  { id: ButtonActionTypesEnum.OPEN_INTERNAL_PAGE, label: "Open Internal Page" },
  { id: ButtonActionTypesEnum.OPEN_EXTERNAL_PAGE, label: "Open External Page" },
  { id: ButtonActionTypesEnum.OPEN_OVERLAY, label: "Open Overlay" },
];

//composables
const {
  alertSuccess,
  axiosErrorAlert,
  toastError,
  toastSuccess,
  confirmDelete,
} = useAlert();

//models
const pages = ref([]);
const sliders = ref<any[]>([]);
const slidersOptions = ref<number[]>([]);
const form = ref({
  name: "",
  actionType: ButtonActionTypesEnum.OPEN_INTERNAL_PAGE,
  overlay: "",
  page_id: 0,
  url: "",
  isSameTab: 1,
  noFollow: 0,
  banner: [],
  bannerMobile: [],
});
const loading = ref<boolean>(false);
const availableOverlays = [
  { id: "wallet", label: "Wallet" },
  { id: "login", label: "Login" },
  { id: "register", label: "Register" },
];

const emits = defineEmits(["onUpdate"]);

//methods
const getPages = async (): Promise<void> => {
  const { data } = await useAPIFetch("/pages/list");
  pages.value = data.data;
};

const getSliders = async (): Promise<void> => {
  const { data } = await useAPIFetch("/sliders/list");

  sliders.value = data.data;
};

const resetForm = () => {
  form.value = {
    name: "",
    actionType: ButtonActionTypesEnum.OPEN_INTERNAL_PAGE,
    overlay: "",
    page_id: 0,
    url: "",
    isSameTab: 1,
    noFollow: 0,
    banner: [],
    bannerMobile: [],
  };
};

const save = async () => {
  loading.value = true;

  var formData = new FormData();

  formData.append("name", form.value.name);
  formData.append("page_id", String(form.value.page_id));
  formData.append("action_type", form.value.actionType);
  formData.append("url", form.value.url);
  formData.append("overlay", form.value.overlay);
  formData.append("is_same_tab", String(form.value.isSameTab));
  formData.append("no_follow", String(form.value.noFollow));

  const bannerFile = Array.isArray(form.value.banner)
    ? form.value.banner[0]
    : form.value.banner;

  const bannerMobileFile = Array.isArray(form.value.banner)
    ? form.value.bannerMobile[0]
    : form.value.bannerMobile;

  if (bannerFile) {
    formData.append("banner", bannerFile);
  }
  if (bannerMobileFile) {
    //@ts-ignore
    formData.append("banner_mobile", bannerMobileFile);
  }

  const { data, error } = await useApiPostFetch("/sliders/save", formData);
  if (data) {
    alertSuccess(data.message);
    loading.value = false;
    getSliders();
    resetForm();
    return;
  }
  loading.value = false;
  return axiosErrorAlert(error, true);
};

const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch(
      "/sliders/delete",
      {
        id,
      },
    );
    if (success) {
      if (data.data.success) {
        toastSuccess(data.data.msg);
        getSliders();
      } else {
        toastError(data.data.msg);
      }

      return;
    }
    if (error) {
      return axiosErrorAlert(error);
    }
  });
};

//watch
watch(
  slidersOptions,
  () => {
    emits("onUpdate", {
      sliders: slidersOptions.value,
    });
  },
  { deep: true },
);

//mounted
onMounted(async () => {
  await getSliders();
  await getPages();
  if (props.item.data && props.item.data.sliders) {
    slidersOptions.value = props.item.data.sliders;
  }
});
</script>
<template>
  <v-row>
    <v-col cols="8">
      <v-table fixed-header>
        <thead>
          <tr>
            <th width="5%"></th>
            <th width="30%">Slider</th>
            <th width="20%">Mobile</th>
            <th width="20%">Name</th>
            <th width="20%">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(slider, i) in sliders" :key="`Slider${i}`">
            <td>
              <v-checkbox
                v-model="slidersOptions"
                :value="slider.id"
                hide-details
                density="compact"
              />
            </td>
            <td>
              <v-img v-if="slider.bannerUrl" :src="slider.bannerUrl" />
            </td>
            <td>
              <v-img v-if="slider.bannerUrl" :src="slider.bannerMobileUrl" />
            </td>
            <td>{{ slider.name }}</td>
            <td>
              <v-btn
                @click.prevent="deleteItem(slider.id)"
                :text="'Delete'"
                :prepend-icon="'mdi-delete-outline'"
              />
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-col>
    <v-col cols="4">
      <v-card>
        <v-card-title>New Banner</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="form.name"
            label="Name"
            hide-details
            density="compact"
          />
          <v-file-input
            v-model="form.banner"
            label="Banner"
            accept="image/*"
            show-size
            hide-details
            density="compact"
          ></v-file-input>
          <v-file-input
            v-model="form.bannerMobile"
            label="Banner (mobile)"
            accept="image/*"
            show-size
            hide-details
            density="compact"
          ></v-file-input>
          <v-select
            label="Choose Action Type"
            v-model="form.actionType"
            :items="ACTION_TYPES"
            item-title="label"
            item-value="id"
            hide-details
            density="compact"
          ></v-select>
          <v-select
            v-if="form.actionType === ButtonActionTypesEnum.OPEN_INTERNAL_PAGE"
            v-model="form.page_id as any"
            label="Choose internal page"
            :items="pages"
            item-title="name"
            item-value="id"
            hide-details
            density="compact"
          />

          <v-select
            v-if="form.actionType === ButtonActionTypesEnum.OPEN_OVERLAY"
            v-model="form.overlay"
            label="Overlay"
            :items="availableOverlays"
            item-title="label"
            item-value="id"
            hide-details
            density="compact"
          />

          <div
            class="d-flex w-100 flex-column"
            v-if="form.actionType === ButtonActionTypesEnum.OPEN_EXTERNAL_PAGE"
          >
            <v-textarea
              v-model="form.url"
              label="External URL Page"
              hide-details
              density="compact"
            />
            <div class="d-flex ga-1">
              <v-checkbox
                v-model="form.isSameTab"
                label="Same Tab?"
                hide-details
                false-value="0"
                true-value="1"
                density="compact"
              />
              <v-checkbox
                v-model="form.noFollow"
                label="No Follow?"
                hide-details
                false-value="0"
                true-value="1"
                density="compact"
              />
            </div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-btn color="blue" flat @click.prevent="save" :disabled="loading"
            >Save</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-col>
  </v-row>
</template>
