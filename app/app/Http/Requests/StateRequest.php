<?php

namespace App\Http\Requests;

class StateRequest extends OwnerRequest
{
    public function rules(): array
    {
        return ['action' => ['required', 'in:publish,hide,archive,restore'], 'version' => ['required', 'integer', 'min:1'], 'confirm' => ['exclude_unless:action,archive', 'required', 'accepted']];
    }
}
