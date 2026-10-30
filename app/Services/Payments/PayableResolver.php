<?php

namespace App\Services\Payments;

use App\Contracts\Payable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Turns the public {type}/{id} pair of route('payments.pay') into a model.
 * Only morph-map aliases whose class implements Payable are accepted, so a
 * URL can never instantiate an arbitrary class.
 */
class PayableResolver
{
    public function classFor(string $type): ?string
    {
        if (! preg_match('/^[a-z0-9_]{1,64}$/', $type)) {
            return null;
        }

        $class = Relation::getMorphedModel($type);

        return $class && is_subclass_of($class, Payable::class) && is_subclass_of($class, Model::class) ? $class : null;
    }

    /** @return (Model&Payable)|null */
    public function find(string $type, string|int $id, bool $lock = false): ?Model
    {
        $class = $this->classFor($type);
        if ($class === null) {
            return null;
        }

        $query = $class::query()->whereKey($id);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
