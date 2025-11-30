<?php
namespace App\Orchid\Filters;

use App\Models\PromzonaType;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class PromzonaTypesFilter extends Filter
{
    public $parameters = ['promzonaType'];

    public function name(): string
    {
        return 'Тип';
    }

    public function parameters(): ?array
    {
        return ['promzonaType'];
    }

    public function run(Builder $builder): Builder
    {
        $typeId = $this->request->get('promzonaType');
        if ($typeId) {
            return $builder->where('id_type', $typeId);
        }
        return $builder;
    }

    public function display(): iterable
    {
        return [
            Select::make('promzonaType')
                ->fromQuery(PromzonaType::query(), 'name_ru')
                ->empty('Выберите тип')
                ->value($this->request->get('promzonaType'))
                ->title('Тип'),
        ];
    }
}
