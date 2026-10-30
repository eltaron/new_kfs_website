<?php

namespace App\Filament\Resources\FamousPersonResource\Pages;

use App\Filament\Resources\FamousPersonResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFamousPerson extends CreateRecord
{
    protected static string $resource = FamousPersonResource::class;
}
