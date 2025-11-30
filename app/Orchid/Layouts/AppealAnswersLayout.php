<?php

namespace App\Orchid\Layouts;

use Orchid\Screen\Layout;
use Orchid\Screen\Repository;

class AppealAnswersLayout extends Layout
{
    /**
     * @var string
     */
    protected $template = 'layouts.appeal-answers';

    /**
     * @var string
     */
    protected $data;

    /**
     * AppealAnswersLayout constructor.
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
            'answers' => $repository->get($this->data, [])
        ]);
    }

    /**
     * @param string $data
     *
     * @return AppealAnswersLayout
     */
    public static function make(string $data): self
    {
        return new static($data);
    }
}
