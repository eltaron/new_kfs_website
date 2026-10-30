<?php

namespace Modules\Syndicate\Services;

use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;

/**
 * يحدد مستلمي الإشعارات: مستخدم مسجل بالبوابة (بالرقم القومي) إن وجد،
 * وإلا مستلم "عند الطلب" (بريد/هاتف) من بيانات المعاملة.
 */
class RecipientResolver
{
    /** @return Collection<int, User|AnonymousNotifiable> */
    public function owner(Project $project): Collection
    {
        $user = $project->owner_user_id ? User::find($project->owner_user_id) : null;
        $user ??= $this->userByNationalId($project->owner_national_id);

        return $this->userOrRoute($user, $project->owner_email, $project->owner_phone);
    }

    /** @return Collection<int, User|AnonymousNotifiable> */
    public function engineer(?Certificate $certificate): Collection
    {
        if (! $certificate) {
            return collect();
        }

        $user = null;
        $engineer = $certificate->engineer;
        if ($engineer?->user_id) {
            $user = User::find($engineer->user_id);
        }
        $user ??= $this->userByNationalId($certificate->engineer_national_id);

        return $this->userOrRoute($user, $certificate->engineer_email ?: $engineer?->email, $certificate->engineer_phone ?: $engineer?->phone);
    }

    /** @return Collection<int, User|AnonymousNotifiable> */
    public function applicant(SyndicateRequest $request): Collection
    {
        $user = $request->applicant_user_id ? User::find($request->applicant_user_id) : null;
        $user ??= $this->userByNationalId($request->applicant_national_id);

        return $this->userOrRoute($user, $request->applicant_email, $request->applicant_phone);
    }

    /** موظفو مجلس المدينة + بريد المجلس المضبوط في الإعدادات. */
    public function council(): Collection
    {
        $roles = array_merge([config('syndicate.roles.council')], (array) config('syndicate.roles.council_extra', []));
        $users = $this->usersWithRoles([config('syndicate.roles.council')]);

        // إن لم يوجد مستخدم بدور "مجلس المدينة" نلجأ للأدوار الهندسية القائمة.
        if ($users->isEmpty()) {
            $users = $this->usersWithRoles($roles);
        }

        foreach ((array) config('syndicate.notifications.council_emails', []) as $email) {
            $users->push(Notification::route('mail', $email));
        }

        return $users;
    }

    public function syndicateStaff(): Collection
    {
        return $this->usersWithRoles([config('syndicate.roles.syndicate')]);
    }

    protected function usersWithRoles(array $roles): Collection
    {
        $roles = array_values(array_filter($roles));
        if (! $roles) {
            return collect();
        }

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $roles))->get();
    }

    protected function userByNationalId(?string $nationalId): ?User
    {
        return filled($nationalId) ? User::where('national_id', $nationalId)->first() : null;
    }

    protected function userOrRoute(?User $user, ?string $email, ?string $phone): Collection
    {
        if ($user) {
            return collect([$user]);
        }

        if (blank($email) && blank($phone)) {
            return collect();
        }

        $route = new AnonymousNotifiable();
        if (filled($email)) {
            $route->route('mail', $email);
        }
        if (filled($phone)) {
            $route->route('sms', $phone);
        }

        return collect([$route]);
    }
}
