<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;

class SotrudnikiFioFilter extends Filter
{
    public $parameters = ['fio'];
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Поиск по ФИО, табельному номеру или ИИН';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [];
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
        $searchTerm = $this->request->get('fio');

        if (!$searchTerm) {
            return $builder;
        }

        // Очищаем поисковый запрос и разбиваем на слова
        $searchTerms = array_filter(preg_split('/\s+/', trim($searchTerm)));

        return $builder->where(function ($query) use ($searchTerms, $searchTerm) {
            // Поиск по полному значению (для табельного номера и ИИН)
            $query->where('tabel_nomer', 'like', "%{$searchTerm}%")
                ->orWhere('iin', 'like', "%{$searchTerm}%");

            // Если есть слова для поиска по ФИО
            if (!empty($searchTerms)) {
                $query->orWhere(function ($subQuery) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $subQuery->where(function ($nameQuery) use ($term) {
                            $nameQuery->where('last_name', 'like', "%{$term}%")
                                ->orWhere('first_name', 'like', "%{$term}%")
                                ->orWhere('father_name', 'like', "%{$term}%");
                        });
                    }
                });
            }
        });
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        return [
            Input::make('fio')
                ->title('Поиск по ФИО, табельному номеру или ИИН')
                ->value($this->request->get('fio'))
                ->placeholder('Введите ФИО, табельный номер или ИИН'),
        ];
    }
}
