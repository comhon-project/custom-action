<?php

namespace Comhon\CustomAction\Files;

use Illuminate\Mail\Mailables\Attachment;

class SystemFile implements StoredFile
{
    public function __construct(private string $path)
    {
        //
    }

    public function getAttachmentInstance(): Attachment
    {
        return Attachment::fromPath($this->path);
    }
}
