<script setup lang="ts">
import type { BonusTestRun, BonusTestRunLog, BonusTestScenario } from "~/core/types/bonuses";

const api = useBonusesApi();
const { toastSuccess, toastError } = useAlert();

const loadingRuns = ref(false);
const runningScenario = ref(false);
const loadingLogs = ref(false);

const scenario = ref<BonusTestScenario>("register_flow_readiness");
const runs = ref<BonusTestRun[]>([]);
const totalRuns = ref(0);
const selectedRunId = ref<number | null>(null);
const selectedRun = ref<BonusTestRun | null>(null);
const logs = ref<BonusTestRunLog[]>([]);
const simulationParams = reactive({
  deposit_ui: "210",
  bet_ui: "120",
  win_ui: "180",
  real_balance_ui: "300",
});

const scenarioOptions: Array<{ title: string; value: BonusTestScenario }> = [
  { title: "Register flow readiness", value: "register_flow_readiness" },
  { title: "Deposit flow readiness", value: "deposit_flow_readiness" },
  { title: "Withdraw lock consistency", value: "withdraw_lock_consistency" },
  { title: "Wallet consumption simulation", value: "wallet_consumption_simulation" },
];

const showSimulationInputs = computed(
  () => scenario.value === "wallet_consumption_simulation" || scenario.value === "all",
);

const runStatusColor = (status: string) => {
  if (status === "passed") return "success";
  if (status === "failed") return "error";
  if (status === "running") return "info";
  return "secondary";
};

const logLevelColor = (level: string) => {
  if (level === "success") return "success";
  if (level === "error") return "error";
  if (level === "warn") return "warning";
  return "info";
};

const formatContext = (context: Record<string, unknown> | null | undefined) => {
  if (!context || Object.keys(context).length === 0) return "";
  try {
    return JSON.stringify(context, null, 2);
  } catch {
    return "";
  }
};

const fetchRuns = async () => {
  loadingRuns.value = true;
  const res = await api.getTestRuns({ page: 1, length: 50 });
  loadingRuns.value = false;

  if (!res.success || !res.data) {
    toastError(res.message || "Failed to load test runs.");
    return;
  }

  runs.value = res.data.items;
  totalRuns.value = res.data.total;

  if (selectedRunId.value && !runs.value.some((r) => r.id === selectedRunId.value)) {
    selectedRunId.value = null;
    selectedRun.value = null;
    logs.value = [];
  }
};

const fetchSelectedRun = async (silent = false) => {
  if (!selectedRunId.value) return;
  const res = await api.getTestRun(selectedRunId.value);
  if (res.success && res.data) {
    selectedRun.value = res.data;
    return;
  }

  if (!silent) {
    toastError(res.message || "Failed to load selected run.");
  }
};

const fetchLogs = async (options: { silent?: boolean } = {}) => {
  if (!selectedRunId.value) return;
  const silent = Boolean(options.silent);
  if (!silent) {
    loadingLogs.value = true;
  }

  const res = await api.getTestRunLogs(selectedRunId.value);
  if (!silent) {
    loadingLogs.value = false;
  }

  if (!res.success || !res.data) {
    if (!silent) {
      toastError(res.message || "Failed to load logs.");
    }
    return;
  }

  logs.value = res.data;
};

const runScenario = async (value: BonusTestScenario) => {
  runningScenario.value = true;
  const params =
    value === "wallet_consumption_simulation" || value === "all"
      ? { ...simulationParams }
      : {};
  const res = await api.runTestScenario(value, params);
  runningScenario.value = false;

  if (!res.success || !res.data) {
    toastError(res.message || "Failed to run scenario.");
    return;
  }

  toastSuccess("Test scenario completed.");
  await fetchRuns();
  selectedRunId.value = res.data.id;
  selectedRun.value = res.data;
  await fetchLogs();
};

const onSelectRun = async (item: BonusTestRun) => {
  selectedRunId.value = item.id;
  selectedRun.value = item;
  await fetchLogs();
};

