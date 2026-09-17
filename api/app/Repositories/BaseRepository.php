<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
abstract class BaseRepository
{
    /**
     * @var class-string<TModel>
     */
    protected string $modelClass;

    /**
     * @return TModel
     */
    public function findOrFail(int $id): Model
    {
        /** @var TModel */
        return $this->modelClass::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        /** @var TModel */
        return $this->modelClass::query()->create($attributes);
    }
}
