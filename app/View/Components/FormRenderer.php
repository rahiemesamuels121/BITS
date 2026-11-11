<?php

namespace App\View\Components;

use App\Services\Forms\FormSchemaService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormRenderer extends Component
{
    public function __construct(public array $schema, public array $settings = [], public string $action = '', public bool $preview = false, public ?int $versionId = null)
    {
        $this->settings = app(FormSchemaService::class)->settings($settings);
    }

    public function render(): View
    {
        return view('components.form-renderer');
    }
}
