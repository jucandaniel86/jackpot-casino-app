<script setup lang="ts">
import { CASINO_GAMES_HEADERS, type GameT } from "./config";

type GamesListT = {
  items: GameT[] | undefined;
  totalItems: number;
  loading: boolean;
};
const props = defineProps<GamesListT>();

//models
const headers = ref(CASINO_GAMES_HEADERS);

//emitters
const emitters = defineEmits(["onReload", "onEdit", "onDelete"]);

//methods
const getStatus = (_status: number) => (_status ? "YES" : "NO");

const handleEdit = (id: number) => emitters("onEdit", id);

const handleDelete = (id: number) => emitters("onDelete", id);

const handleReload = (options: any) => emitters("onReload", options);
</script>
<template>
  <v-data-table-server
    class="games-list-table"
    :headers="headers"
    :items="props.items"
    :items-length="totalItems"
    :loading="props.loading"
    :height="'100%'"
    fixed-header
    density="compact"
    @update:options="handleReload"
  >
    <template v-slot:item="{ item }">
      <tr :key="item.id">
        <td>
          <v-img
            v-if="item.thumbnail_url"
            :min-height="100"
            aspect-ratio="16/9"
            cover
            :src="item.thumbnail_url"
          >
            <template v-slot:placeholder>
              <div class="d-flex align-center justify-center fill-height">
                <v-progress-circular
                  color="grey-lighten-4"
                  indeterminate
                ></v-progress-circular>
              </div>
            </template>
          </v-img>
        </td>
        <td>{{ item.name }} ({{ item.game_id }})</td>
        <td>
          <v-chip
            size="x-small"
            v-for="(casino, i) in item.casinos"
            :key="`Casino${item.id}_${i}`"
            >{{ casino.name }}</v-chip
          >
        </td>
        <td>
          <v-chip
            size="x-small"
            v-for="(category, i) in item.categories"
            :key="`Category${item.id}_${i}`"
            >{{ category.name }}</v-chip
          >
        </td>
        <td>{{ item.provider ? item.provider.name : "N/A" }}</td>
        <td>
          <v-chip
            :color="`${item.active_on_site ? 'green' : 'red'}`"
            pill
            size="x-small"
            >{{ getStatus(item.active_on_site) }}</v-chip
          >
        </td>
        <td>
          <v-chip
            :color="`${item.soon ? 'green' : 'red'}`"
            pill
            size="x-small"
            >{{ getStatus(item.soon) }}</v-chip
          >
        </td>
        <td>
          <v-chip
            :color="`${item.is_recomended ? 'green' : 'red'}`"
            pill
            size="x-small"
            >{{ getStatus(item.is_recomended) }}</v-chip
          >
        </td>
        <td>
          <v-chip
            :color="`${item.is_fun ? 'green' : 'red'}`"
            pill
            size="x-small"
            >{{ getStatus(item.is_fun) }}</v-chip
          >
        </td>
        <td>
          <v-btn color="blue" size="small" @click.prevent="handleEdit(item.id)">
            <v-icon icon="mdi-file-edit-outline" variants="flat" />
            Edit
          </v-btn>
          <v-btn
            color="red"
            size="small"
            @click.prevent="handleDelete(item.id)"
          >
            <v-icon icon="mdi-delete-outline" variants="flat" />
            Delete
          </v-btn>
        </td>
      </tr>
    </template>
  </v-data-table-server>
</template>
