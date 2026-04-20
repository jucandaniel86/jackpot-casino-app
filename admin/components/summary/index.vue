<script setup lang="ts">
import moment from "moment";
import {
  DEFAULT_REPORT,
  REPORT_CURRENCIES,
  REPORT_TYPES,
  REPORTS_HEADERS,
  ReportTypeEnums,
} from "./setup";

const convertDateToUTC = (_date: any) => {
  return moment(_date).format("YYYY-MM-DD HH:mm:ss");
};

//models
const period = ref([moment().subtract(1, "week"), moment()]);
const options = ref<any>({});
const loading = ref<boolean>(false);
const totalItems = ref(0);
const pageData = ref<any[]>([]);
const reportType = ref<ReportTypeEnums>(DEFAULT_REPORT);
const search = ref({
  search: {
    item: "",
    column: "user",
  },
  currency: "",

  fromDate: convertDateToUTC(period.value[0]),
  toDate: convertDateToUTC(period.value[1]),

  report: DEFAULT_REPORT,
});

//methods
const formatDates = (date: string) => moment(date).format("LLL");
const reloadList = async () => {
  loading.value = true;
  search.value.report = reportType.value;
  const { page, itemsPerPage } = options.value;

  const { success, data } = await useAPIFetch("/bets/search", {
    start: page,
    length: itemsPerPage,
    search: JSON.stringify(search.value.search),
    fromDate: convertDateToUTC(period.value[0]),
    toDate: convertDateToUTC(period.value[1]),
    report: reportType.value,
  });
  if (success) {
    pageData.value = data.data.items;
    totalItems.value = data.data.total;
  }

  loading.value = false;
};

const updateSearch = (payload: any) => (search.value.search = payload);

const formatTransactionType = (transactionType: string, payout: number) => {
	switch(transactionType) {
		case 'win': {
			if(payout > 0) 
				return 'win';
			return 'lose';
		}
		default: return transactionType;
	}
}

//computed
const headers = computed(() => {
  return REPORTS_HEADERS.find((headers) => headers.id === search.value.report)
    ?.headers;
});

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
          <div class="text-h5 font-weight-bold">Summary Report</div>
          <div class="text-caption text-medium-emphasis">
            Transactions & users & games & sessions
          </div>
        </div>
        <v-btn variant="flat" color="primary" @click="reloadList">
          <v-icon start icon="mdi-refresh" />
          Refresh
        </v-btn>
      </v-card-text>
    </v-card>
    <v-card min-height="600">
      <v-card-title class="d-flex justify-space-between">
        <h3 class="text-h6">Report</h3>
      </v-card-title>
      <v-card-text>
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
                <v-col cols="10" md="7">
                  <SelectSearch @search:update="updateSearch" />
                </v-col>
                <v-col cols="10" md="5">
                  <SelectDatetimepicker v-model="period" />
                </v-col>
                <v-col cols="2">
                  <v-select
                    v-model="search.currency"
                    :items="REPORT_CURRENCIES"
                    label="Currency"
                    hide-details
                    density="compact"
                  />
                </v-col>
                <v-col cols="2">
                  <v-select
                    v-model="reportType"
                    :items="REPORT_TYPES"
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
            <template v-if="search.report === ReportTypeEnums.TRANSACTIONS">
              <tr>
                <td class="text-sm">
                  <span
                    >Transaction ID: <b>{{ item.transaction_id }}</b></span
                  >
                  <br />
                  <span
                    >Operator Transaction ID:
                    <b>{{ item.operator_transaction_id }}</b></span
                  >
                </td>
                <td class="text-sm">
                  {{ item.operator_round_id }}
                </td>
                <td class="text-sm">
                  <span v-if="item.username">{{ item.username }}</span> <br />
                  <b v-if="item.fixed_id">{{ item.fixed_id }}</b>
                </td>
                <td class="text-sm">
                  <span v-if="item.game_name">{{ item.game_name }}</span>
                </td>
                <td class="text-sm">
                  {{ item.currency }}
                </td>
                <td class="text-sm">
                  {{ Number(item.stake).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ Number(item.payout).toFixed(2) }}
                </td>
                <td class="text-sm">
                  <v-chip
                    :class="`transaction-${formatTransactionType(item.transaction_type, item.payout)}`"
                    class="pa-1"
                    size="sm"
                    >{{ formatTransactionType(item.transaction_type, item.payout) }}</v-chip
                  >
                </td>
                <td class="text-sm">
                  {{ formatDates(item.when_placed) }}
                </td>
              </tr>
            </template>
            <template v-if="search.report === ReportTypeEnums.GAMES">
              <tr>
                <td class="text-sm">
                  <span v-if="item.game_name">{{ item.game_name }}</span>
                </td>
                <td class="text-sm">
                  {{ item.currency }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_stake).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_payout).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_stake - item.total_payout).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ item.total_bets }}
                </td>
              </tr>
            </template>
            <template v-if="search.report === ReportTypeEnums.USERS">
              <tr>
                <td class="text-sm">
                  <span v-if="item.username">{{ item.username }}</span>
                </td>
                <td class="text-sm">
                  {{ item.currency }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_stake).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_payout).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_stake - item.total_payout).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ item.total_bets }}
                </td>
              </tr>
            </template>
            <template v-if="search.report === ReportTypeEnums.SESSION">
              <tr>
                <td class="text-sm">
                  <span v-if="item.session_id">{{ item.session_id }}</span>
                </td>
                <td class="text-sm">
                  {{ item.currency }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_stake).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_payout).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ Number(item.total_stake - item.total_payout).toFixed(2) }}
                </td>
                <td class="text-sm">
                  {{ item.total_bets }}
                </td>
              </tr>
            </template>
          </template>
        </v-data-table>
      </v-card-text>
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
.transaction-lose {
  color: purple;
}
</style>
