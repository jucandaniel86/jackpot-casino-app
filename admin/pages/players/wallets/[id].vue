<script setup lang="ts">
//impots
import moment from "moment";

//models
const loading = ref<boolean>(false);
const wallets = ref<any[]>([]);

//composables
const route = useRoute();
const router = useRouter();

//methods
const getWallets = async (): Promise<void> => {
  loading.value = true;
  const playerID = route.params.id;
  const { data } = await useAPIFetch("/players/wallets", {
    id: playerID,
  });

  if (!data.success) {
    useNuxtApp().$toast.success(data.msg);
    return;
  }
  wallets.value = data.data.wallets;
  loading.value = false;
};
const backToPlayers = () => router.push("/players");
const formatData = (date: string) => moment(date).format("Y-m-d H:MM:s");

//mounted
onMounted(() => {
  getWallets();
});

//meta
useHead({
  title: "Player Wallets",
});
</script>
<template>
  <v-row>
    <v-col cols="12">
      <v-card-title class="d-flex justify-space-between">
        <h3 class="text-h6">Player Wallet</h3>
        <v-btn @click.prevent="backToPlayers" color="blue" size="small">
          <v-icon icon="mdi-arrow-left" color="white" />
          <span>Back to players</span>
        </v-btn>
      </v-card-title>
    </v-col>
    <v-col cols="6" v-for="wallet in wallets" :key="wallet.wallet_uuid">
      <v-card elevation="0">
        <v-card-title class="d-flex justify-space-between">
          <div class="d-flex flex-column">
            <span>
              <v-icon
                :icon="`mdi-currency-${String(wallet.currency).toLowerCase()}`"
              />
              Wallet {{ wallet.currency }}
            </span>
          </div>
          <span class="font-weight-bold">Balance: {{ wallet.balance }}</span>
        </v-card-title>
        <v-card-text>
          <v-table>
            <tr>
              <td width="25%">ID:</td>
              <td>
                <v-chip size="x-small" class="mt-1 flex">{{
                  wallet.name
                }}</v-chip>
              </td>
            </tr>
            <tr>
              <td width="25%">Currency</td>
              <td>{{ wallet.type.name }}</td>
            </tr>
            <tr>
              <td width="25%">Fiat</td>
              <td>
                <v-chip size="x-small">{{
                  wallet.type.is_fiat ? "YES" : "NO"
                }}</v-chip>
              </td>
            </tr>
            <tr>
              <td width="25%">Min. Amount</td>
              <td>{{ wallet.type.min_amount }}</td>
            </tr>
            <tr>
              <td width="25%">Created at</td>
              <td>{{ formatData(wallet.created_at) }}</td>
            </tr>
            <tr
              v-if="
                wallet.type.network_data && wallet.type.network_data.length > 0
              "
            >
              <td>Network Data</td>
              <td>
                <div class="d-flex w-100 ga-1 justyf-space-between flex-column">
                  <div
                    v-for="network in wallet.type.network_data"
                    class="w-100 mt-1 mb-1 border pa-2"
                  >
                    <div class="d-flex justify-space-between ga-1 w-100">
                      <span>Code:</span>
                      <span class="font-weight-bold">{{ network.code }}</span>
                    </div>
                    <div class="d-flex justify-space-between ga-1 w-100">
                      <span>Name:</span>
                      <span class="font-weight-bold">{{ network.name }}</span>
                    </div>
                    <div class="d-flex justify-space-between ga-1 w-100">
                      <span>Min. Amount:</span>
                      <span class="font-weight-bold">{{
                        network.minAmount
                      }}</span>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </v-table>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>
