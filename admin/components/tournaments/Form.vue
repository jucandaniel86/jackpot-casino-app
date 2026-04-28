<script setup lang="ts">
import type {
  Tournament,
  TournamentPayload,
  TournamentPrizePayload,
} from "~/types/tournaments";

type Props = {
  loading: boolean;
  item?: Tournament | null;
};

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: "save", payload: TournamentPayload): void;
}>();

const { toastError } = useAlert();

const formRef = ref<any>(null);
const thumbnailFile = ref<File | File[] | null>(null);
const thumbnailPreview = ref<string | null>(null);

const statusItems = [
  { title: "Draft", value: "draft" },
  { title: "Scheduled", value: "scheduled" },
  { title: "Active", value: "active" },
  { title: "Finished", value: "finished" },
  { title: "Cancelled", value: "cancelled" },
] as const;

type PrizeForm = TournamentPrizePayload & { metadata_text?: string };

const model = reactive<{
  name: string;
  thumbnail: string | null;
  started_at: string;
  ended_at: string;
  status: any;
  point_rate: number | null;
  game_ids: string[];
  prizes: PrizeForm[];
}>({
  name: "",
  thumbnail: null,
  started_at: "",
  ended_at: "",
  status: "draft",
  point_rate: 1,
  game_ids: [],
  prizes: [],
});

const required = (v: any) => !!v || "Required.";
const minPointRate = (v: any) =>
  v !== null && v !== undefined && Number(v) >= 1 ? true : "Min 1.";
const nonNegative = (v: any) =>
  v === null || v === undefined || v === "" || Number(v) >= 0
    ? true
    : "Min 0.";

const endedAfterStarted = () => {
  if (!model.started_at || !model.ended_at) return true;
  const s = new Date(model.started_at);
  const e = new Date(model.ended_at);
  if (Number.isNaN(s.getTime()) || Number.isNaN(e.getTime())) return true;
  return e.getTime() > s.getTime() || "End must be after start.";
};

const normalizeDatetimeInput = (value: string) => {
  // v-text-field datetime-local outputs "YYYY-MM-DDTHH:mm". Backend accepts date strings.
  return value ? String(value) : "";
};

const mapFromItem = (item: Tournament) => {
  model.name = item.name ?? "";
  model.thumbnail = item.thumbnail ?? null;
  thumbnailPreview.value = item.thumbnail_url ?? item.thumbnail ?? null;
  model.started_at = item.started_at ?? "";
  model.ended_at = item.ended_at ?? "";
  model.status = item.status ?? "draft";
  model.point_rate = Number(item.point_rate ?? 1);
  model.game_ids = Array.isArray(item.games)
    ? item.games.map((g: any) => String(g.game_id ?? g.pivot?.game_id ?? ""))
        .filter(Boolean)
    : [];

  model.prizes = Array.isArray(item.prizes)
    ? item.prizes.map((p: any) => ({
        prize_name: String(p.prize_name ?? ""),
        prize_type: p.prize_type === "threshold" ? "threshold" : "rank",
        rank_from:
          p.rank_from === undefined || p.rank_from === null
            ? null
            : Number(p.rank_from),
        rank_to:
          p.rank_to === undefined || p.rank_to === null ? null : Number(p.rank_to),
        min_points:
          p.min_points === undefined || p.min_points === null
            ? null
            : Number(p.min_points),
        prize_currency:
          p.prize_currency === undefined || p.prize_currency === null
            ? null
            : String(p.prize_currency),
        prize_amount: p.prize_amount ?? 0,
        metadata:
          p.metadata && typeof p.metadata === "object" ? (p.metadata as any) : null,
        metadata_text:
          p.metadata && typeof p.metadata === "object"
            ? JSON.stringify(p.metadata, null, 2)
            : "",
      }))
    : [];
};

watch(
  () => props.item,
  (item) => {
    if (item) mapFromItem(item);
    else thumbnailPreview.value = null;
    thumbnailFile.value = null;
  },
  { immediate: true },
);

watch(thumbnailFile, (fileValue, oldValue) => {
  if (typeof thumbnailPreview.value === "string" && thumbnailPreview.value.startsWith("blob:")) {
    URL.revokeObjectURL(thumbnailPreview.value);
  }

  const file = Array.isArray(fileValue) ? fileValue[0] : fileValue;
  thumbnailPreview.value = file instanceof File ? URL.createObjectURL(file) : props.item?.thumbnail_url ?? props.item?.thumbnail ?? null;
});

