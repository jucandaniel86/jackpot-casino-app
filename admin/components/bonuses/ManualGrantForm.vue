<script setup lang="ts">
import type {
  BonusManualFilters,
  BonusManualGrantAmountPayload,
  BonusManualGrantRulePayload,
  BonusSkipReasonItem,
} from "~/types/bonuses";
import { mapBonusSkipReason } from "~/utils/bonusSkipReasonMap";

const bonuses = useBonusesStore();
const { rules, preview, lastGrantResult } = storeToRefs(bonuses);
const { toastError, toastSuccess } = useAlert();

const form = reactive({
  name: "",
  mode: "rule" as "rule" | "amount",
  rule_id: null as number | null,
  currency_id: "",
  amount_ui: null as number | null,
  wagering_multiplier: 1,
  expires_at: "",
  player_ids: "",
  usernames: "",
  emails: "",
  active: "" as "" | "0" | "1",
  registered_from: "",
  registered_to: "",
  min_total_deposit_ui: null as number | null,
});

const loadingPreview = computed(() => bonuses.loading.manualPreview);
const loadingGrant = computed(() => bonuses.loading.manualGrant);
const selectedSkipReasonCode = ref<string>("all");

const parseNumberList = (value: string): number[] => {
  if (!value.trim()) return [];

  return value
    .split(",")
    .map((part) => part.trim())
    .filter(Boolean)
    .map((part) => Number(part))
    .filter((num) => Number.isFinite(num) && num > 0);
};

const parseTextList = (value: string): string[] => {
  if (!value.trim()) return [];

  return value
    .split(",")
    .map((part) => part.trim())
    .filter(Boolean);
};

const filters = computed<BonusManualFilters>(() => {
  const parsed: BonusManualFilters = {};

  const playerIds = parseNumberList(form.player_ids);
  const usernames = parseTextList(form.usernames);
  const emails = parseTextList(form.emails);

  if (playerIds.length) parsed.player_ids = playerIds;
  if (usernames.length) parsed.usernames = usernames;
  if (emails.length) parsed.emails = emails;

  if (form.active === "0" || form.active === "1") {
    parsed.active = Number(form.active) as 0 | 1;
  }

  if (form.registered_from) {
    parsed.registered_from = form.registered_from;
  }

  if (form.registered_to) {
    parsed.registered_to = form.registered_to;
  }

  if (form.min_total_deposit_ui !== null && form.min_total_deposit_ui >= 0) {
    parsed.min_total_deposit_ui = form.min_total_deposit_ui;
  }

  return parsed;
});

const fieldErrors = computed(() => {
  const errors: Record<string, string> = {};

  if (!form.name.trim()) {
    errors.name = "Batch name is required.";
  }

  if (form.mode === "rule" && !form.rule_id) {
    errors.rule_id = "Rule is required for mode=rule.";
  }

  if (form.mode === "amount") {
    if (!form.currency_id.trim()) {
      errors.currency_id = "Currency ID is required for mode=amount.";
    }

    if (form.amount_ui === null || Number(form.amount_ui) <= 0) {
      errors.amount_ui = "Amount must be greater than 0 for mode=amount.";
    }

    if (Number(form.wagering_multiplier) < 0) {
      errors.wagering_multiplier = "Wagering multiplier cannot be negative.";
    }
  }

  if (
    form.registered_from &&
    form.registered_to &&
    form.registered_from > form.registered_to
  ) {
    errors.registered_to = "registered_to must be after registered_from.";
  }

  return errors;
});

const hasValidFilters = computed(() => Object.keys(filters.value).length > 0);

const validate = () => {
  if (!hasValidFilters.value) {
    toastError("At least one filter is required.");
    return false;
  }

  if (Object.keys(fieldErrors.value).length > 0) {
    toastError("Please fix validation errors.");
    return false;
  }

  return true;
};

const buildRulePayload = (): BonusManualGrantRulePayload => ({
  name: form.name.trim(),
  mode: "rule",
  rule_id: Number(form.rule_id),
  filters: filters.value,
});

const buildAmountPayload = (): BonusManualGrantAmountPayload => ({
  name: form.name.trim(),
  mode: "amount",
  currency_id: form.currency_id.trim(),
  amount_ui: Number(form.amount_ui),
  wagering_multiplier: Number(form.wagering_multiplier),
  expires_at: form.expires_at || null,
  filters: filters.value,
});

const previewGrant = async () => {
  if (!validate()) return;

  const amount =
    form.mode === "amount"
      ? Number(form.amount_ui)
      : Number(
          rules.value.find((rule) => rule.id === form.rule_id)?.reward_value ||
            0,
        );

  if (amount <= 0) {
    toastError("Preview amount must be greater than 0.");
    return;
  }

  const result = await bonuses.previewManual({
    amount_ui: amount,
    filters: filters.value,
  });

  if (!result.success) {
    toastError(result.message || "Manual preview failed.");
    return;
  }

  toastSuccess("Preview generated.");
};

