<?php

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'between' => [
        'numeric' => 'La valeur de :attribute doit être comprise entre :min et :max.',
        'string' => 'Le texte :attribute doit contenir entre :min et :max caractères.',
    ],
    'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'exists' => 'Le champ :attribute sélectionné est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'image' => 'Le champ :attribute doit être une image.',
    'max' => [
        'numeric' => 'La valeur de :attribute ne doit pas dépasser :max.',
        'file' => 'Le fichier :attribute ne doit pas dépasser :max kilo-octets.',
        'string' => 'Le texte :attribute ne doit pas contenir plus de :max caractères.',
    ],
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'min' => [
        'numeric' => 'La valeur de :attribute doit être au moins :min.',
        'string' => 'Le texte :attribute doit contenir au moins :min caractères.',
    ],
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'La valeur de :attribute est déjà utilisée.',
    'uploaded' => 'Le fichier :attribute n\'a pas pu être téléversé. Vérifiez la taille du fichier.',

    'password' => [
        'letters' => 'Le mot de passe doit contenir au moins une lettre.',
        'mixed' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        'symbols' => 'Le mot de passe doit contenir au moins un caractère spécial.',
        'uncompromised' => 'Ce mot de passe a été divulgué dans une fuite de données. Veuillez en choisir un autre.',
    ],

    'attributes' => [
        'password' => 'mot de passe',
        'email' => 'email',
        'name' => 'nom',
    ],
];
