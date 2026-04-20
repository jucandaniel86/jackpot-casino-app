<script setup lang="ts">
import type { BonusRule, BonusRulePayload } from "~/core/types/bonuses";
import { bonusRuleHelpItems, bonusRuleReadyTemplates } from "~/core/types/bonusRuleHelp";

const bonuses = useBonusesStore();
const { rules } = storeToRefs(bonuses);
const { toastSuccess, toastError, confirmDelete } = useAlert();

const loading = computed(() => bonuses.loading.rules);
const saving = computed(() => bonuses.loading.ruleSave);
const dialogOpen = ref(false);
const helpOpen = ref(false);
const selected = ref<BonusRule | BonusRulePayload | null>(null);
const togglingIds = ref<number[]>([]);
const deletingIds = ref<number[]>([]);

const headers = [
  { title: "Name", key: "name", sortable: false },
  { title: "Trigger", key: "trigger_type", sortable: false },
  { title: "Reward", key: "reward", sortable: false },
  { title: "Wagering", key: "wagering_multiplier", sortable: false },
  { title: "Priority", key: "priority", sortable: false },
  { title: "Active", key: "is_active", sortable: false },
  { title: "Actions", key: "actions", sortable: false },
];

const openCreate = () => {
  selected.value = null;
  dialogOpen.value = true;
};

const onOpenInForm = (ruleType: string) => {
  const fromHelp = bonusRuleHelpItems.find((item) => item.id === ruleType);
  const fromTemplate = bonusRuleReadyTemplates.find((item) => item.id === ruleType);
  const source = fromHelp?.exampleRule ?? fromTemplate?.payload ?? null;

  if (!source) {
    toastError("Template not found.");
    return;
  }

  selected.value = {
    id: null,
    name: String(source.name ?? ""),
    trigger_type: String(source.trigger_type ?? "register"),
    campaign_type: (source.campaign_type as string) ?? "custom",
    condition_json:
      source.condition_json && typeof source.condition_json === "object"
        ? (source.condition_json as Record<string, unknown>)
        : { register: true },
    reward_type:
      source.reward_type === "percentage" ? "percentage" : "fixed_amount",
    reward_value: Number(source.reward_value ?? 0),
    currency_id: (source.currency_id as string) ?? "",
    currency_code: (source.currency_code as string) ?? "",
    max_reward_amount:
      source.max_reward_amount === undefined || source.max_reward_amount === null
        ? null
        : Number(source.max_reward_amount),
    deposit_bonus_multiplier:
      source.deposit_bonus_multiplier === undefined ||
      source.deposit_bonus_multiplier === null
        ? null
        : Number(source.deposit_bonus_multiplier),
    wagering_multiplier: Number(source.wagering_multiplier ?? 1),
    real_wager_multiplier:
      source.real_wager_multiplier === undefined ||
      source.real_wager_multiplier === null
        ? null
        : Number(source.real_wager_multiplier),
    bonus_wager_multiplier:
      source.bonus_wager_multiplier === undefined ||
      source.bonus_wager_multiplier === null
        ? null
        : Number(source.bonus_wager_multiplier),
    consume_priority: (source.consume_priority as string) ?? "bonus_first",
    win_destination: (source.win_destination as string) ?? "real_wallet",
    max_convert_to_real_ui:
      source.max_convert_to_real_ui === undefined ||
      source.max_convert_to_real_ui === null
        ? null
        : Number(source.max_convert_to_real_ui),
    expire_after_days:
      source.expire_after_days === undefined || source.expire_after_days === null
        ? null
        : Number(source.expire_after_days),
    valid_from: (source.valid_from as string) ?? null,
    valid_until: (source.valid_until as string) ?? null,
    priority: Number(source.priority ?? 10),
    stacking_policy:
      source.stacking_policy === "exclusive" ? "exclusive" : "stackable",
    is_active: Boolean(source.is_active ?? true),
  };
  dialogOpen.value = true;
  toastSuccess(`Template loaded.`);
};

const openEdit = async (item: BonusRule) => {
  const api = useBonusesApi();
  const result = await api.getRule(item.id);

  if (result.success && result.data) {
    selected.value = result.data;
    dialogOpen.value = true;
    return;
  }

  toastError(result.message || "Failed to load rule details.");
};

const onSave = async (payload: BonusRulePayload) => {
  const result = await bonuses.saveRule(payload);

  if (!result.success) {
    toastError(result.message || "Failed to save rule.");
    return;
  }

  toastSuccess(result.message || "Rule saved.");
  dialogOpen.value = false;
};

const onDelete = (item: BonusRule) => {
  confirmDelete(async () => {
    deletingIds.value.push(item.id);
    const result = await bonuses.deleteRule(item.id);
    deletingIds.value = deletingIds.value.filter((id) => id !== item.id);

    if (!result.success) {
      toastError(result.message || "Failed to delete rule.");
      return;
    }

    toastSuccess(result.message || "Rule deleted.");
  });
};

const onToggle = async (item: BonusRule, next: boolean) => {
  togglingIds.value.push(item.id);
  const result = await bonuses.toggleRule(item.id, next);
  togglingIds.value = togglingIds.value.filter((id) => id !== item.id);

  if (!result.success) {
    toastError(result.message || "Failed to toggle rule.");
    return;
  }

  toastSuccess(result.message || "Rule updated.");
};

onMounted(async () => {
  const result = await bonuses.fetchRules();
  if (!result.success) {
    toastError(result.message || "Failed to load rules.");
  }
});
</script>

<template>
  <v-card>
    <v-card-title class="d-flex justify-space-between align-center">
      <span>Bonus Rules</span>
      <div class="d-flex ga-2">
        <v-btn variant="tonal" prepend-icon="ph-question" @click="helpOpen = true">
          Help
        </v-btn>
        <v-btn color="primary" prepend-icon="ph-plus" @click="openCreate">
          Create rule
        </v-btn>
      </div>
    </v-card-title>
    <v-divider />

    <v-card-text>
      <v-skeleton-loader v-if="loading" type="table" />

      <v-data-table
        v-else
        :headers="headers"
        :items="rules"
        density="comfortable"
        item-key="id"
      >
        <template #item.reward="{ item }">
          <span>
            {{ item.reward_type }}: {{ item.reward_value }}
            {{ item.currency_code || "" }}
          </span>
        </template>

        <template #item.is_active="{ item }">
          <v-switch
            :model-value="Boolean(item.is_active)"
            color="success"
            inset
            density="comfortable"
            :loading="togglingIds.includes(item.id)"
            @update:model-value="onToggle(item, Boolean($event))"
          />
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex ga-2">
            <v-btn
              icon="ph-pencil"
              size="small"
              color="info"
              variant="tonal"
              @click="openEdit(item)"
            />
            <v-btn
              icon="ph-trash"
              size="small"
              color="error"
              variant="tonal"
              :loading="deletingIds.includes(item.id)"
              @click="onDelete(item)"
            />
          </div>
        </template>

        <template #no-data>
          <div class="text-medium-emphasis py-6">No rules found.</div>
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>

  <BonusesRuleFormDialog
    v-model="dialogOpen"
    :item="selected"
    :saving="saving"
    @save="onSave"
  />
  <BonusRulesHelpModal v-model="helpOpen" @open-in-form="onOpenInForm" />
</template>
