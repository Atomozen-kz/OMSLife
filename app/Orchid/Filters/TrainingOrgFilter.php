<?php

namespace App\Orchid\Filters;

use App\Models\OrganizationStructure;
use App\Models\TrainingRecord;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class TrainingOrgFilter extends Filter
{
    /**
     * The array of matched parameters.
     *
     * @var array
     */
    public $parameters = ['organization'];

    /**
     * Apply filter if the request parameters were satisfied.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        $parentOrgId = $this->request->get('organization');
        return $builder->whereHas('sotrudnik.organization', function (Builder $query) use ($parentOrgId) {
            $query->where('parent_id', $parentOrgId)
                ->orWhere('id', $parentOrgId);
        });
    }


    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): array
    {
        return [
            Select::make('organization')
                ->fromQuery(
                    OrganizationStructure::query()->whereNull('parent_id'),
                    'name_ru',
                    'id'
                )
                ->empty('Выберите организацию')
                ->value($this->request->get('organization'))
                ->title('Организация'),
        ];
    }



    /**
     * Get the name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        $organizationName = OrganizationStructure::find($this->request->get('organization'))->name_ru ?? 'Не указано';
        return "Организация: {$organizationName}";
    }
}