const executeGrant = async () => {
  if (!validate()) return;

  const payload =
    form.mode === "rule" ? buildRulePayload() : buildAmountPayload();
  const result = await bonuses.grantManual(payload);

  if (!result.success) {
    toastError(result.message || "Manual grant failed.");
    return;
  }

  toastSuccess(result.message || "Manual grant executed.");
};

const skipReasons = computed<BonusSkipReasonItem[]>(() => {
  const items = lastGrantResult.value?.skip_reasons;
  if (!Array.isArray(items)) {
    return [];
  }

  return items;
});

const skipReasonCodeOptions = computed(() => {
  const codes = Array.from(
    new Set(skipReasons.value.map((item) => item.reason)),
  );

  return [{ title: "All reasons", value: "all" }].concat(
    codes.map((code) => ({ title: code, value: code })),
  );
});

const filteredSkipReasons = computed(() => {
  if (selectedSkipReasonCode.value === "all") {
    return skipReasons.value;
  }

  return skipReasons.value.filter(
    (item) => item.reason === selectedSkipReasonCode.value,
  );
});

const errorsCount = computed(() => {
  const errors = lastGrantResult.value?.errors;
  return Array.isArray(errors) ? errors.length : 0;
});

const hasSkippedWithoutReasons = computed(() => {
  const skipped = Number(lastGrantResult.value?.skipped ?? 0);
  return skipped > 0 && skipReasons.value.length === 0;
});

const getSeverityColor = (severity: "info" | "warning" | "error") => {
  if (severity === "error") {
    return "error";
  }
  if (severity === "warning") {
    return "warning";
  }
  return "info";
};

const copySkipReasons = async () => {
  const payload = JSON.stringify(skipReasons.value, null, 2);

  try {
    await navigator.clipboard.writeText(payload);
    toastSuccess("Skip reasons JSON copied.");
  } catch (_error) {
    toastError("Failed to copy skip reasons JSON.");
  }
};

watch(
  () => lastGrantResult.value?.skip_reasons,
  () => {
    selectedSkipReasonCode.value = "all";
  },
);

onMounted(async () => {
  if (!rules.value.length) {
    await bonuses.fetchRules();
  }
});
</script>

