<script setup lang="ts">
import type {
  BonusCampaignType,
  BonusConsumePriority,
  BonusRewardType,
  BonusRule,
  BonusRulePayload,
  BonusStackingPolicy,
  BonusTriggerType,
  BonusWinDestination,
} from "~/types/bonuses";

const props = defineProps<{
  modelValue: boolean;
  item?: BonusRule | BonusRulePayload | null;
  saving?: boolean;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  save: [payload: BonusRulePayload];
}>();

const triggerOptions: Array<{ title: string; value: BonusTriggerType | string }> =
  [
    { title: "Register", value: "register" },
    { title: "Deposit", value: "deposit" },
    { title: "First Deposit", value: "first_deposit" },
    { title: "Manual", value: "manual" },
    { title: "Custom", value: "custom" },
  ];

const rewardOptions: Array<{ title: string; value: BonusRewardType }> = [
  { title: "Fixed Amount", value: "fixed_amount" },
  { title: "Percentage", value: "percentage" },
];

const stackingOptions: Array<{ title: string; value: BonusStackingPolicy }> = [
  { title: "Stackable", value: "stackable" },
  { title: "Exclusive", value: "exclusive" },
];

const campaignTypeOptions: Array<{ title: string; value: BonusCampaignType }> = [
  { title: "Register Bonus", value: "register_bonus" },
  { title: "Deposit Bonus", value: "deposit_bonus" },
  { title: "Custom", value: "custom" },
];

const consumePriorityOptions: Array<{ title: string; value: BonusConsumePriority }> = [
  { title: "Real first", value: "real_first" },
  { title: "Bonus first", value: "bonus_first" },
];

const winDestinationOptions: Array<{ title: string; value: BonusWinDestination }> = [
  { title: "Bonus wallet", value: "bonus_wallet" },
  { title: "Real wallet", value: "real_wallet" },
];

const form = reactive<BonusRulePayload>({
  id: null,
  name: "",
  trigger_type: "register",
  campaign_type: "register_bonus",
  condition_json: { register: true },
  reward_type: "fixed_amount",
  reward_value: 0,
  currency_id: "",
  currency_code: "",
  max_reward_amount: null,
  deposit_bonus_multiplier: null,
  wagering_multiplier: 1,
  real_wager_multiplier: null,
  bonus_wager_multiplier: null,
  consume_priority: "bonus_first",
  win_destination: "real_wallet",
  max_convert_to_real_ui: null,
  expire_after_days: null,
  valid_from: null,
  valid_until: null,
  priority: 10,
  stacking_policy: "stackable",
  is_active: true,
});

const conditionText = ref("{\n  \"register\": true\n}");
const error = ref("");

const toDatetimeValue = (value: string | null | undefined): string => {
  if (!value) return "";
  return value.replace(" ", "T").slice(0, 16);
};

const parseCondition = (): Record<string, unknown> | null => {
  try {
    const value = JSON.parse(conditionText.value || "{}");
    if (!value || typeof value !== "object" || Array.isArray(value)) {
      error.value = "Condition JSON must be an object.";
      return null;
    }

    return value as Record<string, unknown>;
  } catch (_err) {
    error.value = "Condition JSON is invalid.";
    return null;
  }
};

