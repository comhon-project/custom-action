<?php

namespace Comhon\CustomAction\Files;

use Illuminate\Mail\Mailables\Attachment;

class StorageFile implements StoredFile
{
    public function __construct(private string $path, private ?string $disk = null)
    {
        //
    }

    public function getAttachmentInstance(): Attachment
    {
        return Attachment::fromStorageDisk($this->disk, $this->path);
    }
}
