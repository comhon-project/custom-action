<?php

namespace Comhon\CustomAction\Files;

use Illuminate\Mail\Mailables\Attachment;

interface StoredFileInterface
{
    public function getAttachmentInstance(): Attachment;
}
