<script setup lang="ts">
import moment from "moment";
import { REPORTS_HEADERS } from "./setup";

const convertDateToUTC = (_date: any) => {
  return moment(_date).format("YYYY-MM-DD HH:mm:ss");
};

//models
const headers = ref(REPORTS_HEADERS);
const period = ref([moment().subtract(1, "week"), moment()]);
const options = ref<any>({});
const loading = ref<boolean>(false);
const totalItems = ref(0);
const pageData = ref<any[]>([]);
const search = ref({
  currency: "",
  fromDate: convertDateToUTC(period.value[0]),
  toDate: convertDateToUTC(period.value[1]),
  order_by: "bets",
});

//methods
const formatDates = (date: string) => moment(date).format("LLL");
const reloadList = async () => {
  loading.value = true;
  const { page, itemsPerPage } = options.value;

  const { success, data } = await useAPIFetch("/stats/games", {
    start: page,
    length: itemsPerPage,
    from: convertDateToUTC(period.value[0]),
    to: convertDateToUTC(period.value[1]),
    order_by: search.value.order_by,
    currency: search.value.currency,
  });
  if (success) {
    pageData.value = data.items;
    totalItems.value = data.total;
  }

  loading.value = false;
};

//watchers
watch(
  options,
  () => {
    reloadList();
  },
  { deep: true },
);

//onMounted
onMounted(() => {
  reloadList();
});
</script>
<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Games Report</div>
          <div class="text-caption text-medium-emphasis">
            Wagered & Refunded & Win Report
          </div>
        </div>
        <v-btn variant="flat" color="primary" @click="reloadList">
          <v-icon start icon="mdi-refresh" />
          Refresh
        </v-btn>
      </v-card-text>
    </v-card>
    <v-card variant="elevated" color="blue-grey-lighten-5">
      <v-data-table
        :headers="headers as any"
        :items="pageData"
        :options.sync="options"
        :server-items-length="totalItems"
        :loading="loading"
        class="elevation-1"
        :height="600"
        density="compact"
        fixed-header
      >
        <template v-slot:top>
          <div class="pa-1">
            <v-row>
              <v-col cols="10" md="4">
                <SelectDatetimepicker v-model="period" />
              </v-col>
              <v-col cols="2">
                <SelectCurrencies v-model="search.currency" />
              </v-col>
              <v-col cols="2">
                <v-select
                  v-model="search.order_by"
                  :items="['bets', 'wagered']"
                  item-title="label"
                  item-value="id"
                  label="Report type"
                  hide-details
                  density="compact"
                />
              </v-col>
              <v-col cols="1" md="1">
                <v-btn
                  color="primary"
                  :min-width="20"
                  small
                  @click.prevent="reloadList"
                >
                  <v-icon color="white" small>mdi-magnify</v-icon>
                </v-btn>
              </v-col>
            </v-row>
          </div>
        </template>
        <template v-slot:item="{ item }">
          <tr>
            <td>{{ item.game_name }} ({{ item.game_id }})</td>
            <td>{{ item.bets_count }}</td>
            <td>{{ item.players_count }}</td>
            <td>{{ item.refunded }}</td>
            <td>{{ item.wagered }}</td>
            <td>{{ item.won }}</td>
            <td>{{ item.rtp_net_percent }}</td>
            <td>{{ item.rtp_percent }}</td>
          </tr>
        </template>
      </v-data-table>
    </v-card>
  </v-container>
</template>
<style scoped>
.text-sm {
  font-size: 0.8rem;
  text-align: left;
}
.transaction-win {
  color: green;
}
.transaction-bet {
  color: red;
}
.transaction-refund {
  color: orange;
}
</style>
