<?php

namespace App\Console\Commands;

use App\Services\KeyManager;
use Illuminate\Console\Command;

class GenerateSystemKeys extends Command
{
    protected $signature = 'keys:generate-system';

    protected $description = 'Generate master + system RSA/ECC key pairs (from-scratch)';

    public function handle(KeyManager $keys): int
    {
        $this->info('Generating keys (RSA prime generation may take a minute)...');
        $keys->ensureSystemKeys();
        $this->info('System RSA/ECC keys ready in storage/app/keys.');

        return self::SUCCESS;
    }
}
