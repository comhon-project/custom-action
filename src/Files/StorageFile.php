<?php

namespace Comhon\CustomAction\Files;

use Illuminate\Mail\Mailables\Attachment;

class StorageFile implements StoredFileInterface
{
    public function __construct(private string $path, private ?string $disk = null)
    {
        //
    }

    public function toMailAttachment(): Attachment
    {
        return Attachment::fromStorageDisk($this->disk, $this->path);
    }
}
