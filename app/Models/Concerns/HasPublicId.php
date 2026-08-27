<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** @mixin Model */
trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function (self $model): void {
            $model->ulid ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @param  Collection<int, string>  $ulids
     * @return Collection<string, int>
     */
    public static function idsByUlid(Collection $ulids): Collection
    {
        $cached = static::cachedIds($ulids);

        $missing = $ulids->diff($cached->keys());

        if ($missing->isEmpty()) {
            return $cached;
        }

        /** @var Collection<string, int> $found */
        $found = static::query()->whereIn('ulid', $missing)->pluck('id', 'ulid');

        static::rememberIds($found);

        return $cached->merge($found);
    }

    /**
     * @param  Collection<int, string>  $ulids
     * @return Collection<string, int>
     */
    protected static function cachedIds(Collection $ulids): Collection
    {
        return collect(Cache::many($ulids->map(fn (string $ulid) => static::ulidCacheKey($ulid))->all()))
            ->filter()
            ->mapWithKeys(fn ($id, string $key) => [Str::afterLast($key, ':') => (int) $id]);
    }

    /** @param  Collection<string, int>  $ids */
    protected static function rememberIds(Collection $ids): void
    {
        if ($ids->isEmpty()) {
            return;
        }

        Cache::putMany(
            $ids->mapWithKeys(fn (int $id, string $ulid) => [static::ulidCacheKey($ulid) => $id])->all(),
            now()->addDay(),
        );
    }

    protected static function ulidCacheKey(string $ulid): string
    {
        return (new static)->getTable().':ulid:'.$ulid;
    }
}
