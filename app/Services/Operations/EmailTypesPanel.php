<?php

namespace App\Services\Operations;

use App\Models\EmailType;
use Illuminate\Http\Request;

class EmailTypesPanel implements OperationsPanel
{
    public function view(): string
    {
        return 'operations.email-types.panel';
    }

    public function data(Request $request): array
    {
        return [
            'emailTypes' => EmailType::all(),
            'emailType' => $request->id != '' ? EmailType::where('id', $request->id)->first() : [],
        ];
    }
}
