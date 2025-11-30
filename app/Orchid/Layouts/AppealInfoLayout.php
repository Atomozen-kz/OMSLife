<?php

namespace App\Orchid\Layouts;

use Orchid\Screen\Layout;
use Orchid\Screen\Repository;

class AppealInfoLayout extends Layout
{
    /**
     * @var string
     */
    protected $template = 'layouts.appeal-info';

    /**
     * @var string
     */
    protected $data;

    /**
     * AppealInfoLayout constructor.
     *
     * @param string $data
     */
    public function __construct(string $data)
    {
        $this->data = $data;
    }

    /**
     * @param Repository $repository
     *
     * @return mixed
     */
    public function build(Repository $repository)
    {
        return view($this->template, [
            'appeal' => $repository->get($this->data)
        ]);
    }

    /**
     * @param string $data
     *
     * @return AppealInfoLayout
     */
    public static function make(string $data): self
    {
        return new static($data);
    }
}
