<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;

class FallbackCategoryResolver
{
    public static function findOrCreate(User $user, string $type): Category
    {
        $name = ($user->locale ?? 'en') === 'pt-BR' ? 'Sem Categoria' : 'No Category';

        return Category::firstOrCreate(
            [
                'user_id'   => $user->id,
                'parent_id' => null,
                'name'      => $name,
                'type'      => $type,
            ]
        );
    }
}
