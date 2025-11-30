<?php

namespace App\Orchid\Filters;

use App\Models\FinancialAssistanceType;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Input;

class FinancialAssistanceRequestFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Фильтры заявок';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return ['filter.status', 'filter.type', 'filter.search'];
    }

    /**
     * Apply to a given Eloquent query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        if ($this->request->filled('filter.status')) {
            $builder->where('status', $this->request->get('filter.status'));
        }

        if ($this->request->filled('filter.type')) {
            $builder->where('id_type', $this->request->get('filter.type'));
        }

        if ($this->request->filled('filter.search')) {
            $search = $this->request->get('filter.search');
            $builder->whereHas('sotrudnik', function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('father_name', 'like', "%{$search}%");
            })->orWhere('id', 'like', "%{$search}%");
        }

        return $builder;
    }

    /**
     * The displayable fields.
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        return [
            Select::make('filter.status')
                ->title('Статус')
                ->empty('Все статусы')
                ->options([
                    1 => 'На рассмотрении',
                    2 => 'Одобрено',
                    3 => 'Отклонено',
                ])
                ->value($this->request->get('filter.status')),

            Select::make('filter.type')
                ->title('Тип материальной помощи')
                ->empty('Все типы')
                ->fromModel(FinancialAssistanceType::class, 'name')
                ->value($this->request->get('filter.type')),

            Input::make('filter.search')
                ->title('Поиск')
                ->placeholder('Введите ФИО или ID заявителя')
                ->value($this->request->get('filter.search')),
        ];
    }
}
