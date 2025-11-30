<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class PromzonaNamesFilter extends Filter
{
    public $parameters = ['name'];

    public function name(): string
    {
        return 'Название';
    }

    public function run(Builder $builder): Builder
    {
        $name = $this->request->get('name');

        if ($name) {
            return $builder->where('name', 'like', "%{$name}%");
        }

        return $builder;
    }

    public function display(): iterable
    {
        return [
            Input::make('name')
                ->title('Название')
                ->value($this->request->get('name'))
                ->placeholder('Введите название'),
        ];
    }
}
