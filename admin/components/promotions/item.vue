<script setup lang="ts">
import type { PromotionT } from "~/core/types/Promotions";

type PromotionItemT = {
  item: PromotionT;
};
//props
const { item } = defineProps<PromotionItemT>();

//emitters
const emitters = defineEmits(["onEdit", "onDelete"]);

//methods
const handleEdit = (id: number) => emitters("onEdit", id);
const handleDelete = (id: number) => emitters("onDelete", id);
</script>
<template>
  <v-card class="pb-3" border flat>
    <v-img :src="item.thumbnailUrl"></v-img>

    <v-list-item :subtitle="item.subtitle" class="mb-2 text-overflow">
      <template v-slot:title> 
				<div class="d-flex justify-space-between">
					  <strong class="text-h6 mb-2">{{ item.title }}</strong>  
					
				</div>
      
      </template>
    </v-list-item>

    <v-card-actions>
			<div class="d-flex ga-2 justify-start flex-column">
				<span>
					<v-chip size="x-small" :color="item.status === 'PUBLISHED' ? 'green' : 'red'"><b>{{ item.status }}</b></v-chip>
				</span>
				
				<span>
					Active: 
					<v-chip size="x-small" :color="parseInt(item.active as string) ? 'green' : 'red'"><b>{{ parseInt(item.active as string) ? 'YES' : 'NO' }}</b></v-chip>
				</span>
			</div>
      <div class="d-flex ga-1 justify-end px-4 w-100">
        <v-btn
          class="text-none"
          size="small"
          text="Edit"
          variant="flat"
          prepend-icon="mdi-pencil"
          border
          @click.prevent="handleEdit(item.id)"
        >
        </v-btn>
        <v-btn
          class="text-none"
          size="small"
          text="Delete"
          variant="flat"
          prepend-icon="mdi-delete-outline"
          border
          @click.prevent="handleDelete(item.id)"
        >
        </v-btn>
      </div>
    </v-card-actions>
  </v-card>
</template>
