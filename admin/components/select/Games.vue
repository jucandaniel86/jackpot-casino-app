<script setup lang="ts">
import type { GameT } from "../games/config";

type SelectGamesT = {
  initialItems?: Partial<GameT>[];
  rules?: any[];
};

const props = withDefaults(defineProps<SelectGamesT>(), {
  initialItems: () => [],
  rules: () => [],
});

const model = defineModel<string[]>({ default: [] });
const items = ref<Partial<GameT>[]>([]);
const loading = ref<boolean>(false);
const search = ref("");
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const normalizeGame = (game: any): Partial<GameT> => ({
  ...game,
  game_id: String(game.game_id ?? game.pivot?.game_id ?? game.id ?? ""),
});

const mergeItems = (games: any[]) => {
  const byGameId = new Map<string, Partial<GameT>>();

  [...items.value, ...games.map(normalizeGame)].forEach((game) => {
    const gameId = String(game.game_id ?? "");
    if (gameId) {
      byGameId.set(gameId, game);
    }
  });

  items.value = Array.from(byGameId.values());
};

const getGames = async (name = ""): Promise<void> => {
  if (name.trim().length < 2) {
    return;
  }

  loading.value = true;
  try {
    const { data } = await useAPIFetch("/games/search", { name });

    if (data?.data) {
      mergeItems(data.data);
    }
  } finally {
    loading.value = false;
  }
};

const itemTitle = (game: Partial<GameT>) => {
  const name = game.name ? String(game.name) : "Unnamed game";
  const gameId = game.game_id ? String(game.game_id) : "";

  return gameId ? `${name} (${gameId})` : name;
};

watch(
  () => props.initialItems,
  (initialItems) => {
    if (Array.isArray(initialItems)) {
      mergeItems(initialItems);
    }
  },
  { immediate: true },
);

watch(search, (value) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  searchTimeout = setTimeout(() => {
    getGames(value ?? "");
  }, 300);
});

</script>

<template>
  <v-autocomplete
    v-model="model"
    v-model:search="search"
    :loading="loading"
    :items="items"
    :item-title="itemTitle"
    item-value="game_id"
    label="Games"
    variant="outlined"
    density="comfortable"
    multiple
    chips
    closable-chips
    clearable
    auto-select-first
    no-filter
    :rules="props.rules"
  />
</template>
