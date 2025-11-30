<?php

namespace App\Orchid\Layouts;

use App\Orchid\Filters\SotrudnikiFioFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class SotrudnikiSelection extends Selection
{
    public $template = self::TEMPLATE_LINE;
    /**
     * @return Filter[]
     */
    public function filters(): iterable
    {
        return [
            SotrudnikiFioFilter::class,
        ];
    }
}
