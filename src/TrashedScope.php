<?php

namespace Dcat\Laravel\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TrashedScope implements Scope
{
    /**
     * {@inheritDoc}
     */
    public function apply(Builder $builder, Model $model)
    {
        // 先重置 limit 和 offset
        $query = $builder->getQuery();
        $offset = $query->offset;
        $limit = $query->limit;
        // 置空
        $query->limit = $query->offset = null;

        $trashBuilder = clone $builder;
        $trashBuilder->setQuery(clone $query);
        $trashModel = clone $trashBuilder->getModel();
        $trashBuilder->setModel($trashModel);

        // 重置 软删除表 的表名
        $originalTable = $trashModel->getOriginalTable();
        $trashModel->withTrashedTable();

        return $builder
            ->union(
                $trashBuilder
                    ->withoutGlobalScope('withTrashed')
                    ->from($trashModel->getTable(), $originalTable)
            )
            ->when($offset !== null, function (Builder $builder) use ($offset) {
                $builder->offset($offset);
            })
            ->when($limit !== null, function (Builder $builder) use ($limit) {
                $builder->limit($limit);
            });
    }
}
