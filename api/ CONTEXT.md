# Project Context – Crypto Casino Backend

## Stack

- Laravel 10
- MySQL
- Redis (cache + queues)
- Solana SPL tokens
- Node.js scripts for on-chain tx (withdraw, sweep)

## Core Concepts

- Wallets are virtual (DB)
- Real blockchain wallets are used ONLY for deposit & withdraw
- Ledger-based accounting:
    - wallet_balances (available_base, reserved_base)
    - wallet_ledger_entries (immutable, idempotent)

## Currency Format

- DB currency format: SOLANA:PEP
- UI currency symbol: PEP
- Base units used everywhere (amount_base, decimals=9)

## Deposit Flow

1. SolanaDepositScanner detects SPL token transfer
2. creditAvailable() in WalletLedgerService
3. Insert into transactions (type=deposit)
4. Emit DepositDetected event
5. Optional sweep to treasury

## Withdraw Flow (Manual approval)

1. User submits withdraw request (no auto blockchain tx)
2. Admin reviews request
3. Admin sends funds manually
4. Admin marks withdraw as completed
5. Ledger consumes reserved funds

## Sweep Flow

- Sweep = internal transfer user → treasury
- transaction.type = sweep
- status: pending → confirmed / failed

## Stats

- FinanceOpsDashboardService
- CryptoOpsDashboardService (pending/failed sweeps)
- Always use wallet_balances as source of truth

## Rules

- NEVER use wallet.balance for money
- ALWAYS use amount_base + decimals
- UI conversion ONLY at the edge