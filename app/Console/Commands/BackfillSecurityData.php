<?php

namespace App\Console\Commands;

use App\Models\ExchangeRequest;
use App\Models\Item;
use App\Models\Notification;
use App\Models\User;
use App\Services\ItemEcc;
use App\Services\KeyManager;
use App\Services\MacService;
use App\Services\ExchangeRequestSecurity;
use App\Services\ItemSecurity;
use App\Services\ProfileSecurity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class BackfillSecurityData extends Command
{
    protected $signature = 'security:backfill';

    protected $description = 'Encrypt existing plaintext users/items/notifications with scratch RSA/ECC and generate MACs/keys';

    public function handle(
        KeyManager $keys,
        ProfileSecurity $profiles,
        ItemSecurity $items,
        ExchangeRequestSecurity $requests,
        ItemEcc $ecc,
        MacService $mac,
        Google2FA $google2fa,
    ): int {
        $keys->ensureSystemKeys();

        $this->info('Backfilling users...');
        User::query()->orderBy('id')->each(function (User $user) use ($profiles, $keys, $google2fa) {
            if ($user->email_hash && $user->mac && $user->rsa_public_key && str_starts_with((string) $user->name, 'rsa_v')) {
                return;
            }

            $encrypted = $profiles->encryptProfile([
                'name' => $this->maybeDecryptLegacy($user->name),
                'email' => $this->maybeDecryptLegacy($user->email),
                'phone' => $this->maybeDecryptLegacy($user->phone ?? ''),
                'address' => $this->maybeDecryptLegacy($user->address ?? ''),
                'nid_no' => $this->maybeDecryptLegacy($user->nid_no ?? ''),
            ]);

            $userKeys = $keys->generateUserKeys((int) $user->id);

            $user->fill([
                'name' => $encrypted['name'],
                'email' => $encrypted['email'],
                'phone' => $encrypted['phone'],
                'address' => $encrypted['address'],
                'nid_no' => $encrypted['nid_no'],
                'email_hash' => $encrypted['email_hash'],
                'mac' => $encrypted['mac'],
                'rsa_public_key' => $userKeys['rsa_public_key'],
                'ecc_public_key' => $userKeys['ecc_public_key'],
                'google2fa_secret' => $user->google2fa_secret ?: $profiles->encryptTotpSecret($google2fa->generateSecretKey()),
            ]);
            $user->save();
        });

        $this->info('Backfilling items...');
        Item::query()->orderBy('id')->each(function (Item $item) use ($items) {
            if ($item->mac && str_starts_with((string) $item->title, 'ecc_v')) {
                return;
            }

            $fields = $items->encryptFields([
                'title' => $this->maybeDecryptLegacy($item->title),
                'description' => $this->maybeDecryptLegacy($item->description),
                'preferred_product' => $this->maybeDecryptLegacy($item->preferred_product),
            ]);

            $meta = $items->encryptImageMeta([
                'original_name' => basename((string) $item->photo),
                'path' => $item->photo,
                'uploaded_at' => optional($item->created_at)?->toIso8601String(),
            ]);

            $item->fill(array_merge($fields, $meta));
            $item->save();
        });

        $this->info('Backfilling exchange requests...');
        ExchangeRequest::query()->orderBy('id')->each(function (ExchangeRequest $er) use ($requests) {
            if ($er->encrypted_details && $er->mac && str_starts_with((string) $er->encrypted_details, 'ecc_v')) {
                return;
            }

            $details = $requests->encryptDetails([
                'item_id' => $er->item_id,
                'requested_by' => $er->requested_by,
                'offered_item_id' => $er->offered_item_id,
                'created_at' => optional($er->created_at)?->toIso8601String(),
            ]);

            $er->encrypted_details = $details;
            $er->mac = $requests->generateMac($details, $er->status ?? 'pending', $er->completion_payload);
            $er->save();
        });

        $this->info('Backfilling notifications...');
        Notification::query()->orderBy('id')->each(function (Notification $n) use ($keys, $ecc, $mac) {
            if ($n->mac && str_starts_with((string) $n->message, 'ecc_v')) {
                return;
            }

            $plain = $this->maybeDecryptLegacy((string) $n->message);
            $encrypted = $ecc->encrypt($plain, $keys->eccPublic());
            $n->message = $encrypted;
            $n->mac = $mac->generate($encrypted);
            $n->save();
        });

        $this->info('Security backfill complete. For hybrid→scratch migration use security:reencrypt-scratch.');

        return self::SUCCESS;
    }

    private function maybeDecryptLegacy(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (Str::startsWith($value, 'eyJpdiI6')) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }
}
