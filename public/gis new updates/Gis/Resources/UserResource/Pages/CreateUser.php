<?php

namespace App\Filament\Gis\Resources\UserResource\Pages;

use App\Filament\Gis\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
