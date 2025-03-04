<?php

namespace Comhon\CustomAction\Files;

use Illuminate\Mail\Mailables\Attachment;

class SystemFile implements StoredFileInterface
{
    public function __construct(private string $path)
    {
        //
    }

    public function toMailAttachment(): Attachment
    {
        return Attachment::fromPath($this->path);
    }
}
