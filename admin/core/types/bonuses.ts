export type BonusRewardType = "fixed_amount" | "percentage";

export type BonusStackingPolicy = "stackable" | "exclusive";

export type BonusCampaignType = "register_bonus" | "deposit_bonus" | "custom";
export type BonusConsumePriority = "real_first" | "bonus_first";
export type BonusWinDestination = "bonus_wallet" | "real_wallet";

export type BonusTriggerType =
  | "register"
  | "deposit"
  | "manual"
  | "first_deposit"
  | "custom";

export type BonusRuleCondition = Record<string, unknown>;

export interface BonusRule {
  id: number;
  int_casino_id: string;
  name: string;
  trigger_type: BonusTriggerType | string;
  campaign_type?: BonusCampaignType | string | null;
  condition_json: BonusRuleCondition;
  reward_type: BonusRewardType;
  reward_value: number;
  currency_id: string | null;
  currency_code: string | null;
  max_reward_amount: number | null;
  deposit_bonus_multiplier?: number | null;
  wagering_multiplier: number;
  real_wager_multiplier?: number | null;
  bonus_wager_multiplier?: number | null;
  consume_priority?: BonusConsumePriority | string | null;
  win_destination?: BonusWinDestination | string | null;
  max_convert_to_real_ui?: number | null;
  expire_after_days?: number | null;
  valid_from: string | null;
  valid_until: string | null;
  priority: number;
  stacking_policy: BonusStackingPolicy;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface BonusRulePayload {
  id: number | null;
  int_casino_id?: string | null;
  name: string;
  trigger_type: BonusTriggerType | string;
  campaign_type?: BonusCampaignType | string | null;
  condition_json: BonusRuleCondition;
  reward_type: BonusRewardType;
  reward_value: number;
  currency_id: string | null;
  currency_code: string | null;
  max_reward_amount: number | null;
  deposit_bonus_multiplier?: number | null;
  wagering_multiplier: number;
  real_wager_multiplier?: number | null;
  bonus_wager_multiplier?: number | null;
  consume_priority?: BonusConsumePriority | string | null;
  win_destination?: BonusWinDestination | string | null;
  max_convert_to_real_ui?: number | null;
  expire_after_days?: number | null;
  valid_from: string | null;
  valid_until: string | null;
  priority: number;
  stacking_policy: BonusStackingPolicy;
  is_active: boolean;
}

export interface BonusRulesListResponse {
  data?: BonusRule[];
  items?: BonusRule[];
  rules?: BonusRule[];
}

export interface BonusRuleGetResponse {
  data?: BonusRule;
  item?: BonusRule;
  rule?: BonusRule;
}

export type BonusManualFilters = {
  player_ids?: number[];
  usernames?: string[];
  emails?: string[];
  active?: 0 | 1;
  registered_from?: string;
  registered_to?: string;
  min_total_deposit_ui?: number;
};

export interface BonusManualPreviewPayload {
  int_casino_id?: string | null;
  amount_ui: number;
  filters: BonusManualFilters;
}

export interface BonusManualPreviewResponse {
  estimated_players?: number;
  estimated_amount_ui?: number;
  currency_code?: string;
  [key: string]: unknown;
}

export type BonusSkipReasonCode =
  | "missing_bonus_wallet_type_or_wallet"
  | "zero_or_invalid_reward_amount"
  | "zero_or_invalid_amount_ui"
  | "unknown_skip_reason";

export interface BonusSkipReasonItem {
  player_id: number | null;
  username: string | null;
  reason: string;
}

export interface BonusManualGrantRulePayload {
  int_casino_id?: string | null;
  name: string;
  mode: "rule";
  rule_id: number;
  filters: BonusManualFilters;
}

export interface BonusManualGrantAmountPayload {
  int_casino_id?: string | null;
  name: string;
  mode: "amount";
  currency_id: string;
  amount_ui: number;
  wagering_multiplier: number;
  expires_at: string | null;
  filters: BonusManualFilters;
}

export type BonusManualGrantPayload =
  | BonusManualGrantRulePayload
  | BonusManualGrantAmountPayload;

export interface BonusManualGrantResponse {
  granted?: number;
  skipped?: number;
  grant_id?: number;
  batch_id?: number;
  estimated_players?: number;
  source_ref?: string;
  skip_reasons?: BonusSkipReasonItem[];
  errors?: string[] | Record<string, unknown>[];
  message?: string;
  [key: string]: unknown;
}

export interface BonusGrant {
  id: number;
  int_casino_id?: string;
  bonus_rule_id?: number | string | null;
  rule_id?: number | string | null;
  player_id?: number | string | null;
  wallet_id_bonus?: number | string | null;
  status?: string;
  source_type?: string;
  source_ref?: string;
  source?: string;
  currency_id?: string | null;
  currency_code?: string | null;
  amount_granted_base?: number | string | null;
  amount_remaining_base?: number | string | null;
  amount_granted_ui?: number | string | null;
  amount_remaining_ui?: number | string | null;
  wagering_required_base?: number | string | null;
  wagering_progress_base?: number | string | null;
  wagering_required_ui?: number | string | null;
  wagering_progress_ui?: number | string | null;
  expires_at?: string | null;
  meta?: {
    rule_name?: string;
    batch_id?: number;
    admin_id?: number;
    [key: string]: unknown;
  } | null;
  amount_ui?: number | string | null;
  wagering_multiplier?: number | null;
  created_at?: string;
  created_by?: string;
  granted?: number;
  skipped?: number;
  [key: string]: unknown;
}

export interface BonusGrantEvent {
  id?: number;
  bonus_grant_id?: number | string;
  grant_id?: number;
  player_id?: number | string;
  username?: string;
  event_type?: string;
  event_label?: string;
  event_description?: string;
  event_effect?: "credit" | "debit" | string;
  status?: string;
  amount_base?: number | string | null;
  amount_ui?: number | string | null;
  idempotency_key?: string;
  reference_type?: string;
  reference_id?: number | string | null;
  meta?: {
    player_id?: number;
    wallet_id_bonus?: number;
    [key: string]: unknown;
  } | null;
  currency_code?: string | null;
  message?: string;
  created_at?: string;
  updated_at?: string;
  wallet_balance_debug?: {
    wallet_id?: number | string | null;
    wallet_purpose?: string | null;
    entry_type?: string | null;
    delta_base?: number | string | null;
    delta_ui?: number | string | null;
    delta_pct?: number | string | null;
    available_before_base?: number | string | null;
    available_after_base?: number | string | null;
    available_before_ui?: number | string | null;
    available_after_ui?: number | string | null;
  }[] | null;
  wallet_split_total_base?: number | string | null;
  wallet_split_total_ui?: number | string | null;
  [key: string]: unknown;
}

export interface BonusGrantsFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: string;
  source?: string;
  mode?: "rule" | "amount" | "";
  rule_id?: number | null;
  date_from?: string;
  date_to?: string;
}

