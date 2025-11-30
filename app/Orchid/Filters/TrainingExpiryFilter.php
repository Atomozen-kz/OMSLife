<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class TrainingExpiryFilter extends Filter
{
    /**
     * The array of matched parameters.
     *
     * @var array
     */

    public function name(): string
    {
        $days = $this->request->get('days_to_expiry');
        return "Осталось дней до окончания: {$days}";
    }


    public $parameters = ['days_to_expiry'];

    /**
     * Apply filter if the request parameters were satisfied.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        $days = (int) $this->request->get('days_to_expiry');

        if (is_numeric($days)) {
            $targetDate = now()->addDays($days)->format('Y-m-d');
            return $builder->where('validity_date', '<=', $targetDate)->where('validity_date', '>=', date('Y-m-d'));
        }

        return $builder;
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): array
    {
        return [
            Input::make('days_to_expiry')
                ->type('number')
                ->value($this->request->get('days_to_expiry'))
                ->placeholder('Введите количество дней')
                ->title('Дней до окончания'),
        ];
    }
}
