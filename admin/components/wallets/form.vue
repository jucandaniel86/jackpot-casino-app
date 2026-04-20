<script setup lang="ts">
import type { WalletType } from "./types";

//props
type WalletFormType = {
  item?: WalletType;
};
const props = defineProps<WalletFormType>();

//models
const loading = ref<boolean>(false);
const form = ref<WalletType>({
  name: "",
  code: "",
  symbol: "",
  is_fiat: 0,
  precision: 2,
  min_amount: 0,
  id: 0,
  supports_tag: 0,
  network_data: [],
  active: 1,
  icon: "",
  wallet_uuid: "",
  currency_id: "",
  purpose: "real",
});

//emitters
const emitters = defineEmits(["wallet:close-modal", "wallet:save"]);
const save = () => emitters("wallet:save", form.value);

//methods
const addNetwork = () => {
  form.value.network_data.push({
    code: "",
    name: "",
    minAmount: 0,
  });
};
const deleteNetwork = (index: number) => {
  form.value.network_data.splice(index, 1);
};

watch(props, () => {
  form.value = {
    ...form.value,
    ...props.item,
  };
});

onMounted(() => {
  form.value = {
    ...form.value,
    ...props.item,
  };
});
</script>
<template>
  <v-card title="Save Wallet" density="compact">
    <v-card-text>
      <v-text-field
        v-model="form.name"
        label="Name"
        required
        density="compact"
        hide-details
      ></v-text-field>
      <v-text-field
        v-model="form.code"
        label="Code"
        required
        density="compact"
        hide-details
      ></v-text-field>
      <v-select
        v-model="form.purpose"
        :items="['real', 'bonus']"
        label="Purspose"
      />
      <v-text-field
        v-model="form.currency_id"
        label="Internal Code"
        required
        density="compact"
        hide-details
      ></v-text-field>
      <v-text-field
        v-model="form.symbol"
        label="Symbol"
        required
        density="compact"
        hide-details
      ></v-text-field>
      <v-text-field
        v-model="form.min_amount"
        type="number"
        label="Min. Amount"
        required
        density="compact"
        hide-details
      ></v-text-field>
      <v-text-field
        v-model="form.precision"
        type="number"
        label="Precision"
        required
        density="compact"
        hide-details
      ></v-text-field>
      <div class="d-flex w-100 justify-space-between ga-1">
        <v-checkbox
          v-model="form.is_fiat"
          :false-value="0"
          :true-value="1"
          hide-details
          density="compact"
          label="Is Fiat"
        />
        <v-checkbox
          v-model="form.supports_tag"
          :false-value="0"
          :true-value="1"
          hide-details
          density="compact"
          label="Support Tags"
        />
        <v-checkbox
          v-model="form.active"
          :false-value="0"
          :true-value="1"
          hide-details
          density="compact"
          label="Active"
        />
      </div>
      <v-btn
        @click.prevent="addNetwork"
        text="Add network"
        prepend-icon="mdi-plus"
        density="compact"
        variant="tonal"
        color="blue"
        class="mb-1"
      />

      <v-table density="compact">
        <tbody>
          <tr v-for="(network, i) in form.network_data" :key="`Network${i}`">
            <td width="25%" class="pl-0 pr-1">
              <v-text-field
                v-model="network.code"
                label="Code"
                hide-details
                density="compact"
              />
            </td>
            <td width="33.3%" class="pl-0 pr-1">
              <v-text-field
                v-model="network.name"
                label="Name"
                hide-details
                flat
                density="compact"
              />
            </td>
            <td class="pl-0 pr-1" width="29%">
              <v-text-field
                v-model="network.minAmount"
                type="number"
                label="Min. Amount"
                hide-details
                density="compact"
              />
            </td>
            <td>
              <v-btn
                density="compact"
                variant="tonal"
                color="red"
                icon="mdi-delete-outline"
                @click="deleteNetwork(i)"
              />
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card-text>
    <v-card-actions class="d-flex justify-space-between align-center">
      <v-btn
        :disabled="loading"
        @click="save"
        density="compact"
        variant="flat"
        color="blue"
        >Save</v-btn
      >
      <v-btn
        @click="emitters('wallet:close-modal')"
        color="red"
        density="compact"
        variant="flat"
        :disabled="loading"
        >Close</v-btn
      >
    </v-card-actions>
  </v-card>
</template>
