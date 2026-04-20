/**
 * Changelog:
 * - Added condition_json metadata model (conditionJsonExamples, conditionFields, commonMistakes).
 * - Added explicit first_deposit threshold formats and alias docs.
 * - Added ready-to-use rule templates for quick copy.
 */

export type BonusRiskLevel = "low" | "medium" | "high";

export interface BonusRuleHelpField {
  name: string;
  description: string;
  example: string;
}

export interface BonusRuleConditionExample {
  label: string;
  json: string;
}

export interface BonusRuleConditionField {
  name: string;
  type: string;
  required: boolean;
  description: string;
}

export interface BonusRuleHelpItem {
  id: string;
  title: string;
  shortDescription: string;
  icon: string;
  category: "acquisition" | "retention" | "vip" | "special";
  useCases: string[];
  configFields: BonusRuleHelpField[];
  conditionJsonExamples?: BonusRuleConditionExample[];
  conditionFields?: BonusRuleConditionField[];
  commonMistakes?: string[];
  exampleRule: Record<string, unknown>;
  risks: string[];
  bestPractices: string[];
  tags: string[];
  riskLevel: BonusRiskLevel;
}

export interface BonusRuleTemplate {
  id: string;
  title: string;
  payload: Record<string, unknown>;
}

export const bonusRuleHelpItems: BonusRuleHelpItem[] = [
  {
    id: "register_bonus_real_first_bonus_wins",
    title: "Register bonus (real first, wins stay in bonus)",
    shortDescription:
      "Fixed register bonus with bonus-wallet win destination, 35x bonus wagering and capped conversion to real wallet.",
    icon: "ph-gift",
    category: "acquisition",
    useCases: ["Welcome campaign with protected wallet flow"],
    configFields: [
      { name: "trigger_type", description: "Trigger at signup", example: "register" },
      { name: "campaign_type", description: "Campaign profile", example: "register_bonus" },
      { name: "reward_type", description: "Reward mode", example: "fixed_amount" },
      { name: "reward_value", description: "Granted bonus amount", example: "100" },
      { name: "bonus_wager_multiplier", description: "Bonus wagering requirement", example: "35" },
      { name: "consume_priority", description: "Wallet consumption order", example: "real_first" },
      { name: "win_destination", description: "Where wins are credited", example: "bonus_wallet" },
      { name: "max_convert_to_real_ui", description: "Cap moved to real wallet after completion", example: "100" },
      { name: "expire_after_days", description: "Grant expiry period in days", example: "30" },
    ],
    exampleRule: {
      name: "Register 100 / 35x / real-first",
      trigger_type: "register",
      campaign_type: "register_bonus",
      reward_type: "fixed_amount",
      reward_value: 100,
      currency_id: "SOLANA:PEP",
      currency_code: "PEP",
      bonus_wager_multiplier: 35,
      consume_priority: "real_first",
      win_destination: "bonus_wallet",
      max_convert_to_real_ui: 100,
      expire_after_days: 30,
      stacking_policy: "exclusive",
      is_active: true,
    },
    risks: ["Higher accounting complexity due to cross-wallet conversion"],
    bestPractices: [
      "Set explicit max_convert_to_real_ui for predictable cost.",
      "Keep stacking_policy exclusive for register offers.",
    ],
    tags: ["register", "campaign_type", "real_first", "bonus_wallet", "conversion_cap"],
    riskLevel: "medium",
  },
  {
    id: "deposit_bonus_x2_real1x_bonus35x",
    title: "Deposit bonus x2 (real 1x + bonus 35x)",
    shortDescription:
      "Deposit campaign with 2x bonus value, separate wagering tracks and withdraw lock until both tracks are completed.",
    icon: "ph-coins",
    category: "acquisition",
    useCases: ["High-intent deposit conversion campaigns"],
    configFields: [
      { name: "trigger_type", description: "Deposit trigger", example: "deposit" },
      { name: "campaign_type", description: "Campaign profile", example: "deposit_bonus" },
      { name: "reward_type", description: "Reward mode", example: "percentage" },
      { name: "reward_value", description: "Match percentage (2x)", example: "200" },
      { name: "deposit_bonus_multiplier", description: "Optional explicit multiplier", example: "2" },
      { name: "real_wager_multiplier", description: "Rollover on deposit amount", example: "1" },
      { name: "bonus_wager_multiplier", description: "Rollover on bonus amount", example: "35" },
      { name: "consume_priority", description: "Wallet consumption order", example: "real_first" },
      { name: "win_destination", description: "Where wins are credited", example: "bonus_wallet" },
      { name: "max_convert_to_real_ui", description: "Cap moved to real wallet after completion", example: "100" },
    ],
    conditionJsonExamples: [
      {
        label: "Minimum deposit threshold",
        json: '{\n  "min_deposit_ui": "210"\n}',
      },
    ],
    conditionFields: [
      {
        name: "min_deposit_ui",
        type: "string(decimal)",
        required: false,
        description: "Apply rule only if deposit >= threshold.",
      },
    ],
    exampleRule: {
      name: "Deposit x2 / real1x bonus35x",
      trigger_type: "deposit",
      campaign_type: "deposit_bonus",
      reward_type: "percentage",
      reward_value: 200,
      deposit_bonus_multiplier: 2,
      condition_json: {
        min_deposit_ui: "210",
      },
      currency_id: "SOLANA:PEP",
      currency_code: "PEP",
      real_wager_multiplier: 1,
      bonus_wager_multiplier: 35,
      consume_priority: "real_first",
      win_destination: "bonus_wallet",
      max_convert_to_real_ui: 100,
      stacking_policy: "exclusive",
      is_active: true,
    },
    risks: ["High promo cost if cap and thresholds are omitted"],
    bestPractices: [
      "Always define min_deposit_ui and conversion cap.",
      "Monitor ratio between converted_to_real and deposited amounts.",
    ],
    tags: ["deposit", "x2", "real_wager", "bonus_wager", "withdraw_lock"],
    riskLevel: "high",
  },
  {
    id: "welcome_register",
    title: "Welcome bonus on registration",
    shortDescription: "Fixed bonus granted right after account creation.",
    icon: "ph-confetti",
    category: "acquisition",
    useCases: ["Onboarding campaigns", "Faster first-session activation"],
    configFields: [
      { name: "trigger_type", description: "Starting event", example: "register" },
      { name: "reward_type", description: "Reward type", example: "fixed_amount" },
      { name: "reward_value", description: "Bonus value", example: "10" },
      {
        name: "wagering_multiplier",
        description: "Minimum rollover requirement",
        example: "20",
      },
    ],
    exampleRule: {
      name: "Welcome 10",
      trigger_type: "register",
      reward_type: "fixed_amount",
      reward_value: 10,
      currency_id: "SOLANA:PEP",
      wagering_multiplier: 20,
      stacking_policy: "exclusive",
    },
    risks: ["Multi-account abuse"],
    bestPractices: ["Use exclusive stacking by default", "Enable anti-fraud filters"],
    tags: ["welcome", "register", "no deposit"],
    riskLevel: "high",
  },
  {
    id: "first_deposit_match",
    title: "First deposit match",
    shortDescription: "Percentage match on first deposit up to a cap.",
    icon: "ph-percent",
    category: "acquisition",
    useCases: ["Converting signups into first-time depositors"],
    configFields: [
      { name: "trigger_type", description: "First deposit trigger", example: "first_deposit" },
      { name: "reward_type", description: "Reward type", example: "percentage" },
      { name: "reward_value", description: "Match percentage", example: "100" },
      { name: "max_reward_amount", description: "Bonus cap", example: "200" },
    ],
    conditionJsonExamples: [
      {
        label: "Flat format",
        json: '{\n  "min_deposit_ui": "100"\n}',
      },
      {
        label: "Nested format",
        json: '{\n  "first_deposit": {\n    "min_deposit_ui": "100"\n  }\n}',
      },
      {
        label: "Alias fallback",
        json: '{\n  "first_deposit": {\n    "min_amount_ui": "100"\n  }\n}',
      },
    ],
    conditionFields: [
      {
        name: "min_deposit_ui",
        type: "string(decimal)",
        required: false,
        description:
          "Rule is applied only when deposit amount >= min_deposit_ui.",
      },
      {
        name: "min_amount_ui",
        type: "string(decimal)",
        required: false,
        description: "Fallback alias for min_deposit_ui.",
      },
      {
        name: "first_deposit",
        type: "object",
        required: false,
        description: "Optional nested wrapper for first-deposit conditions.",
      },
    ],
    commonMistakes: [
      "Using number values instead of string decimals in JSON.",
      "Using unsupported key names like minDeposit.",
      "Placing min_deposit_ui outside condition_json.",
      "Expecting the rule to trigger below the minimum deposit threshold.",
    ],
    exampleRule: {
      name: "1st Deposit 100% up to 200",
      trigger_type: "first_deposit",
      reward_type: "percentage",
      reward_value: 100,
      max_reward_amount: 200,
      condition_json: {
        first_deposit: {
          min_deposit_ui: "100",
        },
      },
      wagering_multiplier: 25,
      stacking_policy: "exclusive",
    },
    risks: ["High acquisition cost on new cohorts"],
    bestPractices: ["Always define a cap", "Track CPA and first-week retention"],
    tags: ["deposit", "match", "condition_json", "min_deposit_ui", "first_deposit"],
    riskLevel: "medium",
  },
  {
    id: "reload_bonus",
    title: "Reload bonus (daily/weekly)",
    shortDescription: "Recurring bonus for repeated deposits.",
    icon: "ph-arrow-clockwise",
    category: "retention",
    useCases: ["Retaining active players"],
    configFields: [
      { name: "trigger_type", description: "Recurring deposit trigger", example: "deposit" },
      {
        name: "condition_json",
        description: "Frequency and threshold settings",
        example: '{"period":"weekly","min_deposit_ui":"50"}',
      },
      { name: "priority", description: "Rule execution order", example: "20" },
    ],
    conditionJsonExamples: [
      {
        label: "Weekly reload with minimum deposit",
        json: '{\n  "period": "weekly",\n  "min_deposit_ui": "50"\n}',
      },
    ],
    conditionFields: [
      {
        name: "period",
        type: "string",
        required: false,
        description: "Frequency window, e.g. daily or weekly.",
      },
      {
        name: "min_deposit_ui",
        type: "string(decimal)",
        required: false,
        description: "Minimum qualifying deposit for reload eligibility.",
      },
    ],
    exampleRule: {
      name: "Weekly Reload 25%",
      trigger_type: "deposit",
      condition_json: { period: "weekly", min_deposit_ui: "50" },
      reward_type: "percentage",
      reward_value: 25,
      max_reward_amount: 50,
      wagering_multiplier: 15,
    },
    risks: ["Rule overlap with other deposit offers"],
    bestPractices: ["Define clear priority", "Review stacking policy across campaigns"],
    tags: ["reload", "weekly", "condition_json", "min_deposit_ui"],
    riskLevel: "medium",
  },
  {
    id: "cashback_lossback",
    title: "Cashback / lossback",
    shortDescription: "Returns a percentage of net loss for a given window.",
    icon: "ph-arrow-u-down-left",
    category: "retention",
    useCases: ["Recovering players after losing sessions"],
    configFields: [
      {
        name: "condition_json",
        description: "Evaluation interval",
        example: '{"window":"7d"}',
      },
      { name: "reward_value", description: "Lossback percentage", example: "10" },
      { name: "max_reward_amount", description: "Cost cap", example: "100" },
    ],
    exampleRule: {
      name: "Weekly Cashback 10%",
      trigger_type: "custom",
      condition_json: { net_loss_min_ui: 50, window: "7d" },
      reward_type: "percentage",
      reward_value: 10,
      max_reward_amount: 100,
      wagering_multiplier: 5,
    },
    risks: ["Incorrect net-loss formula interpretation"],
    bestPractices: ["Validate loss calculation logic", "Exclude restricted users"],
    tags: ["cashback", "lossback"],
    riskLevel: "medium",
  },
  {
    id: "free_spins_bets",
    title: "Free spins / free bets",
    shortDescription: "Promotional credits for eligible games or bet flows.",
    icon: "ph-game-controller",
    category: "special",
    useCases: ["Theme campaigns", "New game launches"],
    configFields: [
      {
        name: "condition_json",
        description: "Eligible game list",
        example: '{"games":["slot_1"]}',
      },
      {
        name: "valid_until",
        description: "Short expiry window",
        example: "2026-03-01 23:59",
      },
    ],
    exampleRule: {
      name: "20 Free Spins Weekend",
      trigger_type: "custom",
      condition_json: { spins_count: 20, games: ["slot_1", "slot_2"] },
      reward_type: "fixed_amount",
      reward_value: 20,
      valid_until: "2026-03-01 23:59:00",
    },
    risks: ["Unclear game eligibility configuration"],
    bestPractices: ["Limit game catalog", "Use short expiry"],
    tags: ["free spins", "free bets"],
    riskLevel: "low",
  },
  {
    id: "high_roller",
    title: "High-roller bonus",
    shortDescription: "Premium bonus for players with large deposits.",
    icon: "ph-crown-simple",
    category: "vip",
    useCases: ["Retention of high-value players"],
    configFields: [
      {
        name: "condition_json",
        description: "Minimum total deposit threshold",
        example: '{"min_total_deposit_ui":"1000"}',
      },
      {
        name: "reward_value",
        description: "Premium bonus amount",
        example: "250",
      },
    ],
    conditionJsonExamples: [
      {
        label: "Minimum total deposit threshold",
        json: '{\n  "min_total_deposit_ui": "1000"\n}',
      },
    ],
    conditionFields: [
      {
        name: "min_total_deposit_ui",
        type: "string(decimal)",
        required: false,
        description: "Eligible only when cumulative deposits reach this value.",
      },
    ],
    exampleRule: {
      name: "High Roller 250",
      trigger_type: "deposit",
      condition_json: { min_total_deposit_ui: "1000" },
      reward_type: "fixed_amount",
      reward_value: 250,
      wagering_multiplier: 12,
    },
    risks: ["High cost per player"],
    bestPractices: ["Require manual approval", "Set daily cap per player"],
    tags: ["vip", "high roller", "condition_json"],
    riskLevel: "high",
  },
  {
    id: "vip_tier",
    title: "VIP tier bonus",
    shortDescription: "Tier-based bonus for bronze/silver/gold segments.",
    icon: "ph-medal",
    category: "vip",
    useCases: ["Loyalty program rewards"],
    configFields: [
      { name: "condition_json", description: "Target tier", example: '{"vip_tier":"gold"}' },
      { name: "priority", description: "Higher execution priority", example: "30" },
    ],
    exampleRule: {
      name: "VIP Gold Monthly",
      trigger_type: "custom",
      condition_json: { vip_tier: "gold", period: "monthly" },
      reward_type: "fixed_amount",
      reward_value: 100,
      stacking_policy: "stackable",
    },
    risks: ["Tier drift due to stale segmentation"],
    bestPractices: ["Sync tiers daily", "Test exclusion rules"],
    tags: ["vip", "tier"],
    riskLevel: "medium",
  },
  {
    id: "reactivation",
    title: "Reactivation bonus",
    shortDescription: "Win-back offer for inactive players.",
    icon: "ph-plugs-connected",
    category: "retention",
    useCases: ["Reactivation campaigns"],
    configFields: [
      {
        name: "condition_json",
        description: "Inactive days threshold",
        example: '{"inactive_days":"14"}',
      },
      {
        name: "valid_until",
        description: "Reactivation offer window",
        example: "2026-03-05 23:59",
      },
    ],
    conditionJsonExamples: [
      {
        label: "Inactive users threshold",
        json: '{\n  "inactive_days": "14"\n}',
      },
    ],
    conditionFields: [
      {
        name: "inactive_days",
        type: "string(integer)",
        required: false,
        description: "Eligible only for players inactive for at least N days.",
      },
    ],
    exampleRule: {
      name: "Back in 14 Days",
      trigger_type: "custom",
      condition_json: { inactive_days: "14" },
      reward_type: "fixed_amount",
      reward_value: 15,
      valid_until: "2026-03-05 23:59:00",
    },
    risks: ["Targeting users who are already active"],
    bestPractices: ["Exclude active=1 users", "Use a clear campaign message"],
    tags: ["reactivation", "winback", "condition_json"],
    riskLevel: "low",
  },
  {
    id: "birthday",
    title: "Birthday / anniversary bonus",
    shortDescription: "Occasional bonus for personal date milestones.",
    icon: "ph-cake",
    category: "special",
    useCases: ["Personalized CRM rewards"],
    configFields: [
      {
        name: "condition_json",
        description: "Calendar event trigger",
        example: '{"event":"birthday"}',
      },
      {
        name: "reward_value",
        description: "Symbolic bonus amount",
        example: "5",
      },
    ],
    exampleRule: {
      name: "Birthday Gift",
      trigger_type: "custom",
      condition_json: { event: "birthday" },
      reward_type: "fixed_amount",
      reward_value: 5,
      wagering_multiplier: 1,
    },
    risks: ["Incomplete user profile data"],
    bestPractices: ["Validate birth date", "Limit to once per year"],
    tags: ["birthday", "anniversary"],
    riskLevel: "low",
  },
  {
    id: "mission_challenge",
    title: "Mission / challenge bonus",
    shortDescription: "Bonus for completing a short-term mission.",
    icon: "ph-target",
    category: "special",
    useCases: ["Gamification", "Higher engagement"],
    configFields: [
      {
        name: "condition_json",
        description: "Mission condition",
        example: '{"bets_count":10,"window":"24h"}',
      },
      {
        name: "reward_value",
        description: "Reward amount",
        example: "20",
      },
    ],
    exampleRule: {
      name: "10 Bets in 24h",
      trigger_type: "custom",
      condition_json: { bets_count: 10, window: "24h" },
      reward_type: "fixed_amount",
      reward_value: 20,
      wagering_multiplier: 10,
    },
    risks: ["Mission farming patterns"],
    bestPractices: ["Set daily per-user cap", "Monitor abuse indicators"],
    tags: ["mission", "challenge"],
    riskLevel: "medium",
  },
];

