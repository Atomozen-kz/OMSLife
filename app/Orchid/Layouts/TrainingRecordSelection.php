<?php

namespace App\Orchid\Layouts;

use App\Models\User;
use App\Orchid\Filters\TrainingExpireBoolFilter;
use App\Orchid\Filters\TrainingOrgFilter;
use App\Orchid\Filters\TrainingTypeFilter;
use App\Orchid\Filters\TrainingExpiryFilter;
use Orchid\Screen\Layouts\Selection;

class TrainingRecordSelection extends Selection
{

    public $template = self::TEMPLATE_LINE;

    /**
     * @return array
     */
    public function filters(): array
    {
        $isAdmin = (new \App\Models\User)->isAdmin();

        $filters = [
            TrainingTypeFilter::class,
            TrainingExpiryFilter::class,
            TrainingExpireBoolFilter::class,
        ];

        if ($isAdmin) {
            $filters[] = TrainingOrgFilter::class;
        } else {
            $user = auth()->user();
            if (!empty($user->signer) && isset($user->signer[0]['organization_id'])) {
                request()->merge(['organization' => $user->signer[0]['organization_id']]);
            }
        }

        return $filters;
    }



}
