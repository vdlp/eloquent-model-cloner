<?php

declare(strict_types=1);

namespace Vdlp\EloquentModelCloner;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

final class Cloner
{
    private Dispatcher $eventDispatcher;

    public function __construct(Dispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Clone a model instance and all of it's files and relations
     */
    public function duplicate(Model $model, Relation $relation = null): Model
    {
        $clone = $this->cloneModel($model);

        $this->dispatchOnCloningEvent($clone, $model, $relation);

        if ($relation) {
            $relation->save($clone);
        } else {
            $clone->save();
        }

        $clone->save();

        $this->dispatchOnClonedEvent($clone, $model);

        $this->cloneRelations($model, $clone);

        return $clone;
    }
    /**
     * Create duplicate of the model
     */
    protected function cloneModel(Model $model): Model
    {
        $exempt = method_exists($model, 'getCloneExemptAttributes') ? $model->getCloneExemptAttributes() : null;
        return $model->replicate($exempt);
    }

    /**
     * Loop through relations and clone or re-attach them
     */
    protected function cloneRelations(Model $model, Model $clone): void
    {
        if (!method_exists($model, 'getCloneableRelations')) {
            return;
        }

        foreach ($model->getCloneableRelations() as $relationName) {
            $this->duplicateRelation($model, $relationName, $clone);
        }
    }

    /**
     * Duplicate relationships to the clone
     */
    protected function duplicateRelation(Model $model, string $relationName, Model $clone): void
    {
        $relation = $model->$relationName();
        if (is_a($relation, BelongsToMany::class)) {
            $this->duplicatePivotedRelation($relation, $relationName, $clone);
        } else {
            $this->duplicateDirectRelation($relation, $relationName, $clone);
        }
    }

    /**
     * Duplicate a many-to-many style relation where we are just attaching the
     * relation to the dupe
     */
    protected function duplicatePivotedRelation(Relation $relation, string $relationName, Model $clone): void
    {
        // Loop trough current relations and attach to clone
        $relation->get()->each(static function ($foreign) use ($clone, $relationName) {
            $pivot_attributes = array_except($foreign->pivot->getAttributes(), [
                $foreign->pivot->getOtherKey(),
                $foreign->pivot->getForeignKey(),
                $foreign->pivot->getCreatedAtColumn(),
                $foreign->pivot->getUpdatedAtColumn()
            ]);

            $clone->$relationName()->attach($foreign, $pivot_attributes);
        });
    }

    /**
     * Duplicate a one-to-many style relation where the foreign model is ALSO
     * cloned and then associated
     */
    protected function duplicateDirectRelation(Relation $relation, string $relationName, Model $clone): void
    {
        $relation->get()->each(function ($foreign) use ($clone, $relationName) {
            $this->duplicate($foreign, $clone->$relationName());
        });
    }

    protected function dispatchOnCloningEvent(
        Model $clone,
        Model $src,
        Relation $relation = null,
        bool $child = false
    ): void {
        if ($relation) {
            $child = true;
        }

        // Notify listeners via callback or event
        if (method_exists($clone, 'onCloning')) {
            $clone->onCloning($src, $child);
        }

        $this->eventDispatcher->dispatch(new Events\Cloning($clone, $src));
    }

    protected function dispatchOnClonedEvent(Model $clone, Model $src): void
    {
        // Notify listeners via callback or event
        if (method_exists($clone, 'onCloned')) {
            $clone->onCloned($src);
        }

        $this->eventDispatcher->dispatch(new Events\Cloned($clone, $src));
    }
}
