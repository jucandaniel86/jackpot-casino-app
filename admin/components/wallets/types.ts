export type WalletType = {
  id: number;
  code: string;
  name: string;
  symbol: string;
  is_fiat: number;
  precision: number;
  min_amount: number;
  supports_tag: number;
  network_data: any[];
  active: number;
  wallet_uuid: string;
  icon: string;
  currency_id: string;
  purpose: "real" | "bonus";
};
