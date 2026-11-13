<?php

namespace Tests\Unit;

use App\Models\FormSubmission;
use App\Services\Forms\SubmissionAttachmentService;
use PHPUnit\Framework\TestCase;

class SubmissionAttachmentTest extends TestCase
{
    public function test_only_paths_inside_the_submissions_own_directory_are_accepted(): void
    {
        $submission = new FormSubmission;
        $submission->form_id = 7;
        $service = new SubmissionAttachmentService;

        $this->assertSame('forms/7/proof.pdf', $service->path($submission, ['kind' => 'file', 'path' => 'forms/7/proof.pdf']));
        foreach (['forms/8/proof.pdf', 'forms/70/proof.pdf', 'forms/7/../../secret', 'forms/7/..\\secret', "forms/7/a\0.pdf", '/forms/7/proof.pdf'] as $path) {
            $this->assertNull($service->path($submission, ['kind' => 'file', 'path' => $path]));
        }
        foreach ([null, 'proof.pdf', ['kind' => 'file'], ['kind' => 'text', 'path' => 'forms/7/proof.pdf'], ['kind' => 'file', 'path' => ['invalid']]] as $file) {
            $this->assertNull($service->path($submission, $file));
        }
    }
}
