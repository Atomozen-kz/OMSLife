<?php

namespace App\Orchid\Filters;

use App\Models\TrainingType;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class TrainingTypeFilter extends Filter
{
    /**
     * The array of matched parameters.
     *
     * @var array
     */
    public $parameters = ['training_type'];

    /**
     * Apply filter if the request parameters were satisfied.
     *
     * @param Builder $builder
     *
     * @return Builder
     */

    public function name(): string
    {
        $trainingTypeName = TrainingType::find($this->request->get('training_type'))->name_ru ?? 'Не указано';
        return "Тип обучения: {$trainingTypeName}";
    }

    public function run(Builder $builder): Builder
    {
        return $builder->where('id_training_type', $this->request->get('training_type'));
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): array
    {
        return [
            Select::make('training_type')
                ->fromModel(TrainingType::class, 'name_ru', 'id')
                ->empty('Выберите тип обучения')
                ->value($this->request->get('training_type'))
                ->title('Тип обучения'),
        ];
    }
}
