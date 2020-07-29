<?php

declare(strict_types=1);

namespace Vdlp\EloquentModelCloner;

use Illuminate\Database\Eloquent\Model;

trait Cloneable
{
    /**
     * Return the list of attributes on this model that should be cloned
     */
    public function getCloneExemptAttributes(): array
    {
        // Always make the id and timestamps exempt
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        // Include the model count columns in the exempt columns
        $countColumns = array_map(static function ($countColumn) {
            return $countColumn . '_count';
        }, $this->withCount);

        $defaults = array_merge($defaults, $countColumns);

        // It none specified, just return the defaults, else, merge them
        if (!isset($this->cloneExemptAttributes)) {
            return $defaults;
        }

        return array_merge($defaults, $this->cloneExemptAttributes);
    }

    /**
     * Return the list of relations on this model that should be cloned
     */
    public function getCloneableRelations(): array
    {
        if (!isset($this->cloneableRelations)) {
            return [];
        }

        return $this->cloneableRelations;
    }

    /**
     * Add a relation to cloneableRelations uniquely
     */
    public function addCloneableRelation(string $relation): void
    {
        $relations = $this->getCloneableRelations();
        if (in_array($relation, $relations, true)) {
            return;
        }

        $relations[] = $relation;

        $this->cloneableRelations = $relations;
    }

    /**
     * A no-op callback that gets fired when a model is cloning but before it gets
     * committed to the database
     */
    public function onCloning(Model $src, bool $child): void
    {
    }

    /**
     * A no-op callback that gets fired when a model is cloned and saved to the
     * database
     */
    public function onCloned(Model $src): void
    {
    }
}
