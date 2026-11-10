<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use Auditable, HasUuids;

    protected string $auditModule = 'branch_settings';
    protected $primaryKey = 'branch_id';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'agency_id',
        'name',
        'location_id',
        'description',
        'status',
        'contact_number',
        'document',
        'settings',
        'image',
        'email'
    ];

    public function hasFacilitySubscription()
    {
        return $this->hasPlanAccess(['B', 'C']);
    }

    public function hasHomecareSubscription()
    {
        return $this->hasPlanAccess(['A', 'C']);
    }

    public function hasActiveSubscription()
    {
        return $this->hasPlanAccess(['A', 'B', 'C']);
    }

    private function hasPlanAccess(array $planCodes)
    {
        $subscriptions = $this->subscriptions();

        self::constrainPlanAccess($subscriptions, $planCodes);

        return $subscriptions->exists();
    }

    private static function constrainPlanAccess($subscriptions, array $planCodes): void
    {
        $subscriptions
            ->where('branch_subscription.status', BranchSubscription::STATUS_APPROVED)
            ->where('subscriptions.status', Subscription::STATUS_ACTIVE)
            ->whereDate('subscriptions.end_date', '>=', now())
            ->where(function ($q) use ($planCodes) {
                $q->where(function ($follows) use ($planCodes) {
                    $follows->whereNull('branch_subscription.plan_id');
                    self::subscriptionPlanIn($follows, $planCodes);
                })->orWhere(function ($additional) use ($planCodes) {
                    $additional->whereNotNull('branch_subscription.plan_id')
                        ->where('branch_subscription.type', BranchSubscription::TYPE_ADDITIONAL)
                        ->whereIn('branch_subscription.plan_id', Plan::select('plan_id')->whereIn('plan_code', $planCodes));
                })->orWhere(function ($included) use ($planCodes) {
                    $included->whereNotNull('branch_subscription.plan_id')
                        ->where('branch_subscription.type', BranchSubscription::TYPE_INCLUDED)
                        ->where(function ($own) use ($planCodes) {
                            $own->where(function ($onHybrid) use ($planCodes) {
                                self::subscriptionPlanIn($onHybrid, [Plan::CODE_HYBRID]);
                                $onHybrid->whereIn('branch_subscription.plan_id', Plan::select('plan_id')->whereIn('plan_code', $planCodes));
                            })->orWhere(function ($followsMain) use ($planCodes) {
                                self::subscriptionPlanIn($followsMain, array_values(array_diff($planCodes, [Plan::CODE_HYBRID])));
                            });
                        });
                });
            });
    }

    private static function subscriptionPlanIn($query, array $planCodes): void
    {
        if (!$planCodes) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function ($q) use ($planCodes) {
            $q->where(function ($current) use ($planCodes) {
                $current->where(function ($notDue) {
                    $notDue->whereNull('subscriptions.pending_plan_starts_at')
                        ->orWhereDate('subscriptions.pending_plan_starts_at', '>', now());
                })
                    ->whereHas('plans', fn($p) => $p->whereIn('plan_code', $planCodes));
            })->orWhere(function ($upgraded) use ($planCodes) {
                $upgraded->whereDate('subscriptions.pending_plan_starts_at', '<=', now())
                    ->whereHas('pendingPlan', fn($p) => $p->whereIn('plan_code', $planCodes));
            });
        });
    }

    public function effectivePlanCodes(): array
    {
        return $this->subscriptions
            ->map(fn($subscription) => $subscription->planForLink($subscription->pivot?->plan_id, $subscription->pivot?->type)?->plan_code)
            ->filter()
            ->values()
            ->all();
    }

    protected $casts = [
        'settings' => 'array',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function contracts()
    {
        return $this->hasMany(BranchContract::class, 'branch_id', 'branch_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function subscriptions()
    {
        return $this->belongsToMany(
            Subscription::class,
            'branch_subscription',
            'branch_id',
            'subscription_id'
        )
            ->using(BranchSubscription::class)
            ->withPivot(['branch_subscription_id', 'uuid', 'status', 'type', 'plan_id', 'active_until'])
            ->withTimestamps();
    }

    public function isDeactivated(): bool
    {
        return $this->subscriptionLink()->whereNotNull('active_until')->exists();
    }

    // Only a branch on an approved live plan is offered to the public. Trials and
    // anything pending, rejected or cancelled stay out; an expired one is still
    // listed (its services just show as unavailable).
    public function scopeOnLivePlan(Builder $query): Builder
    {
        return $query->whereHas('subscriptionLink', fn($link) => $link
            ->where('status', BranchSubscription::STATUS_APPROVED)
            ->whereHas('subscription', fn($subscription) => $subscription
                ->where('mode', Subscription::MODE_LIVE)
                ->whereNotIn('status', [
                    Subscription::STATUS_PENDING,
                    Subscription::STATUS_REJECTED,
                    Subscription::STATUS_CANCELLED,
                ])));
    }

    public function subscriptionLink()
    {
        return $this->hasOne(BranchSubscription::class, 'branch_id', 'branch_id')
            ->latestOfMany('branch_subscription_id');
    }

    public function agencies()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'agency_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'branch_id', 'branch_id');
    }


    public function rooms()
    {
        return $this->hasMany(Room::class, 'branch_id', 'branch_id');
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'branch_id', 'branch_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'branch_id', 'branch_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'branch_id', 'branch_id');
    }

    public function diagnosisCases()
    {
        return $this->hasMany(DiagnosisCase::class, 'branch_id', 'branch_id');
    }

    public function verificationLogs()
    {
        return $this->hasManyThrough(
            VerificationLog::class,
            BranchSubscription::class,
            'branch_id',
            'branch_subscription_id',
            'branch_id',
            'branch_subscription_id'
        );
    }

    public function employees()
    {
        return $this->hasMany(EmployeeBranch::class, 'branch_id', 'branch_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'branch_id', 'branch_id');
    }

    public function images()
    {
        return $this->hasMany(BranchImage::class, 'branch_id', 'branch_id');
    }

    public function availableBeds()
    {
        return $this->hasMany(Bed::class, 'room_id', 'room_id')
            ->where('status', 'Available');
    }
}
