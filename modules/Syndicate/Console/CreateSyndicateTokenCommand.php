<?php

namespace Modules\Syndicate\Console;

use Illuminate\Console\Command;
use Modules\Syndicate\Models\IntegrationAccount;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * إنشاء توكن API لحساب تكامل نقابة المهندسين:
 *   php artisan syndicate:token "نقابة كفر الشيخ" --ips=196.1.2.3,10.0.0.0/24 --expires=365
 */
class CreateSyndicateTokenCommand extends Command
{
    protected $signature = 'syndicate:token
        {name : اسم حساب التكامل (يُنشأ إن لم يكن موجوداً)}
        {--ips= : عناوين IP / CIDR مسموح بها لهذا الحساب، مفصولة بفواصل}
        {--expires= : مدة صلاحية التوكن بالأيام (بدون = بلا انتهاء)}
        {--description= : وصف الحساب}
        {--revoke : إلغاء كل التوكنات السابقة لهذا الحساب قبل إنشاء الجديد}';

    protected $description = 'إنشاء توكن Sanctum (القدرة: syndicate) لحساب تكامل نقابة المهندسين';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        if ($name === '') {
            $this->error('اسم الحساب مطلوب.');

            return self::FAILURE;
        }

        $ips = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('ips')))));
        foreach ($ips as $ip) {
            $address = explode('/', $ip)[0];
            if ($ip !== '*' && ! filter_var($address, FILTER_VALIDATE_IP)) {
                $this->error('عنوان IP غير صالح: '.$ip);

                return self::FAILURE;
            }
            // يتحقق من صحة صيغة CIDR
            IpUtils::checkIp($address, $ip);
        }

        $expires = $this->option('expires');
        if ($expires !== null && (! ctype_digit((string) $expires) || (int) $expires < 1)) {
            $this->error('--expires يجب أن يكون عدد أيام صحيحاً موجباً.');

            return self::FAILURE;
        }

        $account = IntegrationAccount::firstOrNew(['name' => $name]);
        $isNew = ! $account->exists;
        $account->is_active = true;
        if ($this->option('description') !== null) {
            $account->description = $this->option('description');
        }
        if ($ips) {
            $account->allowed_ips = $ips;
        }
        $account->save();

        if ($this->option('revoke')) {
            $revoked = $account->tokens()->delete();
            $this->warn("تم إلغاء {$revoked} توكن سابق.");
        }

        $ability = (string) config('syndicate.api.ability', 'syndicate');
        $token = $account->createToken(
            $name.' @ '.now()->format('Y-m-d H:i'),
            [$ability],
            $expires ? now()->addDays((int) $expires) : null,
        );

        $this->info(($isNew ? 'تم إنشاء حساب التكامل' : 'حساب التكامل موجود').': '.$account->name.' (#'.$account->getKey().')');
        $this->line('القدرة: '.$ability.' — IP المسموح: '.($account->allowed_ips ? implode(', ', $account->allowed_ips) : 'حسب SYNDICATE_API_ALLOWED_IPS'));
        $this->line('الانتهاء: '.($expires ? now()->addDays((int) $expires)->toDateString() : 'بدون'));
        $this->newLine();
        $this->warn('احفظ التوكن الآن؛ لن يظهر مرة أخرى:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
