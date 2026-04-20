/**
 * Changelog:
 * - Added dedicated "Condition JSON (Advanced)" section with optional badge.
 * - Added condition_json examples + per-example copy action.
 * - Added inline validation notes and first_deposit common mistakes.
 * - Added ready-to-use templates with copy support.
 */
<script setup lang="ts">
import { useDisplay } from "vuetify";
import {
  bonusRuleCommonErrors,
  bonusRuleGlobalBestPractices,
  bonusRuleHelpItems,
  bonusRuleReadyTemplates,
  type BonusRuleConditionExample,
  type BonusRiskLevel,
  type BonusRuleHelpItem,
} from "~/core/types/bonusRuleHelp";

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  "open-in-form": [ruleType: string];
}>();

const { smAndDown } = useDisplay();
const { toastSuccess, toastError } = useAlert();

const search = ref("");
const category = ref<"all" | "acquisition" | "retention" | "vip" | "special">("all");
const selectedId = ref<string>(bonusRuleHelpItems[0]?.id || "");

const categories = [
  { value: "all", label: "All" },
  { value: "acquisition", label: "Acquisition" },
  { value: "retention", label: "Retention" },
  { value: "vip", label: "VIP" },
  { value: "special", label: "Special" },
] as const;

const riskMeta: Record<BonusRiskLevel, { label: string; color: string }> = {
  low: { label: "Low risk", color: "success" },
  medium: { label: "Medium risk", color: "warning" },
  high: { label: "High risk", color: "error" },
};

const conditionValidationNotes: string[] = [
  'Use string values for decimal numbers (e.g. "100", "250.50").',
  "Avoid raw JS float values inside condition_json payloads.",
];

const filteredItems = computed<BonusRuleHelpItem[]>(() => {
  const term = search.value.trim().toLowerCase();

  return bonusRuleHelpItems.filter((item) => {
    const inCategory = category.value === "all" || item.category === category.value;
    if (!inCategory) return false;
    if (!term) return true;

    const haystack = [
      item.title,
      item.shortDescription,
      ...item.tags,
      ...item.useCases,
      ...item.configFields.map(
        (field) => `${field.name} ${field.description} ${field.example}`,
      ),
      ...(item.conditionFields || []).map(
        (field) => `${field.name} ${field.type} ${field.description}`,
      ),
      ...(item.conditionJsonExamples || []).map(
        (example) => `${example.label} ${example.json}`,
      ),
      ...(item.commonMistakes || []),
    ]
      .join(" ")
      .toLowerCase();

    return haystack.includes(term);
  });
});

const selectedItem = computed<BonusRuleHelpItem | null>(() => {
  return (
    filteredItems.value.find((item) => item.id === selectedId.value) ||
    filteredItems.value[0] ||
    null
  );
});

const close = () => emit("update:modelValue", false);

const copyText = async (value: string, label: string) => {
  try {
    await navigator.clipboard.writeText(value);
    toastSuccess(`${label} copied.`);
  } catch (_error) {
    toastError(`Failed to copy ${label.toLowerCase()}.`);
  }
};

const copyExample = async (item: BonusRuleHelpItem) =>
  copyText(JSON.stringify(item.exampleRule, null, 2), "JSON");

const copyConditionExample = async (example: BonusRuleConditionExample) =>
  copyText(example.json, "Condition JSON");

const copyTemplate = async (payload: Record<string, unknown>) =>
  copyText(JSON.stringify(payload, null, 2), "Template");

const openInForm = (item: BonusRuleHelpItem) => {
  emit("open-in-form", item.id);
};

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return;
    if (!filteredItems.value.length) return;
    if (!filteredItems.value.some((item) => item.id === selectedId.value)) {
      selectedId.value = filteredItems.value[0].id;
    }
  },
);

