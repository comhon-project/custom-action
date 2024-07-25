<?php

namespace Tests\Unit;

use Comhon\CustomAction\Files\StorageFile;
use Comhon\CustomAction\Files\SystemFile;
use Illuminate\Mail\Mailables\Attachment;
use Tests\TestCase;

class StoredFileTest extends TestCase
{
    public function testGetSystemFileAttachment()
    {
        $systemFile = new SystemFile('foo.pdf');
        $this->assertInstanceOf(Attachment::class, $systemFile->getAttachmentInstance());
    }

    public function testGetStorageFileAttachment()
    {
        $systemFile = new StorageFile('foo.pdf');
        $this->assertInstanceOf(Attachment::class, $systemFile->getAttachmentInstance());
    }
}
