import { describe, expect, it, vi } from "vitest";
import { defineComponent, h, ref } from "vue";
import { mount } from "@vue/test-utils";
import RulesHelpModal from "~/components/bonus/RulesHelpModal.vue";

vi.mock("vuetify", () => ({
  useDisplay: () => ({ smAndDown: ref(false) }),
}));

vi.mock("#imports", () => ({
  useAlert: () => ({
    toastSuccess: vi.fn(),
    toastError: vi.fn(),
  }),
}));

const VDialogStub = defineComponent({
  props: { modelValue: Boolean },
  emits: ["update:modelValue", "keydown.esc"],
  setup(_props, { slots }) {
    return () => h("div", { class: "v-dialog-stub" }, slots.default?.());
  },
});

const VTextFieldStub = defineComponent({
  props: { modelValue: String },
  emits: ["update:modelValue"],
  setup(props, { emit }) {
    return () =>
      h("input", {
        value: props.modelValue,
        onInput: (e: Event) =>
          emit("update:modelValue", (e.target as HTMLInputElement).value),
      });
  },
});

describe("RulesHelpModal", () => {
  it("renders when opened", () => {
    const wrapper = mount(RulesHelpModal, {
      props: { modelValue: true },
      global: {
        stubs: {
          VDialog: VDialogStub,
          VTextField: VTextFieldStub,
          VCard: true,
          VCardTitle: true,
          VCardText: true,
          VDivider: true,
          VRow: true,
          VCol: true,
          VBtn: true,
          VTabs: true,
          VTab: true,
          VAlert: true,
          VList: true,
          VListItem: true,
          VIcon: true,
          VChip: true,
          VTable: true,
          VExpansionPanels: true,
          VExpansionPanel: true,
          VExpansionPanelText: true,
        },
      },
    });

    expect(wrapper.text()).toContain("Bonus Rules Help");
  });

  it("filters results by search term", async () => {
    const wrapper = mount(RulesHelpModal, {
      props: { modelValue: true },
      global: {
        stubs: {
          VDialog: VDialogStub,
          VTextField: VTextFieldStub,
          VCard: true,
          VCardTitle: true,
          VCardText: true,
          VDivider: true,
          VRow: true,
          VCol: true,
          VBtn: true,
          VTabs: true,
          VTab: true,
          VAlert: true,
          VList: true,
          VListItem: true,
          VIcon: true,
          VChip: true,
          VTable: true,
          VExpansionPanels: true,
          VExpansionPanel: true,
          VExpansionPanelText: true,
        },
      },
    });

    await wrapper.find("input").setValue("min_deposit_ui");
    expect(wrapper.text().toLowerCase()).toContain("first deposit match");
  });

  it("emits close on ESC key", async () => {
    const wrapper = mount(RulesHelpModal, {
      props: { modelValue: true },
      global: {
        stubs: {
          VDialog: VDialogStub,
          VTextField: VTextFieldStub,
          VCard: true,
          VCardTitle: true,
          VCardText: true,
          VDivider: true,
          VRow: true,
          VCol: true,
          VBtn: true,
          VTabs: true,
          VTab: true,
          VAlert: true,
          VList: true,
          VListItem: true,
          VIcon: true,
          VChip: true,
          VTable: true,
          VExpansionPanels: true,
          VExpansionPanel: true,
          VExpansionPanelText: true,
        },
      },
    });

    await wrapper.trigger("keydown.esc");

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
  });

  it("renders condition_json advanced section and copy action", () => {
    const wrapper = mount(RulesHelpModal, {
      props: { modelValue: true },
      global: {
        stubs: {
          VDialog: VDialogStub,
          VTextField: VTextFieldStub,
          VCard: true,
          VCardTitle: true,
          VCardText: true,
          VDivider: true,
          VRow: true,
          VCol: true,
          VBtn: true,
          VTabs: true,
          VTab: true,
          VAlert: true,
          VList: true,
          VListItem: true,
          VIcon: true,
          VChip: true,
          VTable: true,
          VExpansionPanels: true,
          VExpansionPanel: true,
          VExpansionPanelText: true,
        },
      },
    });

    expect(wrapper.text()).toContain("Condition JSON (Advanced)");
    expect(wrapper.text()).toContain("Copy condition JSON");
  });
});