function addPrize() {
  model.prizes.push({
    prize_name: "",
    prize_type: "rank",
    rank_from: null,
    rank_to: null,
    min_points: null,
    prize_currency: "GC",
    prize_amount: 0,
    metadata: null,
    metadata_text: "",
  });
}

function removePrize(index: number) {
  model.prizes.splice(index, 1);
}

const uuidLike = (value: string) =>
  /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(
    value,
  );

function validatePrizes(): string[] {
  const errors: string[] = [];

  model.prizes.forEach((p, index) => {
    if (!p.prize_name?.trim()) {
      errors.push(`Prize #${index + 1}: prize_name is required.`);
    }
    if (p.prize_type !== "rank" && p.prize_type !== "threshold") {
      errors.push(`Prize #${index + 1}: prize_type is required.`);
    }
    if (p.prize_type === "rank") {
      if (p.rank_from === null || p.rank_from === undefined || p.rank_from === ("" as any)) {
        errors.push(`Prize #${index + 1}: rank_from is required for rank type.`);
      }
    }
    if (p.prize_type === "threshold") {
      if (p.min_points === null || p.min_points === undefined || p.min_points === ("" as any)) {
        errors.push(
          `Prize #${index + 1}: min_points is required for threshold type.`,
        );
      }
    }

    if (p.prize_amount === null || p.prize_amount === undefined || p.prize_amount === ("" as any)) {
      errors.push(`Prize #${index + 1}: prize_amount is required.`);
    } else if (Number(p.prize_amount) < 0) {
      errors.push(`Prize #${index + 1}: prize_amount must be >= 0.`);
    }
  });

  return errors;
}

function buildPayload(): TournamentPayload | null {
  const prizeErrors = validatePrizes();
  if (prizeErrors.length) {
    toastError(prizeErrors.join("\n"));
    return null;
  }

  if (!Array.isArray(model.game_ids) || model.game_ids.length < 1) {
    toastError("Please add at least one game ID.");
    return null;
  }

 
  const prizes: TournamentPrizePayload[] = model.prizes.map((p) => {
    let meta: any = null;
    const raw = (p as any).metadata_text;
    if (raw && typeof raw === "string" && raw.trim()) {
      try {
        meta = JSON.parse(raw);
      } catch {
        toastError("Invalid JSON in prize metadata.");
        meta = "__invalid__";
      }
    } else if (p.metadata && typeof p.metadata === "object") {
      meta = p.metadata;
    }

    if (meta === "__invalid__") {
      throw new Error("Invalid prize metadata JSON");
    }

    return {
      prize_name: String(p.prize_name ?? ""),
      prize_type: p.prize_type === "threshold" ? "threshold" : "rank",
      rank_from: p.prize_type === "rank" ? (p.rank_from ?? null) : null,
      rank_to: p.prize_type === "rank" ? (p.rank_to ?? null) : null,
      min_points: p.prize_type === "threshold" ? (p.min_points ?? null) : null,
      prize_currency: p.prize_currency ?? "GC",
      prize_amount: p.prize_amount ?? 0,
      metadata: meta,
    };
  });

  return {
    name: String(model.name ?? ""),
    thumbnail: model.thumbnail ? String(model.thumbnail) : null,
    thumbnail_file: thumbnailFile.value,
    started_at: normalizeDatetimeInput(model.started_at),
    ended_at: normalizeDatetimeInput(model.ended_at),
    status: model.status,
    point_rate: Number(model.point_rate ?? 1),
    game_ids: model.game_ids.map((id) => String(id)),
    prizes: prizes.length ? prizes : [],
  };
}

async function onSave() {
  const form = formRef.value;
  const res = await form?.validate?.();
  if (res && res.valid === false) return;

  let payload: TournamentPayload | null = null;
  try {
    payload = buildPayload();
  } catch {
    return;
  }
  if (!payload) return;

  emit("save", payload);
}
</script>

