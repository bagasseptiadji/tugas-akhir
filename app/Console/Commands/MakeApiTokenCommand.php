<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('api:make-token {email=esp32@example.com : Email user pemilik token} {--name=esp32-aquarium-token : Nama token Sanctum}')]
#[Description('Membuat token Sanctum untuk ESP32.')]
class MakeApiTokenCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = (string) $this->argument('email');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'ESP32 API Client',
                'password' => Hash::make(str()->password(32)),
            ]
        );

        $token = $user->createToken((string) $this->option('name'), ['sensor:create', 'readings:view'])->plainTextToken;

        $this->info('Token Sanctum berhasil dibuat. Simpan token ini untuk header Authorization ESP32:');
        $this->line($token);

        return self::SUCCESS;
    }
}
