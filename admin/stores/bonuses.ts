import { defineStore } from "pinia";
import type {
  BonusGrant,
  BonusGrantEvent,
  BonusGrantsFilters,
  BonusManualGrantPayload,
  BonusManualGrantResponse,
  BonusManualPreviewPayload,
  BonusManualPreviewResponse,
  BonusRule,
  BonusRulePayload,
  BonusStatsResponse,
} from "~/core/types/bonuses";

type LoadingMap = {
  rules: boolean;
  ruleSave: boolean;
  grants: boolean;
  events: boolean;
  stats: boolean;
  manualPreview: boolean;
  manualGrant: boolean;
};

interface BonusesState {
  rules: BonusRule[];
  grants: BonusGrant[];
  grantsTotal: number;
  grantEvents: BonusGrantEvent[];
  stats: BonusStatsResponse | null;
  preview: BonusManualPreviewResponse | null;
  lastGrantResult: BonusManualGrantResponse | null;
  loading: LoadingMap;
}

const defaultLoading = (): LoadingMap => ({
  rules: false,
  ruleSave: false,
  grants: false,
  events: false,
  stats: false,
  manualPreview: false,
  manualGrant: false,
});

export const useBonusesStore = defineStore("bonuses-store", {
  state: (): BonusesState => ({
    rules: [],
    grants: [],
    grantsTotal: 0,
    grantEvents: [],
    stats: null,
    preview: null,
    lastGrantResult: null,
    loading: defaultLoading(),
  }),
  getters: {
    activeRules(state) {
      return state.rules.filter((rule) => Boolean(rule.is_active));
    },
  },
  actions: {
    async fetchRules() {
      this.loading.rules = true;
      const api = useBonusesApi();

      const result = await api.getRules();

      if (result.success && result.data) {
        this.rules = result.data;
      }

      this.loading.rules = false;
      return result;
    },

    async saveRule(payload: BonusRulePayload) {
      this.loading.ruleSave = true;
      const api = useBonusesApi();
      const result = await api.saveRule(payload);

      if (result.success && result.data) {
        const existingIndex = this.rules.findIndex(
          (rule) => rule.id === result.data?.id,
        );

        if (existingIndex >= 0) {
          this.rules[existingIndex] = result.data;
        } else {
          this.rules.unshift(result.data);
        }
      }

      this.loading.ruleSave = false;
      return result;
    },

    async deleteRule(id: number) {
      const api = useBonusesApi();
      const result = await api.deleteRule(id);

      if (result.success) {
        this.rules = this.rules.filter((rule) => rule.id !== id);
      }

      return result;
    },

    async toggleRule(id: number, isActive: boolean) {
      const api = useBonusesApi();
      const result = await api.toggleRule(id, isActive);

      if (result.success && result.data) {
        const index = this.rules.findIndex((rule) => rule.id === id);
        if (index >= 0) {
          this.rules[index] = {
            ...this.rules[index],
            ...result.data,
          };
        }
      }

      return result;
    },

    async fetchGrants(filters: BonusGrantsFilters) {
      this.loading.grants = true;
      const api = useBonusesApi();
      const result = await api.getGrants(filters);

      if (result.success && result.data) {
        this.grants = result.data.items;
        this.grantsTotal = result.data.total;
      }

      this.loading.grants = false;
      return result;
    },

    async fetchGrantEvents(grantId: number) {
      this.loading.events = true;
      const api = useBonusesApi();
      const result = await api.getGrantEvents(grantId);

      if (result.success && result.data) {
        this.grantEvents = result.data;
      }

      this.loading.events = false;
      return result;
    },

    async fetchStats() {
      this.loading.stats = true;
      const api = useBonusesApi();
      const result = await api.getStats();

      if (result.success && result.data) {
        this.stats = result.data;
      }

      this.loading.stats = false;
      return result;
    },

    async previewManual(payload: BonusManualPreviewPayload) {
      this.loading.manualPreview = true;
      const api = useBonusesApi();
      const result = await api.manualPreview(payload);

      if (result.success && result.data) {
        this.preview = result.data;
      }

      this.loading.manualPreview = false;
      return result;
    },

    async grantManual(payload: BonusManualGrantPayload) {
      this.loading.manualGrant = true;
      const api = useBonusesApi();
      const result = await api.manualGrant(payload);

      if (result.success && result.data) {
        this.lastGrantResult = result.data;
      }

      this.loading.manualGrant = false;
      return result;
    },

    clearPreview() {
      this.preview = null;
    },

    resetState() {
      this.rules = [];
      this.grants = [];
      this.grantsTotal = 0;
      this.grantEvents = [];
      this.stats = null;
      this.preview = null;
      this.lastGrantResult = null;
      this.loading = defaultLoading();
    },
  },
});
