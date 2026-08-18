<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['plan_code', 'feature_key', 'limit_value', 'is_enforced'])]
class PlanLimit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_enforced' => 'boolean',
        ];
    }
}
