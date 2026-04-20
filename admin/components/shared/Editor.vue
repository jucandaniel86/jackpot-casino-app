<template>
  <div>
    <QuillEditor
      :modules="modules"
      :options="{ scrollingContainer: true }"
      toolbar="full"
      @update:content="onModelChange"
      content-type="delta"
      ref="Q"
    />
  </div>
</template>

<script setup lang="ts">
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import "@vueup/vue-quill/dist/vue-quill.bubble.css";
import { QuillEditor } from "@vueup/vue-quill";

type EditorT = {
  content: String | null;
};

const modules = {
  table: true,
  tableUI: true,
};
const Q = ref();
const emitters = defineEmits(["update:model-value"]);
const props = defineProps<EditorT>();

const onModelChange = (content: any) => {
  emitters("update:model-value", Q.value.getHTML());
};

onMounted(() => {
  if (props.content) {
    Q.value.setHTML(props.content);
  }
});
</script>
<style>
.ql-editor {
  height: 350px;
}
</style>