let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(async () => {
  await fetchRuns();
  if (runs.value.length > 0) {
    selectedRunId.value = runs.value[0].id;
    selectedRun.value = runs.value[0];
    await fetchLogs();
  }

  pollTimer = setInterval(async () => {
    if (selectedRunId.value) {
      await fetchSelectedRun(true);
      await fetchLogs({ silent: true });
    }
  }, 3000);
});

onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
  <v-card>
    <v-card-title class="d-flex justify-space-between align-center">
      <span>Bonus Test Runner</span>
      <v-chip size="small" variant="tonal">{{ totalRuns }} runs</v-chip>
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-row class="mb-3">
        <v-col cols="12" md="6">
          <v-select
            v-model="scenario"
            :items="scenarioOptions"
            item-title="title"
            item-value="value"
            label="Scenario"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="6" class="d-flex align-center ga-2">
          <v-btn color="primary" :loading="runningScenario" @click="runScenario(scenario)">
            Run selected
          </v-btn>
          <v-btn variant="tonal" :loading="runningScenario" @click="runScenario('all')">
            Run all
          </v-btn>
          <v-btn variant="text" :loading="loadingRuns" @click="fetchRuns">
            Refresh
          </v-btn>
        </v-col>
      </v-row>

      <v-row v-if="showSimulationInputs" class="mb-3">
        <v-col cols="12">
          <v-alert type="info" variant="tonal" density="comfortable">
            Simulation inputs (used by wallet consumption simulation).
          </v-alert>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="simulationParams.deposit_ui"
            label="Deposit UI"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="simulationParams.bet_ui"
            label="Bet UI"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="simulationParams.win_ui"
            label="Win UI"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="simulationParams.real_balance_ui"
            label="Real Balance UI"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" md="5">
          <v-card variant="outlined">
            <v-card-title class="text-subtitle-2">Runs</v-card-title>
            <v-divider />
            <v-card-text>
              <v-skeleton-loader v-if="loadingRuns" type="table-row@4" />
              <v-list v-else class="pa-0">
                <v-list-item
                  v-for="item in runs"
                  :key="item.id"
                  :active="item.id === selectedRunId"
                  @click="onSelectRun(item)"
                >
                  <template #title>
                    <div class="d-flex justify-space-between align-center">
                      <span>{{ item.scenario }}</span>
                      <v-chip size="x-small" :color="runStatusColor(item.status)" variant="tonal">
                        {{ item.status }}
                      </v-chip>
                    </div>
                  </template>
                  <template #subtitle>
                    <span>{{ item.created_at }}</span>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="7">
          <v-card variant="outlined">
            <v-card-title class="text-subtitle-2 d-flex justify-space-between align-center">
              <span>Console</span>
              <v-chip
                v-if="selectedRun"
                size="small"
                :color="runStatusColor(selectedRun.status)"
                variant="tonal"
              >
                {{ selectedRun.status }}
              </v-chip>
            </v-card-title>
            <v-divider />
            <v-card-text class="console-wrapper">
              <v-skeleton-loader v-if="loadingLogs" type="list-item@5" />
              <div v-else-if="!selectedRunId" class="text-medium-emphasis">
                Select a run to view logs.
              </div>
              <div v-else-if="logs.length === 0" class="text-medium-emphasis">
                No logs yet.
              </div>
              <div v-else class="console-lines">
                <div v-for="log in logs" :key="log.id" class="console-line">
                  <v-chip size="x-small" :color="logLevelColor(log.level)" variant="tonal">
                    {{ log.level }}
                  </v-chip>
                  <code class="step-code">{{ log.step_code || "step" }}</code>
                  <span class="message">{{ log.message }}</span>
                  <code v-if="formatContext(log.context_json)" class="context">
                    {{ formatContext(log.context_json) }}
                  </code>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.console-wrapper {
  max-height: 460px;
  overflow: auto;
  background: #0f172a;
  color: #e2e8f0;
}

.console-lines {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.console-line {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.step-code {
  color: #93c5fd;
}

.message {
  white-space: pre-wrap;
}

.context {
  display: block;
  width: 100%;
  color: #a7f3d0;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
</style>