const fieldErrors = computed(() => {
  const errors: Record<string, string> = {};

  if (!form.name.trim()) {
    errors.name = "Rule name is required.";
  }

  if (!["fixed_amount", "percentage"].includes(form.reward_type)) {
    errors.reward_type = "Reward type is invalid.";
  }

  if (!["stackable", "exclusive"].includes(form.stacking_policy)) {
    errors.stacking_policy = "Stacking policy is invalid.";
  }

  if (Number(form.reward_value) <= 0) {
    errors.reward_value = "Reward value must be greater than 0.";
  }

  if (!String(form.currency_id || "").trim()) {
    errors.currency_id = "Currency ID is required.";
  }

  if (!String(form.currency_code || "").trim()) {
    errors.currency_code = "Currency code is required.";
  }

  if (Number(form.wagering_multiplier) < 0) {
    errors.wagering_multiplier = "Wagering multiplier cannot be negative.";
  }

  if (
    form.deposit_bonus_multiplier !== null &&
    Number(form.deposit_bonus_multiplier) < 0
  ) {
    errors.deposit_bonus_multiplier = "Deposit bonus multiplier cannot be negative.";
  }

  if (
    form.real_wager_multiplier !== null &&
    Number(form.real_wager_multiplier) < 0
  ) {
    errors.real_wager_multiplier = "Real wager multiplier cannot be negative.";
  }

  if (
    form.bonus_wager_multiplier !== null &&
    Number(form.bonus_wager_multiplier) < 0
  ) {
    errors.bonus_wager_multiplier = "Bonus wager multiplier cannot be negative.";
  }

  if (
    form.max_convert_to_real_ui !== null &&
    Number(form.max_convert_to_real_ui) < 0
  ) {
    errors.max_convert_to_real_ui = "Max convert to real cannot be negative.";
  }

  if (form.expire_after_days !== null && Number(form.expire_after_days) < 0) {
    errors.expire_after_days = "Expire after days cannot be negative.";
  }

  if (form.valid_from && form.valid_until && form.valid_from > form.valid_until) {
    errors.valid_until = "Valid until must be later than valid from.";
  }

  if (Number(form.priority) < 0) {
    errors.priority = "Priority must be at least 0.";
  }

  if (form.max_reward_amount !== null && Number(form.max_reward_amount) <= 0) {
    errors.max_reward_amount = "Max reward amount must be greater than 0.";
  }

  return errors;
});

const applyItem = () => {
  const item = props.item;

  if (!item) {
    Object.assign(form, {
      id: null,
      name: "",
      trigger_type: "register",
      campaign_type: "register_bonus",
      condition_json: { register: true },
      reward_type: "fixed_amount",
      reward_value: 0,
      currency_id: "",
      currency_code: "",
      max_reward_amount: null,
      deposit_bonus_multiplier: null,
      wagering_multiplier: 1,
      real_wager_multiplier: null,
      bonus_wager_multiplier: null,
      consume_priority: "bonus_first",
      win_destination: "real_wallet",
      max_convert_to_real_ui: null,
      expire_after_days: null,
      valid_from: null,
      valid_until: null,
      priority: 10,
      stacking_policy: "stackable",
      is_active: true,
    });
    conditionText.value = JSON.stringify(form.condition_json, null, 2);
    return;
  }

  Object.assign(form, {
    id: item.id,
    name: item.name ?? "",
    trigger_type: item.trigger_type ?? "register",
    campaign_type: item.campaign_type ?? "register_bonus",
    condition_json: item.condition_json ?? {},
    reward_type: item.reward_type ?? "fixed_amount",
    reward_value: Number(item.reward_value ?? 0),
    currency_id: item.currency_id ?? "",
    currency_code: item.currency_code ?? "",
    max_reward_amount:
      item.max_reward_amount === null ? null : Number(item.max_reward_amount),
    deposit_bonus_multiplier:
      item.deposit_bonus_multiplier === null ||
      item.deposit_bonus_multiplier === undefined
        ? null
        : Number(item.deposit_bonus_multiplier),
    wagering_multiplier: Number(item.wagering_multiplier ?? 0),
    real_wager_multiplier:
      item.real_wager_multiplier === null ||
      item.real_wager_multiplier === undefined
        ? null
        : Number(item.real_wager_multiplier),
    bonus_wager_multiplier:
      item.bonus_wager_multiplier === null ||
      item.bonus_wager_multiplier === undefined
        ? null
        : Number(item.bonus_wager_multiplier),
    consume_priority: item.consume_priority ?? "bonus_first",
    win_destination: item.win_destination ?? "real_wallet",
    max_convert_to_real_ui:
      item.max_convert_to_real_ui === null ||
      item.max_convert_to_real_ui === undefined
        ? null
        : Number(item.max_convert_to_real_ui),
    expire_after_days:
      item.expire_after_days === null || item.expire_after_days === undefined
        ? null
        : Number(item.expire_after_days),
    valid_from: toDatetimeValue(item.valid_from),
    valid_until: toDatetimeValue(item.valid_until),
    priority: Number(item.priority ?? 0),
    stacking_policy: item.stacking_policy ?? "stackable",
    is_active: Boolean(item.is_active),
  });

  conditionText.value = JSON.stringify(form.condition_json ?? {}, null, 2);
};