<template>
  <v-form ref="formRef" validate-on="submit">
    <v-card variant="tonal" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        Tournament details
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="model.name"
              label="Name"
              variant="outlined"
              density="comfortable"
              :rules="[required]"
            />
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-2">
              <v-img
                v-if="thumbnailPreview"
                :src="thumbnailPreview"
                max-width="180"
                aspect-ratio="16/9"
                cover
                class="rounded"
              />
            </div>

            <v-file-input
              v-model="thumbnailFile"
              label="Thumbnail image"
              accept="image/*"
              variant="outlined"
              density="comfortable"
              clearable
              show-size
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="model.started_at"
              type="datetime-local"
              label="Starts at"
              variant="outlined"
              density="comfortable"
              :rules="[required]"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="model.ended_at"
              type="datetime-local"
              label="Ends at"
              variant="outlined"
              density="comfortable"
              :rules="[required, endedAfterStarted]"
            />
          </v-col>

          <v-col cols="12" md="2">
            <v-select
              v-model="model.status"
              label="Status"
              :items="statusItems"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="comfortable"
              :rules="[required]"
            />
          </v-col>

          <v-col cols="12" md="2">
            <v-text-field
              v-model="model.point_rate"
              type="number"
              label="Point rate"
              variant="outlined"
              density="comfortable"
              :rules="[required, minPointRate]"
              min="1"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="tonal" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        Games
      </v-card-title>
      <v-divider />
      <v-card-text>
        <SelectGames
          v-model="model.game_ids"
          :initial-items="props.item?.games ?? []"
          :rules="[
            (v: any) =>
              Array.isArray(v) && v.length > 0
                ? true
                : 'At least one game is required.',
          ]"
        />
      </v-card-text>
    </v-card>

    <v-card variant="tonal" class="mb-4">
      <v-card-title class="d-flex align-center justify-space-between">
        <span class="text-subtitle-1 font-weight-bold">Prizes</span>
        <v-btn variant="flat" color="primary" prepend-icon="mdi-plus" @click="addPrize">
          Add prize
        </v-btn>
      </v-card-title>
      <v-divider />

      <v-card-text v-if="model.prizes.length === 0" class="text-medium-emphasis">
        No prizes. You can add prizes for rank or threshold.
      </v-card-text>

      <v-card-text v-else class="d-flex flex-column ga-4">
        <v-card
          v-for="(prize, index) in model.prizes"
          :key="`prize-${index}`"
          variant="outlined"
        >
          <v-card-title class="d-flex justify-space-between align-center">
            <div>Prize #{{ index + 1 }}</div>
            <v-btn
              variant="text"
              color="error"
              prepend-icon="mdi-delete"
              @click="removePrize(index)"
            >
              Remove
            </v-btn>
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-row dense>
              <v-col cols="12" md="5">
                <v-text-field
                  v-model="prize.prize_name"
                  label="Prize name"
                  variant="outlined"
                  density="comfortable"
                  :rules="[required]"
                />
              </v-col>

              <v-col cols="12" md="3">
                <v-select
                  v-model="prize.prize_type"
                  label="Prize type"
                  :items="[
                    { title: 'Rank', value: 'rank' },
                    { title: 'Threshold', value: 'threshold' },
                  ]"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="comfortable"
                  :rules="[required]"
                />
              </v-col>

              <v-col cols="12" md="2" v-if="prize.prize_type === 'rank'">
                <v-text-field
                  v-model="prize.rank_from"
                  type="number"
                  label="Rank from"
                  variant="outlined"
                  density="comfortable"
                  :rules="[required]"
                  min="1"
                />
              </v-col>

              <v-col cols="12" md="2" v-if="prize.prize_type === 'rank'">
                <v-text-field
                  v-model="prize.rank_to"
                  type="number"
                  label="Rank to"
                  variant="outlined"
                  density="comfortable"
                  :rules="[(v: any) => (v === null || v === '' || Number(v) >= 1) ? true : 'Min 1.']"
                  min="1"
                />
              </v-col>

              <v-col cols="12" md="4" v-if="prize.prize_type === 'threshold'">
                <v-text-field
                  v-model="prize.min_points"
                  type="number"
                  label="Min points"
                  variant="outlined"
                  density="comfortable"
                  :rules="[required, nonNegative]"
                  min="0"
                />
              </v-col>

              <v-col cols="12" md="2">
                <v-text-field
                  v-model="prize.prize_currency"
                  label="Currency"
                  variant="outlined"
                  density="comfortable"
                  placeholder="GC"
                  clearable
                />
              </v-col>

              <v-col cols="12" md="3">
                <v-text-field
                  v-model="prize.prize_amount"
                  type="number"
                  label="Amount"
                  variant="outlined"
                  density="comfortable"
                  :rules="[required, nonNegative]"
                  min="0"
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="prize.metadata_text"
                  label="Metadata (JSON, optional)"
                  variant="outlined"
                  density="comfortable"
                  rows="3"
                  auto-grow
                  placeholder='{"key":"value"}'
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-card-text>
    </v-card>

    <div class="d-flex justify-end ga-2">
      <v-btn
        :disabled="props.loading"
        color="primary"
        variant="flat"
        @click.prevent="onSave"
      >
        Save
      </v-btn>
    </div>
  </v-form>
</template>
