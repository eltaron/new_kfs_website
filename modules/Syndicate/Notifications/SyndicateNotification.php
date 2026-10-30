<?php

namespace Modules\Syndicate\Notifications;

use App\Models\User;
use Filament\Notifications\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Syndicate\Channels\SmsChannel;
use Modules\Syndicate\Channels\WebPushIfSubscribedChannel;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * أساس إشعارات النقابة: قاعدة البيانات (جرس لوحات Filament + لوحة المواطن)، البريد،
 * SMS (واجهة SmsGateway)، وWeb Push عند وجود اشتراك.
 *
 * $audience: owner | engineer | previous_engineer | council | syndicate | applicant
 */
abstract class SyndicateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public ?Certificate $certificate = null,
        public ?SyndicateRequest $request = null,
        public string $audience = 'owner',
    ) {
    }

    abstract public function title(): string;

    abstract public function body(): string;

    /** success | danger | warning | info */
    public function level(): string
    {
        return 'info';
    }

    public function via(object $notifiable): array
    {
        $mail = (bool) config('syndicate.notifications.mail', true);
        $sms = (bool) config('syndicate.notifications.sms', true);

        if ($notifiable instanceof AnonymousNotifiable) {
            return array_values(array_filter([
                $mail && filled($notifiable->routeNotificationFor('mail', $this)) ? 'mail' : null,
                $sms && filled($notifiable->routeNotificationFor('sms', $this)) ? SmsChannel::class : null,
            ]));
        }

        return array_values(array_filter([
            'database',
            $mail && filled($notifiable->email ?? null) ? 'mail' : null,
            $sms && filled($notifiable->phone ?? null) ? SmsChannel::class : null,
            config('syndicate.notifications.webpush', true) && WebPushIfSubscribedChannel::canReach($notifiable)
                ? WebPushIfSubscribedChannel::class : null,
        ]));
    }

    public function url(object $notifiable): string
    {
        if ($notifiable instanceof User) {
            $panel = $this->panelFor($notifiable);
            if ($panel) {
                return url('/' . $panel . '/projects/' . $this->project->getKey());
            }

            return route('syndicate.my-projects');
        }

        return $this->certificate
            ? $this->certificate->verificationUrl()
            : route('syndicate.verify.form');
    }

    protected function panelFor(User $user): ?string
    {
        $roles = config('syndicate.roles');
        if ($this->audience === 'syndicate' && $user->hasAnyRole(array_merge([$roles['syndicate']], $roles['admins']))) {
            return 'syndicate';
        }
        if ($this->audience === 'council' && $user->hasAnyRole(array_merge([$roles['council']], $roles['council_extra'], $roles['admins']))) {
            return 'council';
        }

        return null;
    }

    protected function context(): array
    {
        return array_filter([
            'project_id' => $this->project->getKey(),
            'project_code' => $this->project->code,
            'work_status' => $this->project->work_status,
            'certificate_number' => $this->certificate?->number,
            'transaction_number' => $this->request?->transaction_number,
            'audience' => $this->audience,
            'event' => static::class,
        ], fn ($v) => $v !== null);
    }

    public function toDatabase(object $notifiable): array
    {
        $icon = match ($this->level()) {
            'danger' => 'heroicon-o-exclamation-triangle',
            'success' => 'heroicon-o-check-badge',
            'warning' => 'heroicon-o-clock',
            default => 'heroicon-o-document-text',
        };

        $message = FilamentNotification::make()
            ->title($this->title())
            ->body($this->body())
            ->icon($icon)
            ->status($this->level())
            ->actions([
                FilamentAction::make('view')->label('عرض')->url($this->url($notifiable))->markAsRead(),
            ])
            ->getDatabaseMessage();

        return array_merge($message, ['syndicate' => $this->context(), 'message' => $this->body()]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->title() . ' — محافظة كفر الشيخ')
            ->greeting('السلام عليكم ورحمة الله')
            ->line($this->body())
            ->line('كود المشروع: ' . $this->project->code . ' — ' . $this->project->address . ' (' . $this->project->center . ')');

        if ($this->level() === 'danger') {
            $mail->error();
        }

        return $mail->action('عرض التفاصيل', $this->url($notifiable))
            ->salutation('بوابة محافظة كفر الشيخ — منظومة الإشراف الهندسي');
    }

    public function toSms(object $notifiable): string
    {
        return mb_substr($this->title() . ': ' . $this->body(), 0, 300);
    }

    public function toWebPush(object $notifiable, $notification = null): WebPushMessage
    {
        return (new WebPushMessage())
            ->title($this->title())
            ->body(mb_substr($this->body(), 0, 180))
            ->icon('/favicon/favicon.ico')
            ->data(['url' => $this->url($notifiable)]);
    }

    protected function engineerLabel(?Certificate $certificate = null): string
    {
        $c = $certificate ?? $this->certificate;
        if (! $c) {
            return '';
        }

        return 'م. ' . $c->engineer_name . ($c->office_name ? ' (' . $c->office_name . ')' : '');
    }
}
