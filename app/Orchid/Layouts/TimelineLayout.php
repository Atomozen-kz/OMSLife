<?php

namespace App\Orchid\Layouts;

use Orchid\Screen\Layout;
use Orchid\Screen\Repository;

class TimelineLayout extends Layout
{
    /**
     * @var string
     */
    protected $template = 'layouts.timeline';

    /**
     * @var string
     */
    protected $data;

    /**
     * TimelineLayout constructor.
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
            'statusHistory' => $repository->get($this->data, [])
        ]);
    }

    /**
     * @param string $data
     *
     * @return TimelineLayout
     */
    public static function make(string $data): self
    {
        return new static($data);
    }
}