export const bonusRuleGlobalBestPractices: string[] = [
  "Define cost caps per rule and per player.",
  "Keep stacking policy explicit (stackable vs exclusive).",
  "Monitor granted vs consumed vs remaining continuously.",
  "Roll out to a small segment before full launch.",
];

export const bonusRuleCommonErrors: string[] = [
  "Currency mismatch between rule and active bonus wallet_type.",
  "Percentage reward configured without max_reward_amount.",
  "Overly broad conditions causing uncontrolled cost.",
  "Missing valid_until for short campaign rules.",
];

export const bonusRuleReadyTemplates: BonusRuleTemplate[] = [
  {
    id: "tpl_register_100_35x_real_first",
    title: "Register 100 / 35x / Real First / Bonus Wins",
    payload: {
      trigger_type: "register",
      campaign_type: "register_bonus",
      reward_type: "fixed_amount",
      reward_value: 100,
      bonus_wager_multiplier: 35,
      consume_priority: "real_first",
      win_destination: "bonus_wallet",
      max_convert_to_real_ui: 100,
      expire_after_days: 30,
      stacking_policy: "exclusive",
    },
  },
  {
    id: "tpl_deposit_x2_real1x_bonus35x",
    title: "Deposit x2 / real1x / bonus35x",
    payload: {
      trigger_type: "deposit",
      campaign_type: "deposit_bonus",
      reward_type: "percentage",
      reward_value: 200,
      deposit_bonus_multiplier: 2,
      real_wager_multiplier: 1,
      bonus_wager_multiplier: 35,
      consume_priority: "real_first",
      win_destination: "bonus_wallet",
      max_convert_to_real_ui: 100,
      condition_json: {
        min_deposit_ui: "210",
      },
      stacking_policy: "exclusive",
    },
  },
  {
    id: "tpl_first_deposit_100",
    title: "First Deposit >= 100",
    payload: {
      trigger_type: "first_deposit",
      reward_type: "percentage",
      reward_value: 100,
      condition_json: {
        min_deposit_ui: "100",
      },
    },
  },
  {
    id: "tpl_first_deposit_250",
    title: "First Deposit >= 250",
    payload: {
      trigger_type: "first_deposit",
      reward_type: "percentage",
      reward_value: 100,
      condition_json: {
        first_deposit: {
          min_deposit_ui: "250",
        },
      },
    },
  },
  {
    id: "tpl_high_roller_1000",
    title: "High Roller Deposit >= 1000",
    payload: {
      trigger_type: "deposit",
      reward_type: "fixed_amount",
      reward_value: 250,
      condition_json: {
        min_total_deposit_ui: "1000",
      },
    },
  },
];
