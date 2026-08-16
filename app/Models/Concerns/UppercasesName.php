<?php

namespace App\Models\Concerns;

trait UppercasesName
{
    protected static function bootUppercasesName(): void
    {
        static::saving(function ($model) {
            foreach ($model->uppercaseNameFields() as $field) {
                if (! empty($model->{$field})) {
                    $model->{$field} = mb_strtoupper(trim($model->{$field}), 'UTF-8');
                }
            }
        });
    }

    protected function uppercaseNameFields(): array
    {
        return ['name'];
    }
}
