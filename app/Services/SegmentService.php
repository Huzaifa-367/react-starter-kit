<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSegment;
use Illuminate\Database\Eloquent\Builder;

class SegmentService
{
    /**
     * Convert filter JSON array to Eloquent query Builder on User model.
     */
    public function buildQuery(array $filters): Builder
    {
        $query = User::query();

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if (empty($field)) {
                continue;
            }

            // Normalize operator
            $operator = strtolower(trim($operator));
            if (!in_array($operator, ['=', '!=', '>', '>=', '<', '<=', 'like', 'in', 'not in'])) {
                $operator = '=';
            }

            if ($field === 'plan_id') {
                // Filter users by active subscription plan ID
                $query->whereHas('subscriptions', function ($q) use ($operator, $value) {
                    if ($operator === 'in' || $operator === 'not in') {
                        $values = is_array($value) ? $value : [$value];
                        if ($operator === 'in') {
                            $q->whereIn('plan_id', $values);
                        } else {
                            $q->whereNotIn('plan_id', $values);
                        }
                    } else {
                        $q->where('plan_id', $operator, $value);
                    }
                    $q->whereIn('status', ['active', 'trialing', 'grace']);
                });
            } elseif ($field === 'role') {
                // Filter users by Spatie role
                $query->whereHas('roles', function ($q) use ($operator, $value) {
                    if ($operator === 'in' || $operator === 'not in') {
                        $values = is_array($value) ? $value : [$value];
                        if ($operator === 'in') {
                            $q->whereIn('name', $values);
                        } else {
                            $q->whereNotIn('name', $values);
                        }
                    } else {
                        $q->where('name', $operator, $value);
                    }
                });
            } else {
                // Regular column query
                if ($operator === 'in') {
                    $values = is_array($value) ? $value : [$value];
                    $query->whereIn($field, $values);
                } elseif ($operator === 'not in') {
                    $values = is_array($value) ? $value : [$value];
                    $query->whereNotIn($field, $values);
                } else {
                    $query->where($field, $operator, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Evaluate the segment filters, count matching users, and update stats.
     */
    public function evaluate(UserSegment $segment): int
    {
        $count = $this->buildQuery($segment->filters)->count();

        $segment->update([
            'user_count' => $count,
            'last_evaluated_at' => now(),
        ]);

        return $count;
    }

    /**
     * Get list of user IDs that belong to the segment.
     */
    public function getUserIds(UserSegment $segment): array
    {
        return $this->buildQuery($segment->filters)->pluck('id')->toArray();
    }

    /**
     * Refresh counts for all user segments in the system.
     */
    public function refreshAllCounts(): void
    {
        $segments = UserSegment::all();
        foreach ($segments as $segment) {
            $this->evaluate($segment);
        }
    }
}
