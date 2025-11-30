<?php

namespace App\Orchid\Layouts;

use App\Orchid\Filters\PromzonaNamesFilter;
use App\Orchid\Filters\PromzonaTypesFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class PromzonaSelection extends Selection
{
    public $template = self::TEMPLATE_LINE;

    /**
     * @return Filter[]
     */
    public function filters(): iterable
    {
        return [
            PromzonaNamesFilter::class,
            PromzonaTypesFilter::class,
        ];
    }
}