const close = () => emit("update:modelValue", false);

const save = () => {
  error.value = "";

  const condition = parseCondition();
  if (!condition) {
    return;
  }

  if (Object.keys(fieldErrors.value).length) {
    error.value = "Please fix all validation errors before saving.";
    return;
  }

  emit("save", {
    ...form,
    condition_json: condition,
    valid_from: form.valid_from || null,
    valid_until: form.valid_until || null,
    currency_id: String(form.currency_id || "").trim(),
    currency_code: String(form.currency_code || "").trim(),
  });
};

watch(
  () => props.modelValue,
  (value) => {
    if (value) {
      error.value = "";
      applyItem();
    }
  },
  { immediate: true },
);
</script>

<template>
  <v-dialog :model-value="modelValue" max-width="960" @update:model-value="emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>{{ item ? "Edit rule" : "Create rule" }}</span>
        <v-btn icon="ph-x" variant="text" @click="close" />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.name"
              label="Rule name"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.name"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="form.trigger_type"
              :items="triggerOptions"
              item-title="title"
              item-value="value"
              label="Trigger"
              variant="outlined"
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="form.campaign_type"
              :items="campaignTypeOptions"
              item-title="title"
              item-value="value"
              label="Campaign type"
              variant="outlined"
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="form.reward_type"
              :items="rewardOptions"
              item-title="title"
              item-value="value"
              label="Reward type"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.reward_type"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.reward_value"
              type="number"
              label="Reward value"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.reward_value"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="form.currency_id"
              label="Currency ID"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.currency_id"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-text-field
              v-model="form.currency_code"
              label="Currency code"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.currency_code"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.max_reward_amount"
              type="number"
              label="Max reward amount"
              variant="outlined"
              density="comfortable"
              clearable
              :error-messages="fieldErrors.max_reward_amount"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.deposit_bonus_multiplier"
              type="number"
              label="Deposit bonus multiplier"
              variant="outlined"
              density="comfortable"
              clearable
              :error-messages="fieldErrors.deposit_bonus_multiplier"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.wagering_multiplier"
              type="number"
              label="Wagering multiplier"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.wagering_multiplier"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.real_wager_multiplier"
              type="number"
              label="Real wager multiplier"
              variant="outlined"
              density="comfortable"
              clearable
              :error-messages="fieldErrors.real_wager_multiplier"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.bonus_wager_multiplier"
              type="number"
              label="Bonus wager multiplier"
              variant="outlined"
              density="comfortable"
              clearable
              :error-messages="fieldErrors.bonus_wager_multiplier"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.priority"
              type="number"
              label="Priority"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.priority"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="form.stacking_policy"
              :items="stackingOptions"
              item-title="title"
              item-value="value"
              label="Stacking policy"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.stacking_policy"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="form.consume_priority"
              :items="consumePriorityOptions"
              item-title="title"
              item-value="value"
              label="Consume priority"
              variant="outlined"
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="form.win_destination"
              :items="winDestinationOptions"
              item-title="title"
              item-value="value"
              label="Win destination"
              variant="outlined"
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="3" class="d-flex align-center">
            <v-switch v-model="form.is_active" label="Active" inset color="success" />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.max_convert_to_real_ui"
              type="number"
              label="Max convert to real"
              variant="outlined"
              density="comfortable"
              clearable
              :error-messages="fieldErrors.max_convert_to_real_ui"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.expire_after_days"
              type="number"
              label="Expire after days"
              variant="outlined"
              density="comfortable"
              clearable
              :error-messages="fieldErrors.expire_after_days"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.valid_from"
              label="Valid from"
              type="datetime-local"
              variant="outlined"
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.valid_until"
              label="Valid until"
              type="datetime-local"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.valid_until"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="conditionText"
              label="Condition JSON"
              rows="7"
              variant="outlined"
              auto-grow
            />
          </v-col>
        </v-row>

        <v-alert v-if="error" type="error" variant="tonal" density="comfortable">
          {{ error }}
        </v-alert>
      </v-card-text>
      <v-divider />
      <v-card-actions class="justify-end">
        <v-btn variant="text" @click="close">Cancel</v-btn>
        <v-btn color="primary" :loading="saving" @click="save">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
