<script setup lang="ts">
import moment from "moment";

//models
const loading = ref<boolean>(false);
const activity = ref<any[]>([]);
const headers = ref([
  { title: "Type", value: "type", width: "10%" },
  { title: "Description", value: "description", width: "40%" },
  { title: "IP Address", value: "ip_address", width: "15%" },
  { title: "User Agent", value: "user_agent", width: "20%" },
  { title: "Added", value: "created_at" },
]);
const height = 500;
const options = ref<any>({});
const totalItems = ref(0);
const search = ref({
  period: [
    moment().subtract(30, "days").format("YYYY-MM-DD"),
    moment().format("YYYY-MM-DD"),
  ],
});

//composables
const router = useRouter();
const route = useRoute();

//methods
const backToPlayers = () => router.push("/players");
const formatDates = (date: string) => moment(date).format("LLL");
const formatLog = (_log: any) => {
  try {
    return JSON.parse(_log);
  } catch (err) {
    return "";
  }
};
const reloadList = async () => {
  loading.value = true;
  const { page, itemsPerPage } = options.value;
  const user_id = route.params.id;

  const { success, data } = await useAPIFetch("/players/activity", {
    start: page,
    length: itemsPerPage,
    search: search.value,
    user_id,
  });
  if (success) {
    activity.value = data.data.items;
    totalItems.value = data.data.total;
  }

  loading.value = false;
};

//watchers
watch(
  options,
  () => {
    reloadList();
  },
  { deep: true }
);

//onMounted
onMounted(() => {
  reloadList();
});
</script>
<template>
  <v-col class="text-center">
    <v-card min-height="600">
      <v-card-title class="d-flex justify-space-between">
        <h3 class="text-h6">Player Activity</h3>
        <v-btn @click.prevent="backToPlayers" color="blue" size="small">
          <v-icon icon="mdi-arrow-left" color="white" />
          <span>Back to players</span>
        </v-btn>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col>
            <v-data-table
              :headers="headers"
              :items="activity"
              :options.sync="options"
              :server-items-length="totalItems"
              :loading="loading"
              class="elevation-1"
              :height="600"
              density="compact"
              fixed-header
            >
              <template v-slot:item="{ item }">
                <tr>
                  <td class="text-sm">{{ item.type }}</td>
                  <td class="text-sm truncate text-left">
                    <pre>{{ formatLog(item.description) }}</pre>
                    <br />
                    <v-row>
                      <v-col md="12" col="12">
                        <span class="">
                          Country: <b>{{ item.country }}</b> City:
                          <b>{{ item.city }}</b> OS:
                          <b>{{ item.os }}</b> Device:
                          <b>{{ item.device }}</b> Browser:
                          <b>{{ item.browser }}</b>
                        </span>
                      </v-col>
                    </v-row>
                  </td>
                  <td class="text-sm">{{ item.ip_address }}</td>
                  <td class="text-sm">
                    <span class="text-sm">{{ item.user_agent }}</span>
                  </td>
                  <td class="text-sm">{{ formatDates(item.created_at) }}</td>
                </tr>
              </template>
            </v-data-table>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-col>
</template>
