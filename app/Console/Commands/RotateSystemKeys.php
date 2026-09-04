<?php

namespace App\Console\Commands;

use App\Services\KeyManager;
use Illuminate\Console\Command;

class RotateSystemKeys extends Command
{
    protected $signature = 'keys:rotate {--rsa : Rotate RSA only} {--ecc : Rotate ECC only}';

    protected $description = 'Rotate system RSA and/or ECC keys (keeps old versions for decrypt)';

    public function handle(KeyManager $keys): int
    {
        $keys->ensureSystemKeys();
        $doRsa = $this->option('rsa') || (!$this->option('rsa') && !$this->option('ecc'));
        $doEcc = $this->option('ecc') || (!$this->option('rsa') && !$this->option('ecc'));

        if ($doRsa) {
            $this->info('Rotating RSA (prime generation may take a minute)...');
            $v = $keys->rotateRsa();
            $this->info("RSA now at version {$v}");
        }
        if ($doEcc) {
            $this->info('Rotating ECC...');
            $v = $keys->rotateEcc();
            $this->info("ECC now at version {$v}");
        }

        return self::SUCCESS;
    }
}
