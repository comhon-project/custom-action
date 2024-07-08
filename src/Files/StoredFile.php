<?php

namespace Comhon\CustomAction\Files;

use Illuminate\Mail\Mailables\Attachment;

interface StoredFile
{
    public function getAttachmentInstance(): Attachment;
}
