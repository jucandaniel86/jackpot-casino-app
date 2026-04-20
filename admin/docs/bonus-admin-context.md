# Bonus Admin Context (Nuxt 3 FE)

## Scope
Acest document este sursa de adevăr pentru modulul FE Admin Bonusuri.

## Core Business Rules
1. Player-ul are wallet-uri separate: `real` și `bonus`.
2. La `bet`: se consumă întâi `bonus`, apoi `real`.
3. La `win`: se creditează întotdeauna în `real` wallet.
4. Refund-ul inversează corect sursa inițială din ledger.

## Backend Endpoints (auth:api)
### Rules
- `GET /api/bonuses/rules`
- `GET /api/bonuses/rules/get?id={id}`
- `POST /api/bonuses/rules/save`
- `DELETE /api/bonuses/rules/delete?id={id}`
- `POST /api/bonuses/rules/toggle`

### Manual Grants
- `POST /api/bonuses/manual/preview`
- `POST /api/bonuses/manual/grant`

### History & Stats
- `GET /api/bonuses/grants`
- `GET /api/bonuses/grants/events?grant_id={id}`
- `GET /api/bonuses/stats`

## Rule Payload (save)
```json
{
  "id": null,
  "int_casino_id": "CASINO_1",
  "name": "Welcome 10",
  "trigger_type": "register",
  "condition_json": { "register": true },
  "reward_type": "fixed_amount",
  "reward_value": 10,
  "currency_id": "SOLANA:PEP",
  "currency_code": "PEP",
  "max_reward_amount": null,
  "wagering_multiplier": 20,
  "valid_from": null,
  "valid_until": null,
  "priority": 10,
  "stacking_policy": "stackable",
  "is_active": true
}
```

## Manual Preview Payload
```json
{
  "int_casino_id": "CASINO_1",
  "amount_ui": 5,
  "filters": {
    "player_ids": [123, 124]
  }
}
```

## Manual Grant Payloads
### By Rule
```json
{
  "int_casino_id": "CASINO_1",
  "name": "Manual rule batch",
  "mode": "rule",
  "rule_id": 1,
  "filters": {
    "player_ids": [123]
  }
}
```

### By Amount
```json
{
  "int_casino_id": "CASINO_1",
  "name": "Manual amount batch",
  "mode": "amount",
  "currency_id": "SOLANA:PEP",
  "amount_ui": 5,
  "wagering_multiplier": 10,
  "expires_at": null,
  "filters": {
    "player_ids": [123, 124]
  }
}
```

## Useful Filters
În `filters`, backend suportă:
- `player_ids: number[]`
- `usernames: string[]`
- `emails: string[]`
- `active: 0 | 1`
- `registered_from: datetime/date`
- `registered_to: datetime/date`
- `min_total_deposit_ui: number`

## FE Screens Expected
1. Rules list + create/edit/toggle/delete.
2. Manual preview + execute.
3. Grants list + filters + pagination.
4. Grant events viewer.
5. Stats cards + breakdown pe status/source.

## FE Validation Notes
1. `reward_type`: `fixed_amount | percentage`.
2. `stacking_policy`: `stackable | exclusive`.
3. Pentru `mode=rule`, `rule_id` obligatoriu.
4. Pentru `mode=amount`, `currency_id` + `amount_ui` obligatorii.

## Manual Test Checklist
1. Creează rule activă `register` și verifică grant la user nou.
2. Rule inactive nu trebuie să acorde bonus.
3. Manual preview trebuie să afișeze `estimated_players`.
4. Manual grant trebuie să returneze `granted/skipped`.
5. Grants list să includă granturile noi.
6. Events endpoint să arate `issued` pentru grant.
7. La joc:
   - dacă bonus=100 și bet=200 -> bonus devine 0, real scade cu 100.
   - win-ul crește doar real wallet.
