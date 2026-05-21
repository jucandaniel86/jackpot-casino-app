<?php

namespace App\Repositories;

use App\Enums\RewardType;
use App\Exceptions\ApiResponseException;
use App\Interfaces\RewardInterface;
use App\Models\Reward;
use App\Models\RewardClaim;
use App\Traits\QueryTrait;
use App\Traits\UploadFilesTrait;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RewardRepository implements RewardInterface
{
    use QueryTrait, UploadFilesTrait;

    public function list(array $params = []): array
    {
        return Reward::query()
            ->tap(fn (Builder $query) => $this->applyTypeFilter($query, $params))
            ->tap(fn (Builder $query) => $this->applyCasinoFilter($query, $params))
            ->tap(fn (Builder $query) => $this->applyDefaultOrder($query))
            ->get()
            ->toArray();
    }

    public function publicList(array $params = []): array
    {
        $rewards = Reward::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->tap(fn (Builder $query) => $this->applyTypeFilter($query, $params))
            ->tap(fn (Builder $query) => $this->applyPublicCasinoFilter($query, $params))
            ->tap(fn (Builder $query) => $this->applyDefaultOrder($query))
            ->get()
            ->toArray();

        return $this->appendClaimState($rewards);
    }

    public function types(): array
    {
        return RewardType::options();
    }

    public function insert(array $params = [])
    {
        try {
            $title = trim($params['title']);
            $casinoId = $params['int_casino_id'] ?? null;

            $reward = Reward::query()->create([
                'uid' => (string)Str::uuid(),
                'int_casino_id' => $casinoId,
                'title' => $title,
                'slug' => $this->makeUniqueSlug($title, $casinoId),
                'subtitle' => $params['subtitle'] ?? null,
                'description' => $params['description'] ?? null,
                'thumbnail' => null,
                'type' => $params['type'] ?? RewardType::DAILY_REDEEM->value,
                'rule' => $this->normalizeRule($params['rule'] ?? null),
                'page_order' => (int)($params['page_order'] ?? 0),
                'is_active' => $this->normalizeBoolean($params['is_active'] ?? true),
                'starts_at' => $params['starts_at'] ?? null,
                'ends_at' => $params['ends_at'] ?? null,
            ]);

            if (isset($params['thumbnail']) && $params['thumbnail'] !== 'null') {
                $reward->thumbnail = $this->uploadThumbnail($params['thumbnail'], $this->uploadPath(), $title);
                $reward->save();
            }

            return $reward;
        } catch (QueryException $exception) {
            activity()
                ->causedBy(null)
                ->withProperties([
                    'message' => $exception->getMessage(),
                    'line' => $exception->getLine(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                ])
                ->log(config('errors.31'));

            throw new ApiResponseException($exception->getMessage());
        }
    }

    public function update(array $params = [])
    {
        try {
            $reward = Reward::query()->find($params['id'] ?? null);

            if (!$reward) {
                throw new \Exception('Invalid ID');
            }

            $title = trim($params['title']);
            $casinoId = $params['int_casino_id'] ?? $reward->int_casino_id;

            $reward->update([
                'int_casino_id' => $casinoId,
                'title' => $title,
                'slug' => $this->makeUniqueSlug($title, $casinoId, $reward->id),
                'subtitle' => $params['subtitle'] ?? null,
                'description' => $params['description'] ?? null,
                'type' => $params['type'] ?? $reward->type,
                'rule' => $this->normalizeRule($params['rule'] ?? null),
                'page_order' => (int)($params['page_order'] ?? 0),
                'is_active' => $this->normalizeBoolean($params['is_active'] ?? true),
                'starts_at' => $params['starts_at'] ?? null,
                'ends_at' => $params['ends_at'] ?? null,
            ]);

            if (isset($params['thumbnail']) && $params['thumbnail'] !== 'null') {
                $oldThumbnail = $reward->thumbnail;
                $reward->thumbnail = $this->uploadThumbnail($params['thumbnail'], $this->uploadPath(), $title, function () use ($oldThumbnail) {
                    if ($oldThumbnail) {
                        $this->deleteFile($this->uploadPath() . $oldThumbnail);
                    }
                });
                $reward->save();
            }

            return $reward;
        } catch (QueryException $exception) {
            activity()
                ->causedBy(null)
                ->withProperties([
                    'message' => $exception->getMessage(),
                    'line' => $exception->getLine(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                ])
                ->log(config('errors.31'));

            throw new ApiResponseException($exception->getMessage());
        }
    }

    public function delete($id)
    {
        return $this->deleteByID(Reward::class, $id, function ($reward) {
            if ($reward->thumbnail) {
                $this->deleteFile($this->uploadPath() . $reward->thumbnail);
            }
        });
    }

    private function uploadPath(): string
    {
        return config('casino.uploads.rewards', '/uploads/rewards/');
    }

    private function normalizeRule($rule): ?array
    {
        if ($rule === null || $rule === '' || $rule === 'null') {
            return null;
        }

        if (is_array($rule)) {
            return $rule;
        }

        $decoded = json_decode($rule, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function applyTypeFilter(Builder $query, array $params): void
    {
        if (isset($params['type']) && in_array($params['type'], RewardType::values(), true)) {
            $query->where('type', $params['type']);
        }
    }

    private function applyCasinoFilter(Builder $query, array $params): void
    {
        if (isset($params['int_casino_id']) && $params['int_casino_id'] !== '') {
            $query->where('int_casino_id', $params['int_casino_id']);
            return;
        }

        if (isset($params['casino_id']) && $params['casino_id'] !== '') {
            $query->where('int_casino_id', $params['casino_id']);
        }
    }

    private function applyPublicCasinoFilter(Builder $query, array $params): void
    {
        $casinoId = $params['int_casino_id'] ?? $params['casino_id'] ?? null;

        if ($casinoId === null || $casinoId === '') {
            $query->whereNull('int_casino_id');
            return;
        }

        $query->where(function ($query) use ($casinoId) {
            $query->where('int_casino_id', $casinoId)
                ->orWhereNull('int_casino_id');
        });
    }

    private function applyDefaultOrder(Builder $query): void
    {
        $query->orderBy('page_order')->orderBy('id');
    }

    private function appendClaimState(array $rewards): array
    {
        if (!request()->bearerToken()) {
            return $rewards;
        }

        try {
            $player = Auth::guard('casino')->user();
        } catch (\Throwable) {
            return $rewards;
        }

        if (!$player) {
            return $rewards;
        }

        $dailyRewards = collect($rewards)
            ->filter(fn (array $reward) => ($reward['type'] ?? null) === RewardType::DAILY_REDEEM->value)
            ->values();

        if ($dailyRewards->isEmpty()) {
            return $rewards;
        }

        $periodKeysByRewardId = $dailyRewards
            ->mapWithKeys(fn (array $reward) => [
                $reward['id'] => $this->periodKey($reward),
            ])
            ->all();

        $claims = RewardClaim::query()
            ->where('player_id', $player->id)
            ->whereIn('reward_id', array_keys($periodKeysByRewardId))
            ->whereIn('period_key', array_values($periodKeysByRewardId))
            ->get(['reward_id', 'period_key', 'claimed_at'])
            ->keyBy(fn (RewardClaim $claim) => $claim->reward_id . ':' . $claim->period_key);

        return array_map(function (array $reward) use ($claims, $periodKeysByRewardId) {
            if (($reward['type'] ?? null) !== RewardType::DAILY_REDEEM->value) {
                return $reward;
            }

            $periodKey = $periodKeysByRewardId[$reward['id']] ?? $this->periodKey($reward);
            $claim = $claims->get($reward['id'] . ':' . $periodKey);
            $nextClaimAt = $this->nextClaimAt($reward);

            $reward['claim_state'] = [
                'is_claimed' => $claim !== null,
                'period_key' => $periodKey,
                'claimed_at' => $claim?->claimed_at?->toIso8601String(),
                'next_claim_at' => $nextClaimAt?->toIso8601String(),
                'seconds_until_next' => $nextClaimAt ? (int)ceil(max(0, now($this->rewardTimezone($reward))->diffInSeconds($nextClaimAt, false))) : null,
                'message' => $claim !== null ? 'You already claimed today\'s reward.' : null,
            ];

            return $reward;
        }, $rewards);
    }

    private function rewardFrequency(array $reward): string
    {
        $frequency = $reward['rule']['frequency'] ?? (
            ($reward['type'] ?? null) === RewardType::DAILY_REDEEM->value ? 'daily' : 'once'
        );

        $frequency = is_string($frequency) && trim($frequency) !== ''
            ? strtolower(trim($frequency))
            : 'once';

        if (in_array($frequency, ['once', 'daily', 'weekly', 'monthly'], true)) {
            return $frequency;
        }

        return ($reward['type'] ?? null) === RewardType::DAILY_REDEEM->value ? 'daily' : 'once';
    }

    private function rewardTimezone(array $reward): string
    {
        $timezone = $reward['rule']['timezone'] ?? config('app.timezone', 'UTC');

        return is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone', 'UTC');
    }

    private function periodKey(array $reward): string
    {
        $now = now($this->rewardTimezone($reward));

        return match ($this->rewardFrequency($reward)) {
            'daily' => $now->format('Y-m-d'),
            'weekly' => $now->format('o-\WW'),
            'monthly' => $now->format('Y-m'),
            default => 'lifetime',
        };
    }

    private function nextClaimAt(array $reward)
    {
        $now = now($this->rewardTimezone($reward));

        return match ($this->rewardFrequency($reward)) {
            'daily' => $now->copy()->addDay()->startOfDay(),
            'weekly' => $now->copy()->addWeek()->startOfWeek(),
            'monthly' => $now->copy()->addMonthNoOverflow()->startOfMonth(),
            default => null,
        };
    }

    private function makeUniqueSlug(string $title, ?string $casinoId = null, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExists($slug, $casinoId, $ignoreId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?string $casinoId = null, ?int $ignoreId = null): bool
    {
        return Reward::query()
            ->where('slug', $slug)
            ->when($casinoId !== null && $casinoId !== '', function ($query) use ($casinoId) {
                $query->where('int_casino_id', $casinoId);
            }, function ($query) {
                $query->whereNull('int_casino_id');
            })
            ->when($ignoreId !== null, function ($query) use ($ignoreId) {
                $query->where('id', '<>', $ignoreId);
            })
            ->exists();
    }
}
