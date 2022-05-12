<?php

namespace Phuclh\Imagekit\Commands;

use Illuminate\Console\Command;

class ImagekitCommand extends Command
{
    public $signature = 'flysystem-imagekit';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
