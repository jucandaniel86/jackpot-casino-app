<script setup lang="ts">
import moment from "moment";

//models
const loading = ref<boolean>(false);
const sessions = ref<any[]>([]);
const headers = ref([
  { title: "Session ID", value: "type", width: "10%" },
  { title: "Game", value: "type", width: "10%" },
  { title: "Started Balance", value: "type", width: "10%" },
  { title: "Currency", value: "type", width: "10%" },
  { title: "Real Money", value: "real_money", width: "10%" },
  { title: "Demo", value: "demo", width: "10%" },
  { title: "IP Address", value: "ip_address", width: "15%" },
  { title: "User Agent", value: "user_agent", width: "20%" },
  { title: "Started at", value: "created_at" },
  { title: "Expire at", value: "expire_at" },
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

const reloadList = async () => {
  loading.value = true;
  const { page, itemsPerPage } = options.value;
  const user_id = route.params.id;

  const { success, data } = await useAPIFetch("/players/sessions", {
    start: page,
    length: itemsPerPage,
    id: user_id,
  });
  if (success) {
    sessions.value = data.data.items;
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

//head
useHead({
  title: "Player Sessions",
});
</script>
<template>
  <v-col class="text-center">
    <v-card min-height="600">
      <v-card-title class="d-flex justify-space-between">
        <h3 class="text-h6">Player Sessions</h3>
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
              :items="sessions"
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
                  <td class="text-sm">{{ item.session }}</td>
                  <td class="text-sm">
                    {{ item.game ? item.game.name : "N/A" }}
                  </td>
                  <td class="text-sm">{{ item.start_balance }}</td>
                  <td class="text-sm">
                    {{ item.wallet ? item.wallet.currency : "N/A" }}
                  </td>
                  <td class="text-sm">
                    <v-chip
                      :color="item.demo ? 'orange' : 'green'"
                      size="small"
                      >{{ item.demo ? "NO" : "YES" }}</v-chip
                    >
                  </td>
                  <td class="text-sm">
                    <v-chip
                      :color="!item.demo ? 'orange' : 'green'"
                      size="small"
                      >{{ !item.demo ? "NO" : "YES" }}</v-chip
                    >
                  </td>
                  <td class="text-sm">{{ item.ip_address }}</td>
                  <td class="text-sm">
                    <span class="text-sm">{{ item.user_agent }}</span>
                  </td>
                  <td class="text-sm">{{ formatDates(item.created_at) }}</td>
                  <td class="text-sm">{{ formatDates(item.expire_at) }}</td>
                </tr>
              </template>
            </v-data-table>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-col>
</template>