export interface BonusGrantsListResponse {
  data?: BonusGrant[];
  items?: BonusGrant[];
  total?: number;
  page?: number;
  per_page?: number;
  meta?: {
    total?: number;
    current_page?: number;
    per_page?: number;
  };
}

export interface BonusGrantEventsResponse {
  data?: BonusGrantEvent[];
  items?: BonusGrantEvent[];
  events?: BonusGrantEvent[];
}

export interface BonusStatsCard {
  label: string;
  value: number;
}

export interface BonusStatsCurrencyBreakdownItem {
  currency_id?: string;
  currency_code: string;
  grants_count: number | string;
  granted_base: number | string;
  consumed_base: number | string;
  remaining_base: number | string;
  granted_ui?: number | string;
  consumed_ui?: number | string;
  remaining_ui?: number | string;
}

export interface BonusStatsDailyTrendItem {
  date?: string;
  day?: string;
  week?: string;
  month?: string;
  period?: string;
  grants_count?: number | string;
  granted_base?: number | string;
  consumed_base?: number | string;
  remaining_base?: number | string;
  granted_ui?: number | string;
  consumed_ui?: number | string;
  remaining_ui?: number | string;
}

export interface BonusStatsTopRuleItem {
  rule_id?: number | string;
  rule_name?: string;
  grants_count?: number | string;
  granted_base?: number | string;
  consumed_base?: number | string;
  remaining_base?: number | string;
  granted_ui?: number | string;
  consumed_ui?: number | string;
  remaining_ui?: number | string;
}

export interface BonusStatsResponse {
  total_grants?: number | string;
  total_granted_base?: number | string;
  total_remaining_base?: number | string;
  total_consumed_base?: number | string;
  total_granted_ui?: number | string;
  total_remaining_ui?: number | string;
  total_consumed_ui?: number | string;
  aggregate_currency_code?: string;
  consumption_rate_pct?: number | string;
  active_grants?: number | string;
  consumed_grants?: number | string;
  expired_grants?: number | string;
  revoked_grants?: number | string;
  status_breakdown?: Record<string, number | string>;
  source_breakdown?: Record<string, number | string>;
  currency_breakdown?: BonusStatsCurrencyBreakdownItem[];
  daily_trend?: BonusStatsDailyTrendItem[];
  top_rules?: BonusStatsTopRuleItem[];
  totals?: Record<string, number | string>;
  cards?: Record<string, number | string>;
  by_status?: Record<string, number | string>;
  by_source?: Record<string, number | string>;
  by_mode?: Record<string, number | string>;
  [key: string]: unknown;
}

export interface BonusApiResult<T> {
  success: boolean;
  data: T | null;
  error: unknown;
  message?: string;
}

export interface BonusWalletBackfillResponse {
  newWallets?: number;
  [key: string]: unknown;
}

export type BonusTestScenario =
  | "register_flow_readiness"
  | "deposit_flow_readiness"
  | "withdraw_lock_consistency"
  | "wallet_consumption_simulation"
  | "all";

export interface BonusTestRun {
  id: number;
  uuid: string;
  int_casino_id?: string | null;
  scenario: BonusTestScenario | string;
  status: "pending" | "running" | "passed" | "failed" | string;
  requested_by?: number | null;
  requested_by_name?: string | null;
  started_at?: string | null;
  finished_at?: string | null;
  summary_json?: Record<string, unknown> | null;
  meta?: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
}

export interface BonusTestRunLog {
  id: number;
  run_id: number;
  level: "info" | "warn" | "error" | "success" | string;
  step_code?: string | null;
  message: string;
  context_json?: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
}
