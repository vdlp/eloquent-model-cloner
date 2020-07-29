<?php

declare(strict_types=1);

namespace Vdlp\EloquentModelCloner\Events;

use Illuminate\Database\Eloquent\Model;

final class Cloning
{
    private Model $clone;
    private Model $src;

    public function __construct(Model $clone, Model $src)
    {
        $this->clone = $clone;
        $this->src = $src;
    }

    public function getClone(): Model
    {
        return $this->clone;
    }

    public function getSrc(): Model
    {
        return $this->src;
    }
}