<template>
  <v-card>
    <v-card-title>Manual Bonus Grant</v-card-title>
    <v-divider />
    <v-card-text>
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="form.name"
            label="Batch name"
            variant="outlined"
            :error-messages="fieldErrors.name"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="form.mode"
            :items="[
              { title: 'By Rule', value: 'rule' },
              { title: 'By Amount', value: 'amount' },
            ]"
            item-title="title"
            item-value="value"
            label="Mode"
            variant="outlined"
          />
        </v-col>

        <template v-if="form.mode === 'rule'">
          <v-col cols="12" md="5">
            <v-select
              v-model="form.rule_id"
              :items="rules"
              item-title="name"
              item-value="id"
              label="Rule"
              variant="outlined"
              :error-messages="fieldErrors.rule_id"
            />
          </v-col>
        </template>

        <template v-else>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="form.currency_id"
              label="Currency ID"
              variant="outlined"
              :error-messages="fieldErrors.currency_id"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.amount_ui"
              type="number"
              label="Amount"
              variant="outlined"
              :error-messages="fieldErrors.amount_ui"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.wagering_multiplier"
              type="number"
              label="Wagering multiplier"
              variant="outlined"
              :error-messages="fieldErrors.wagering_multiplier"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="form.expires_at"
              type="datetime-local"
              label="Expires at"
              variant="outlined"
            />
          </v-col>
        </template>
      </v-row>

      <v-divider class="my-2" />
      <div class="text-subtitle-2 mb-2">Filters</div>
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="form.player_ids"
            label="Player IDs (comma-separated)"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="form.usernames"
            label="Usernames (comma-separated)"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="form.emails"
            label="Emails (comma-separated)"
            variant="outlined"
          />
        </v-col>

        <v-col cols="12" md="3">
          <v-select
            v-model="form.active"
            label="Active"
            variant="outlined"
            :items="[
              { title: 'Any', value: '' },
              { title: 'Active', value: '1' },
              { title: 'Inactive', value: '0' },
            ]"
            item-title="title"
            item-value="value"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="form.registered_from"
            type="datetime-local"
            label="Registered from"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="form.registered_to"
            type="datetime-local"
            label="Registered to"
            variant="outlined"
            :error-messages="fieldErrors.registered_to"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model.number="form.min_total_deposit_ui"
            type="number"
            label="Min total deposit"
            variant="outlined"
          />
        </v-col>
      </v-row>

      <v-alert
        v-if="!hasValidFilters"
        type="warning"
        variant="tonal"
        density="comfortable"
      >
        At least one filter is required before preview or grant.
      </v-alert>

      <v-row class="mt-2">
        <v-col cols="12" md="3">
          <v-btn
            color="info"
            block
            :loading="loadingPreview"
            @click="previewGrant"
          >
            Preview
          </v-btn>
        </v-col>
        <v-col cols="12" md="3">
          <v-btn
            color="success"
            block
            :loading="loadingGrant"
            @click="executeGrant"
          >
            Execute Grant
          </v-btn>
        </v-col>
      </v-row>

      <v-row class="mt-4">
        <v-col cols="12" md="4">
          <v-card>
            <v-card-title class="text-subtitle-2">Preview</v-card-title>
            <v-divider />
            <v-card-text>
              <v-skeleton-loader
                v-if="loadingPreview"
                type="list-item-two-line"
              />
              <template v-else-if="preview">
                <div>
                  Estimated players: {{ preview.estimated_players ?? 0 }}
                </div>
                <div>
                  Estimated amount: {{ preview.estimated_amount_ui ?? 0 }}
                  {{ preview.currency_code ?? "" }}
                </div>
              </template>
              <div v-else class="text-medium-emphasis">No preview yet.</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-card>
            <v-card-title class="text-subtitle-2"
              >Last grant result</v-card-title
            >
            <v-divider />
            <v-card-text>
              <v-skeleton-loader
                v-if="loadingGrant"
                type="list-item-two-line"
              />
              <template v-else-if="lastGrantResult">
                <v-row>
                  <v-col cols="12" md="4">
                    <v-card variant="tonal" color="success">
                      <v-card-text>
                        <div class="text-caption">Granted</div>
                        <div class="text-h6 font-weight-bold">
                          {{ lastGrantResult.granted ?? 0 }}
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-card variant="tonal" color="warning">
                      <v-card-text>
                        <div class="text-caption">Skipped</div>
                        <div class="text-h6 font-weight-bold">
                          {{ lastGrantResult.skipped ?? 0 }}
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-card variant="tonal" color="error">
                      <v-card-text>
                        <div class="text-caption">Errors</div>
                        <div class="text-h6 font-weight-bold">
                          {{ errorsCount }}
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>

                <div class="mt-3 d-flex align-center justify-space-between">
                  <div v-if="lastGrantResult.grant_id">
                    Grant ID: {{ lastGrantResult.grant_id }}
                  </div>
                  <v-btn
                    size="small"
                    variant="tonal"
                    color="primary"
                    :disabled="!skipReasons.length"
                    @click="copySkipReasons"
                  >
                    Copy raw skip_reasons JSON
                  </v-btn>
                </div>

                <v-alert
                  v-if="hasSkippedWithoutReasons"
                  type="warning"
                  variant="tonal"
                  class="mt-3"
                >
                  Some players were skipped, but no skip reasons were returned.
                </v-alert>

                <template v-if="skipReasons.length">
                  <v-row class="mt-2">
                    <v-col cols="12" md="5">
                      <v-select
                        v-model="selectedSkipReasonCode"
                        label="Filter by reason code"
                        variant="outlined"
                        density="comfortable"
                        :items="skipReasonCodeOptions"
                        item-title="title"
                        item-value="value"
                      />
                    </v-col>
                  </v-row>

                  <v-table density="comfortable">
                    <thead>
                      <tr>
                        <th>Player ID</th>
                        <th>Username</th>
                        <th>Reason</th>
                        <th>Description</th>
                        <th>Severity</th>
                        <th>Code</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(item, index) in filteredSkipReasons"
                        :key="`${item.player_id ?? 'na'}-${index}`"
                      >
                        <td>{{ item.player_id ?? "-" }}</td>
                        <td>{{ item.username || "-" }}</td>
                        <td>{{ mapBonusSkipReason(item.reason).title }}</td>
                        <td>
                          {{ mapBonusSkipReason(item.reason).description }}
                        </td>
                        <td>
                          <v-chip
                            size="small"
                            :color="
                              getSeverityColor(
                                mapBonusSkipReason(item.reason).severity,
                              )
                            "
                            variant="tonal"
                          >
                            {{ mapBonusSkipReason(item.reason).severity }}
                          </v-chip>
                        </td>
                        <td>
                          <v-chip size="small" variant="outlined">
                            {{ item.reason || "unknown_skip_reason" }}
                          </v-chip>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>
                </template>
              </template>
              <div v-else class="text-medium-emphasis">
                No grant executed yet.
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>
