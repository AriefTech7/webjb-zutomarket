<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand;

class ServeWithUploads extends ServeCommand
{
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();
        array_splice($command, 1, 0, ['-d', 'upload_max_filesize=4M', '-d', 'post_max_size=32M', '-d', 'max_file_uploads=20']);

        return $command;
    }
}
