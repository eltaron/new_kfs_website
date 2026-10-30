<?php

namespace Modules\Syndicate\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Syndicate\Models\IntegrationAccount;

/** من قام بالإجراء: موظف (user)، نظام النقابة عبر الـ API (api)، أو النظام (system). */
final class Actor
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $id,
        public readonly string $name,
        public readonly ?User $user = null,
    ) {
    }

    public static function fromUser(?Authenticatable $user): self
    {
        if (! $user) {
            return self::system();
        }

        return new self('user', (string) $user->getAuthIdentifier(), (string) ($user->name ?? 'مستخدم'), $user instanceof User ? $user : null);
    }

    public static function fromIntegration(IntegrationAccount $account): self
    {
        return new self('api', (string) $account->getKey(), 'نظام النقابة: ' . $account->name);
    }

    public static function system(): self
    {
        return new self('system', null, 'النظام');
    }

    /** يحدد الفاعل من الطلب الحالي (توكن تكامل أو مستخدم مسجل). */
    public static function current(): self
    {
        $user = auth()->user();

        if ($user instanceof IntegrationAccount) {
            return self::fromIntegration($user);
        }

        return self::fromUser($user);
    }

    public function reference(): string
    {
        return $this->type . ($this->id ? ':' . $this->id : '');
    }

    public function toEventAttributes(): array
    {
        return ['actor_type' => $this->type, 'actor_id' => $this->id, 'actor_name' => $this->name];
    }
}
