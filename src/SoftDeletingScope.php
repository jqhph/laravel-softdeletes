<?php

namespace Dcat\Laravel\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SoftDeletingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = ['Restore', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            /* @var Model $model */
            $model = $builder->getModel();

            return $model->transaction(function () use ($builder, $model) {
                if ($model->canDelete || $model->getTable() === $model->getTrashedTable()) {
                    // 回收站强制删除
                    return $builder->toBase()->delete();
                }

                return $this->softDelete($builder);
            });
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return int
     */
    protected function softDelete(Builder $builder)
    {
        /* @var Model $model */
        $model = $builder->getModel();
        $keyName = $model->getKeyName();

        $count = 0;

        $trash = function ($collection) use ($builder, $model, $keyName, &$count) {
            $inserts = $collection->map(function (Model $model) {
                $data = $model->getOriginal();

                $data[$model->getDeletedAtColumn()] = $model->freshTimestampString();

                return $data;
            });

            // 写入回收表
            $builder->from($model->getTrashedTable())->insert($inserts->toArray());

            // 删除原始表数据
            $model->newQuery()->whereIn($keyName, $inserts->pluck($keyName))->toBase()->delete();

            $count += $inserts->count();
        };

        if ($builder->getQuery()->limit || $builder->getQuery()->offset) {
            $trash($builder->get());
        } else {
            if ($model->incrementing) {
                $builder->chunkById(1000, $trash);
            } else {
                $builder->chunk(1000, $trash);
            }
        }

        return $count;
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getDeletedAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedDeletedAtColumn();
        }

        return $builder->getModel()->getDeletedAtColumn();
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $model = $builder->getModel();
            $result = null;

            $originalBuilder = clone $builder;

            $collections = $builder->get();

            $model->transaction(function () use ($collections, $originalBuilder, $builder, $model, &$result) {
                $collections->transform(function (Model $model) {
                    $data = $model->getOriginal();

                    unset($data[$model->getDeletedAtColumn()]);

                    return $data;
                });

                $builder->from($model->getOriginalTable())->insert($collections->toArray());

                $result = $originalBuilder->toBase()->delete();
            });

            return $result;
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithTrashed(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder, $withTrashed = true) {
            if (! $withTrashed) {
                return $builder->withoutTrashed();
            }

            $builder->withGlobalScope('withTrashed', new TrashedScope());

            return $builder;
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithoutTrashed(Builder $builder)
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $model->withoutTrashedTable();

            return $builder->from($model->getTable());
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $model->withTrashedTable();

            return $builder->withoutGlobalScope($this)->from($model->getTrashedTable());
        });
    }
}
