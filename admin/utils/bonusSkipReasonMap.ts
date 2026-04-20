export type BonusSkipReasonCode =
  | "missing_bonus_wallet_type_or_wallet"
  | "zero_or_invalid_reward_amount"
  | "zero_or_invalid_amount_ui"
  | "unknown_skip_reason";

export interface BonusSkipReasonMeta {
  title: string;
  description: string;
  severity: "info" | "warning" | "error";
}

const SKIP_REASON_MAP: Record<BonusSkipReasonCode, BonusSkipReasonMeta> = {
  missing_bonus_wallet_type_or_wallet: {
    title: "Bonus wallet unavailable",
    description:
      "Player has no eligible bonus wallet type/wallet for the selected currency.",
    severity: "warning",
  },
  zero_or_invalid_reward_amount: {
    title: "Invalid rule reward",
    description:
      "Rule reward resolved to zero or invalid amount for this player.",
    severity: "error",
  },
  zero_or_invalid_amount_ui: {
    title: "Invalid manual amount",
    description:
      "Manual amount is zero or invalid, so grant could not be issued.",
    severity: "error",
  },
  unknown_skip_reason: {
    title: "Unknown skip reason",
    description:
      "Grant was skipped for an unspecified reason. Check backend logs for details.",
    severity: "info",
  },
};

const DEFAULT_META = SKIP_REASON_MAP.unknown_skip_reason;

export function mapBonusSkipReason(reason: string): BonusSkipReasonMeta {
  if (!reason || !(reason in SKIP_REASON_MAP)) {
    return DEFAULT_META;
  }

  return SKIP_REASON_MAP[reason as BonusSkipReasonCode];
}
