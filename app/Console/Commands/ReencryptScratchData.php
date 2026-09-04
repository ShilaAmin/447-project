<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\ExchangeRequest;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use App\Services\ItemEcc;
use App\Services\KeyManager;
use App\Services\LegacyHybridDecryptor;
use App\Services\MacService;
use App\Services\ExchangeRequestSecurity;
use App\Services\ItemSecurity;
use App\Services\NotificationService;
use App\Services\PostSecurity;
use App\Services\ProfileSecurity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class ReencryptScratchData extends Command
{
    protected $signature = 'security:reencrypt-scratch';

    protected $description = 'Re-encrypt all app data with from-scratch RSA/ECC (no AES)';

    public function handle(
        KeyManager $keys,
        ProfileSecurity $profiles,
        ItemSecurity $items,
        ExchangeRequestSecurity $requests,
        NotificationService $notifications,
        PostSecurity $posts,
        LegacyHybridDecryptor $legacy,
        MacService $mac,
        Google2FA $google2fa,
    ): int {
        $this->info('Ensuring scratch system keys...');
        $keys->ensureSystemKeys();

        $this->info('Users...');
        User::query()->orderBy('id')->each(function (User $user) use ($profiles, $keys, $legacy, $google2fa) {
            $name = $this->toPlain($user->name, $legacy, 'rsa');
            $email = $this->toPlain($user->email, $legacy, 'rsa');
            $phone = $this->toPlain((string) ($user->phone ?? ''), $legacy, 'rsa');
            $address = $this->toPlain((string) ($user->address ?? ''), $legacy, 'rsa');
            $nid = $this->toPlain((string) ($user->nid_no ?? ''), $legacy, 'rsa');

            // skip if already scratch rsa and MAC valid
            if (str_starts_with((string) $user->name, 'rsa_v') && $profiles->verifyProfileMac($user)) {
                return;
            }

            $enc = $profiles->encryptProfile([
                'name' => $name,
                'email' => $email !== '' ? $email : 'unknown@example.com',
                'phone' => $phone,
                'address' => $address,
                'nid_no' => $nid,
            ]);

            $user->fill([
                'name' => $enc['name'],
                'email' => $enc['email'],
                'phone' => $enc['phone'],
                'address' => $enc['address'],
                'nid_no' => $enc['nid_no'],
                'email_hash' => $enc['email_hash'],
                'mac' => $enc['mac'],
            ]);

            if (!$user->google2fa_secret) {
                $user->google2fa_secret = $profiles->encryptTotpSecret($google2fa->generateSecretKey());
            }
            if (!$user->rsa_public_key) {
                $uk = $keys->generateUserKeys((int) $user->id);
                $user->rsa_public_key = $uk['rsa_public_key'];
                $user->ecc_public_key = $uk['ecc_public_key'];
            }
            $user->save();
            $this->line("  user #{$user->id} ok");
        });

        $this->info('Items...');
        Item::query()->orderBy('id')->each(function (Item $item) use ($items, $legacy) {
            if (str_starts_with((string) $item->title, 'ecc_v') && $items->verifyItemMac($item)) {
                return;
            }
            $fields = $items->encryptFields([
                'title' => $this->toPlain($item->title, $legacy, 'auto'),
                'description' => $this->toPlain($item->description, $legacy, 'auto'),
                'preferred_product' => $this->toPlain($item->preferred_product, $legacy, 'auto'),
            ]);
            $metaPlain = ['path' => $item->photo, 'migrated' => true];
            if ($item->image_meta) {
                try {
                    $metaPlain = json_decode($this->toPlain($item->image_meta, $legacy, 'ecc'), true) ?: $metaPlain;
                } catch (\Throwable) {
                }
            }
            $meta = $items->encryptImageMeta($metaPlain);
            $item->fill(array_merge($fields, $meta));
            $item->save();
            $this->line("  item #{$item->id} ok");
        });

        $this->info('Exchange requests...');
        ExchangeRequest::query()->orderBy('id')->each(function (ExchangeRequest $er) use ($requests, $legacy) {
            $details = [
                'item_id' => $er->item_id,
                'requested_by' => $er->requested_by,
                'offered_item_id' => $er->offered_item_id,
            ];
            if ($er->encrypted_details) {
                try {
                    if (str_starts_with($er->encrypted_details, 'ecc_v')) {
                        $details = $requests->decryptDetails($er->encrypted_details);
                    } elseif ($legacy->looksLikeLegacyHybrid($er->encrypted_details)) {
                        $details = json_decode($legacy->decryptRsaHybrid($er->encrypted_details), true) ?: $details;
                    }
                } catch (\Throwable) {
                }
            }
            $enc = $requests->encryptDetails($details);
            $completion = null;
            if ($er->completion_payload) {
                try {
                    $plain = str_starts_with($er->completion_payload, 'ecc_v')
                        ? $requests->decryptCompletion($er->completion_payload)
                        : json_decode($this->toPlain($er->completion_payload, $legacy, 'auto'), true);
                    if (is_array($plain)) {
                        $completion = $requests->encryptCompletion($plain);
                    }
                } catch (\Throwable) {
                }
            }
            $er->encrypted_details = $enc;
            $er->completion_payload = $completion;
            $er->mac = $requests->generateMac($enc, $er->status ?? 'pending', $completion);
            $er->save();
        });

        $this->info('Notifications...');
        Notification::query()->orderBy('id')->each(function (Notification $n) use ($legacy, $mac, $keys) {
            if (str_starts_with((string) $n->message, 'ecc_v') && $n->mac) {
                return;
            }
            $plain = $this->toPlain((string) $n->message, $legacy, 'auto');
            $ecc = app(ItemEcc::class);
            $cipher = $ecc->encrypt($plain, $keys->eccPublic());
            $n->message = $cipher;
            $n->mac = $mac->generate($cipher);
            $n->save();
        });

        $this->info('Community posts...');
        Post::query()->orderBy('id')->each(function (Post $post) use ($posts, $legacy) {
            if (str_starts_with((string) $post->title, 'ecc_v') && $post->mac) {
                return;
            }
            $enc = $posts->encryptPost([
                'title' => $this->toPlain((string) $post->title, $legacy, 'auto'),
                'content' => $this->toPlain((string) $post->content, $legacy, 'auto'),
            ]);
            $post->fill($enc);
            $post->save();
        });

        Comment::query()->orderBy('id')->each(function (Comment $c) use ($posts, $legacy) {
            if (str_starts_with((string) $c->content, 'ecc_v') && $c->mac) {
                return;
            }
            $enc = $posts->encryptComment($this->toPlain((string) $c->content, $legacy, 'auto'));
            $c->fill($enc);
            $c->save();
        });

        $this->info('Re-encryption complete.');
        return self::SUCCESS;
    }

    private function toPlain(string $value, LegacyHybridDecryptor $legacy, string $prefer): string
    {
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, 'rsa_v') || str_starts_with($value, 'ecc_v')) {
            // already scratch — try decrypt via services by returning as-is only if caller handles;
            // for migration of already-scratch, caller skips. If we get here, attempt decrypt.
            try {
                if (str_starts_with($value, 'rsa_v')) {
                    return app(ProfileSecurity::class)->decryptField($value);
                }
                return app(ItemEcc::class)
                    ->decrypt($value, app(KeyManager::class)->resolveEccPrivateFromCipher($value));
            } catch (\Throwable) {
                return $value;
            }
        }
        if ($legacy->looksLikeLegacyHybrid($value)) {
            try {
                if ($prefer === 'ecc' || (isset(json_decode(base64_decode($value, true) ?: '', true)['alg']) && str_contains((string) json_decode(base64_decode($value, true) ?: '', true)['alg'], 'ECIES'))) {
                    return $legacy->decryptEccHybrid($value);
                }
                return $legacy->decryptRsaHybrid($value);
            } catch (\Throwable) {
                try {
                    return $legacy->decryptEccHybrid($value);
                } catch (\Throwable) {
                    try {
                        return $legacy->decryptRsaHybrid($value);
                    } catch (\Throwable) {
                        return $value;
                    }
                }
            }
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
