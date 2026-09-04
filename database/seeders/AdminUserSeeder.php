<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\KeyManager;
use App\Services\ProfileSecurity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $keys = app(KeyManager::class);
        $profiles = app(ProfileSecurity::class);
        $google2fa = app(Google2FA::class);

        $keys->ensureSystemKeys();

        $email = 'admin@gmail.com';
        $hash = ProfileSecurity::emailHash($email);
        $secret = $google2fa->generateSecretKey();

        $user = User::where('email_hash', $hash)->first();

        if ($user) {
            $user->password = Hash::make('admin123');
            $user->google2fa_secret = $profiles->encryptTotpSecret($secret);
            if (!$user->rsa_public_key) {
                $userKeys = $keys->generateUserKeys((int) $user->id);
                $user->rsa_public_key = $userKeys['rsa_public_key'];
                $user->ecc_public_key = $userKeys['ecc_public_key'];
            }
            $user->save();
            $this->command?->info('Admin updated: admin@gmail.com / admin123');
        } else {
            $encrypted = $profiles->encryptProfile([
                'name' => 'Administrator',
                'email' => $email,
                'phone' => '0000000000',
                'address' => 'Admin Office',
                'nid_no' => 'ADMIN',
            ]);

            $user = User::create([
                'name' => $encrypted['name'],
                'email' => $encrypted['email'],
                'email_hash' => $encrypted['email_hash'],
                'phone' => $encrypted['phone'],
                'address' => $encrypted['address'],
                'nid_no' => $encrypted['nid_no'],
                'mac' => $encrypted['mac'],
                'password' => Hash::make('admin123'),
                'google2fa_secret' => $profiles->encryptTotpSecret($secret),
            ]);

            $userKeys = $keys->generateUserKeys((int) $user->id);
            $user->rsa_public_key = $userKeys['rsa_public_key'];
            $user->ecc_public_key = $userKeys['ecc_public_key'];
            $user->save();

            $this->command?->info('Admin created: admin@gmail.com / admin123');
        }

        $this->command?->info('2FA secret: ' . $secret);
        $this->command?->info('otpauth: ' . $google2fa->getQRCodeUrl('ExchangeIT', $email, $secret));
    }
}
