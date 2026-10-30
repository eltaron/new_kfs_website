<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use Illuminate\Support\Facades\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function afterCreate(): void
    {
        // Find all super admins to notify them
        $recipients = User::whereHas('roles', fn($query) => $query->where('name', 'Super Admin'))->get();

        // Get the user that was just created
        $user = $this->record;

        Notification::send($recipients, new NewUserRegistered($user));
    }
}
