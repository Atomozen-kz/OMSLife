<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class TrainingExpireBoolFilter extends Filter
{
    /**
     * Массив параметров, по которым фильтруем.
     *
     * @var array
     */
    public $parameters = ['expired'];

    /**
     * Возвращает название фильтра.
     *
     * @return string
     */
    public function name(): string
    {
        $expired = $this->request->get('expired');
        return "Просрочен: " . ($expired === '1' ? 'Да' : ($expired === '0' ? 'Нет' : ''));
    }

    /**
     * Применяет фильтр к запросу.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        $expired = $this->request->get('expired');
        if ($expired === '1') { // выбираем просроченные сертификаты
            return $builder->where('validity_date', '<', now());
        } elseif ($expired === '0') { // выбираем действующие сертификаты
            return $builder->where('validity_date', '>=', now());
        }
        return $builder;
    }

    /**
     * Поля для отображения фильтра.
     *
     * @return array
     */
    public function display(): array
    {
        return [
            Select::make('expired')
                ->empty('Выберите статус')
                ->options([
                    '1' => 'Просрочен',
                    '0' => 'Действующий',
                ])
                ->title('Фильтр по сроку действия'),
        ];
    }
}
