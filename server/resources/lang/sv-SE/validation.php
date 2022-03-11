<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute måste accepteras.',
    'active_url' => ':attribute är inte en giltig URL.',
    'after' => ':attribute måste vara ett datum efter :date.',
    'after_or_equal' => ':attribute måste vara ett datum som är lika med eller senare än :date.',
    'alpha' => ':attribute får bara innehålla av bokstäver.',
    'alpha_dash' => ':attribute får bara innehålla bokstäver, siffror, streck och understreck.',
    'alpha_num' => ':attribute får bara innehålla bokstäver och siffror.',
    'array' => ':attribute måste vara en array.',
    'before' => ':attribute måste vara ett datum före :date.',
    'before_or_equal' => ':attribute måste vara ett datum som är lika med eller tidigare än :date.',
    'between' => [
        'numeric' => ':attribute måste vara mellan :min och :max.',
        'file' => ':attribute måste vara mellan :min och :max kilobyte.',
        'string' => ':attribute måste vara mellan :min och :max tecken.',
        'array' => ':attribute måste ha mellan :min och :max objekt.',
    ],
    'boolean' => ':attribute måste vara true eller false.',
    'confirmed' => ':attribute upprepningen matchar inte.',
    'date' => ':attribute är inte ett giltigt datum.',
    'date_equals' => ':attribute måste vara ett datum som är lika med :date.',
    'date_format' => ':attribute matchar inte formatet :format.',
    'different' => ':attribute och :other får inte vara identiska.',
    'digits' => ':attribute måste vara :digits siffror.',
    'digits_between' => ':attribute måste vara mellan :min och :max siffror.',
    'dimensions' => ':attribute har ogiltiga bilddimensioner.',
    'distinct' => ':attribute innehåller ett duplicerat värde.',
    'email' => ':attribute måste vara en giltig e-postadress.',
    'ends_with' => ':attribute måste avslutas med ett av följande :values.',
    'exists' => 'Den valda :attribute är ogiltig.',
    'file' => ':attribute måste vara en fil.',
    'filled' => ':attribute måste ha ett värde.',
    'gt' => [
        'numeric' => ':attribute måste vara större än :value.',
        'file' => ':attribute måste vara större än :value kilobyte.',
        'string' => ':attribute måste vara längre än :value tecken.',
        'array' => ':attribute måste ha fler än :value objekt.',
    ],
    'gte' => [
        'numeric' => ':attribute måste vara större än eller lika med :value.',
        'file' => ':attribute måste vara större än eller lika med :value kilobyte.',
        'string' => ':attribute måste vara längre än eller lika med :value tecken.',
        'array' => ':attribute måste ha :value objekt eller fler.',
    ],
    'image' => ':attribute måste vara en bild.',
    'in' => ':attribute är ogiltig/ogiltigt.',
    'in_array' => ':attribute finns inte i :other.',
    'integer' => ':attribute måste vara ett heltal.',
    'ip' => ':attribute måste vara en giltig IP-adress.',
    'ipv4' => ':attribute måste vara en giltig IPv4-adress.',
    'ipv6' => ':attribute måste vara en giltig IPv6-adress.',
    'json' => ':attribute måste vara en giltig JSON-sträng.',
    'lt' => [
        'numeric' => ':attribute måste vara mindre än :value.',
        'file' => ':attribute måste vara mindre än :value kilobyte.',
        'string' => ':attribute måste vara kortare än :value tecken.',
        'array' => ':attribute måste ha färre än :value objekt.',
    ],
    'lte' => [
        'numeric' => ':attribute måste vara mindre än eller lika med :value.',
        'file' => ':attribute måste vara mindre än eller lika med :value kilobyte.',
        'string' => ':attribute måste vara kortare än eller lika med :value tecken.',
        'array' => ':attribute får inte ha fler än :value objekt.',
    ],
    'max' => [
        'numeric' => ':attribute får inte vara större än :max.',
        'file' => ':attribute får inte vara större än :max kilobyte.',
        'string' => ':attribute får inte vara längre än :max tecken.',
        'array' => ':attribute får inte ha fler än :max objekt.',
    ],
    'mimes' => ':attribute måste vara en fil av typ: :values.',
    'mimetypes' => ':attribute måste vara en fil av typ: :values.',
    'min' => [
        'numeric' => ':attribute måste vara minst :min.',
        'file' => ':attribute måste vara minst :min kilobyte.',
        'string' => ':attribute måste innehålla minst :min tecken.',
        'array' => ':attribute måste ha minst :min objekt.',
    ],
    'not_in' => ':attribute är ogiltig/ogiltigt.',
    'not_regex' => ':attribute formatet är ogiltigt.',
    'numeric' => ':attribute måste vara numeriskt.',
    'password' => 'Lösenordet är ogiltigt.',
    'present' => ':attribute måste finnas.',
    'regex' => ':attribute formatet är ogiltigt.',
    'required' => ':attribute är obligatoriskt.',
    'required_if' => ':attribute är obligatoriskt när :other är :value.',
    'required_unless' => ':attribute är obligatoriskt om inte :other finns i :values.',
    'required_with' => ':attribute är obligatoriskt när :values finns.',
    'required_with_all' => ':attribute är obligatoriskt när :values finns.',
    'required_without' => ':attribute är obligatoriskt när :values inte finns.',
    'required_without_all' => ':attribute är obligatoriskt när inget av :values finns.',
    'same' => ':attribute och :other måste matcha.',
    'size' => [
        'numeric' => ':attribute måste vara :size.',
        'file' => ':attribute måste vara :size kilobyte.',
        'string' => ':attribute måste vara :size tecken.',
        'array' => ':attribute måste innehålla :size objekt.',
    ],
    'starts_with' => ':attribute måste börja med ett av följande värden: :values.',
    'string' => ':attribute måste vara en sträng.',
    'timezone' => ':attribute måste vara en giltig zon.',
    'unique' => ':attribute har redan använts.',
    'uploaded' => ':attribute gick inte att ladda upp.',
    'url' => ':attribute är ogiltigt.',
    'uuid' => ':attribute måste vara ett giltigt UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
