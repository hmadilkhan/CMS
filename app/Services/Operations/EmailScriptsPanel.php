<?php

namespace App\Services\Operations;

use App\Models\Department;
use App\Models\EmailScript;
use App\Models\EmailType;
use App\Services\NotificationTemplateService;
use Illuminate\Http\Request;

class EmailScriptsPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.email-scripts.panel';
    }

    public function data(Request $request): array
    {
        $templates = app(NotificationTemplateService::class)->all();
        $editingTemplate = $templates[$request->template] ?? null;

        return [
            'emailTypes' => EmailType::all(),
            'departments' => Department::all(),
            'emailScripts' => EmailScript::with('email', 'department')->get(),
            'script' => $request->id != '' ? EmailScript::with('email', 'department')->where('id', $request->id)->first() : [],
            'notificationTemplates' => $templates,
            'editingTemplate' => $editingTemplate,
            // Land on the tab the request is about: editing a template, or
            // coming back from saving one.
            'activeTab' => ($editingTemplate || $request->tab === 'templates') ? 'templates' : 'scripts',
        ];
    }
}