watch(filteredItems, (items) => {
  if (!items.length) return;
  if (!items.some((item) => item.id === selectedId.value)) {
    selectedId.value = items[0].id;
  }
});
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="1200"
    scrollable
    aria-label="Bonus rules help modal"
    @update:model-value="emit('update:modelValue', $event)"
    @keydown.esc="close"
  >
    <v-card class="help-modal">
      <v-card-title class="d-flex align-center justify-space-between ga-3">
        <div>
          <div class="text-h6">Bonus Rules Help</div>
          <div class="text-caption text-medium-emphasis">
            Quick guide for configuring bonus rule types.
          </div>
        </div>
        <v-btn icon="ph-x" variant="text" aria-label="Close help" @click="close" />
      </v-card-title>

      <v-divider />

      <v-card-text>
        <v-row class="mb-3">
          <v-col cols="12" md="7">
            <v-text-field
              v-model="search"
              prepend-inner-icon="ph-magnifying-glass"
              label="Search bonus type or option"
              variant="outlined"
              density="comfortable"
              aria-label="Search bonus rule help"
            />
          </v-col>
          <v-col cols="12" md="5">
            <v-tabs v-model="category" color="primary" align-tabs="end">
              <v-tab v-for="cat in categories" :key="cat.value" :value="cat.value">
                {{ cat.label }}
              </v-tab>
            </v-tabs>
          </v-col>
        </v-row>

        <v-alert v-if="!filteredItems.length" type="info" variant="tonal">
          No results found for the current search.
        </v-alert>

        <template v-else-if="!smAndDown">
          <v-row>
            <v-col cols="12" md="4">
              <v-list nav class="rule-list" aria-label="Bonus rule types">
                <v-list-item
                  v-for="item in filteredItems"
                  :key="item.id"
                  :active="item.id === selectedId"
                  :title="item.title"
                  :subtitle="item.shortDescription"
                  @click="selectedId = item.id"
                >
                  <template #prepend>
                    <v-icon :icon="item.icon" />
                  </template>
                  <template #append>
                    <v-chip
                      size="x-small"
                      :color="riskMeta[item.riskLevel].color"
                      variant="tonal"
                    >
                      {{ riskMeta[item.riskLevel].label }}
                    </v-chip>
                  </template>
                </v-list-item>
              </v-list>
            </v-col>

            <v-col cols="12" md="8">
              <v-card v-if="selectedItem" variant="outlined" class="detail-card">
                <v-card-title class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center ga-2">
                    <v-icon :icon="selectedItem.icon" />
                    <span>{{ selectedItem.title }}</span>
                  </div>
                  <v-chip :color="riskMeta[selectedItem.riskLevel].color" variant="tonal" size="small">
                    {{ riskMeta[selectedItem.riskLevel].label }}
                  </v-chip>
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <p class="mb-3">{{ selectedItem.shortDescription }}</p>

                  <h4 class="section-title">When to use</h4>
                  <ul class="section-list">
                    <li v-for="useCase in selectedItem.useCases" :key="useCase">{{ useCase }}</li>
                  </ul>

                  <h4 class="section-title">Recommended fields</h4>
                  <v-table density="compact">
                    <thead>
                      <tr>
                        <th>Field</th>
                        <th>Description</th>
                        <th>Example</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="field in selectedItem.configFields" :key="field.name">
                        <td><code>{{ field.name }}</code></td>
                        <td>{{ field.description }}</td>
                        <td><code>{{ field.example }}</code></td>
                      </tr>
                    </tbody>
                  </v-table>

                  <h4 class="section-title">Configuration example</h4>
                  <div class="d-flex justify-end mb-2">
                    <v-btn size="small" variant="tonal" @click="copyExample(selectedItem)">Copy JSON</v-btn>
                    <v-btn size="small" color="primary" class="ml-2" variant="tonal" @click="openInForm(selectedItem)">
                      Open in form
                    </v-btn>
                  </div>
                  <pre class="json-box">{{ JSON.stringify(selectedItem.exampleRule, null, 2) }}</pre>

                  <div
                    v-if="
                      selectedItem.conditionFields?.length ||
                      selectedItem.conditionJsonExamples?.length
                    "
                  >
                    <h4 class="section-title d-flex align-center ga-2">
                      Condition JSON (Advanced)
                      <v-chip size="x-small" variant="tonal" color="info">Optional</v-chip>
                    </h4>
                    <p class="text-body-2 text-medium-emphasis mb-2">
                      Optional rule filters used for eligibility checks.
                    </p>

                    <ul class="section-list mb-2">
                      <li v-for="note in conditionValidationNotes" :key="note">{{ note }}</li>
                    </ul>

                    <v-table
                      v-if="selectedItem.conditionFields?.length"
                      density="compact"
                      class="mb-2"
                    >
                      <thead>
                        <tr>
                          <th>Field</th>
                          <th>Type</th>
                          <th>Required</th>
                          <th>Description</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="field in selectedItem.conditionFields"
                          :key="field.name"
                        >
                          <td><code>{{ field.name }}</code></td>
                          <td>{{ field.type }}</td>
                          <td>{{ field.required ? "Yes" : "No" }}</td>
                          <td>{{ field.description }}</td>
                        </tr>
                      </tbody>
                    </v-table>

                    <div
                      v-for="example in selectedItem.conditionJsonExamples || []"
                      :key="example.label"
                      class="mb-2"
                    >
                      <div class="d-flex justify-space-between align-center mb-1">
                        <strong>{{ example.label }}</strong>
                        <v-btn
                          size="x-small"
                          variant="tonal"
                          @click="copyConditionExample(example)"
                        >
                          Copy condition JSON
                        </v-btn>
                      </div>
                      <pre class="json-box">{{ example.json }}</pre>
                    </div>

                    <div v-if="selectedItem.commonMistakes?.length">
                      <h4 class="section-title">Common mistakes</h4>
                      <ul class="section-list">
                        <li
                          v-for="mistake in selectedItem.commonMistakes"
                          :key="mistake"
                        >
                          {{ mistake }}
                        </li>
                      </ul>
                    </div>
                  </div>

                  <h4 class="section-title">Warnings</h4>
                  <ul class="section-list">
                    <li v-for="risk in selectedItem.risks" :key="risk">{{ risk }}</li>
                  </ul>

                  <h4 class="section-title">Best practices for this rule type</h4>
                  <ul class="section-list">
                    <li v-for="practice in selectedItem.bestPractices" :key="practice">{{ practice }}</li>
                  </ul>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </template>

        <template v-else>
          <v-expansion-panels variant="accordion">
            <v-expansion-panel v-for="item in filteredItems" :key="item.id" :title="item.title">
              <v-expansion-panel-text>
                <div class="d-flex justify-space-between align-center mb-2">
                  <div class="text-caption text-medium-emphasis">{{ item.shortDescription }}</div>
                  <v-chip size="x-small" :color="riskMeta[item.riskLevel].color" variant="tonal">
                    {{ riskMeta[item.riskLevel].label }}
                  </v-chip>
                </div>

                <h4 class="section-title">When to use</h4>
                <ul class="section-list">
                  <li v-for="useCase in item.useCases" :key="useCase">{{ useCase }}</li>
                </ul>

                <h4 class="section-title">Recommended fields</h4>
                <ul class="section-list">
                  <li v-for="field in item.configFields" :key="field.name">
                    <code>{{ field.name }}</code> - {{ field.description }} ({{ field.example }})
                  </li>
                </ul>

                <div class="d-flex justify-end mb-2 ga-2">
                  <v-btn size="small" variant="tonal" @click="copyExample(item)">Copy JSON</v-btn>
                  <v-btn size="small" color="primary" variant="tonal" @click="openInForm(item)">
                    Open in form
                  </v-btn>
                </div>
                <pre class="json-box">{{ JSON.stringify(item.exampleRule, null, 2) }}</pre>

                <div
                  v-if="item.conditionFields?.length || item.conditionJsonExamples?.length"
                  class="mt-2"
                >
                  <h4 class="section-title d-flex align-center ga-2">
                    Condition JSON (Advanced)
                    <v-chip size="x-small" variant="tonal" color="info">Optional</v-chip>
                  </h4>
                  <p class="text-body-2 text-medium-emphasis mb-2">
                    Optional rule filters used for eligibility checks.
                  </p>
                  <ul class="section-list">
                    <li v-for="note in conditionValidationNotes" :key="note">{{ note }}</li>
                  </ul>
                  <div
                    v-for="example in item.conditionJsonExamples || []"
                    :key="example.label"
                    class="mb-2 mt-2"
                  >
                    <div class="d-flex justify-space-between align-center mb-1">
                      <strong>{{ example.label }}</strong>
                      <v-btn
                        size="x-small"
                        variant="tonal"
                        @click="copyConditionExample(example)"
                      >
                        Copy condition JSON
                      </v-btn>
                    </div>
                    <pre class="json-box">{{ example.json }}</pre>
                  </div>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </template>

        <v-row class="mt-4">
          <v-col cols="12" md="6">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-2">Best practices</v-card-title>
              <v-divider />
              <v-card-text>
                <ul class="section-list">
                  <li v-for="item in bonusRuleGlobalBestPractices" :key="item">{{ item }}</li>
                </ul>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="6">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-2">Common errors</v-card-title>
              <v-divider />
              <v-card-text>
                <ul class="section-list">
                  <li v-for="item in bonusRuleCommonErrors" :key="item">{{ item }}</li>
                </ul>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-row class="mt-2">
          <v-col cols="12">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-2">Ready-to-use templates</v-card-title>
              <v-divider />
              <v-card-text>
                <v-row>
                  <v-col
                    v-for="template in bonusRuleReadyTemplates"
                    :key="template.id"
                    cols="12"
                    md="4"
                  >
                    <v-card variant="tonal">
                      <v-card-title class="text-body-2">{{ template.title }}</v-card-title>
                      <v-card-text>
                        <pre class="json-box mb-2">{{ JSON.stringify(template.payload, null, 2) }}</pre>
                        <div class="d-flex ga-2">
                          <v-btn size="small" variant="outlined" @click="copyTemplate(template.payload)">
                            Copy template
                          </v-btn>
                          <v-btn
                            size="small"
                            color="primary"
                            variant="tonal"
                            @click="emit('open-in-form', template.id)"
                          >
                            Open in form
                          </v-btn>
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.help-modal {
  background: #ffffff;
}

.rule-list {
  max-height: 60vh;
  overflow: auto;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 8px;
}

.detail-card {
  max-height: 60vh;
  overflow: auto;
}

.section-title {
  margin-top: 14px;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
}

.section-list {
  margin: 0;
  padding-left: 18px;
}

.json-box {
  margin: 0;
  padding: 10px;
  border-radius: 8px;
  background: #f7f8fa;
  font-size: 12px;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
