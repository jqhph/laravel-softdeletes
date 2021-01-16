<?php

namespace Dcat\Laravel\Database;

use Illuminate\Database\Query\Builder as Query;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Str;

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

    //protected function prepareTrashedQuery(Builder $builder)
    //{
    //    $query = $this->replaceTrashedQuery($builder->getQuery(), $builder->getModel());
    //
    //    $builder->setQuery($query);
    //}
    //
    //// 子查询表名替换
    //protected function replaceTrashedQuery(Query $query, Model $trashModel)
    //{
    //    $query = clone $query;
    //
    //    foreach ($query->wheres as $k => &$where) {
    //        if ($where instanceof Query) {
    //            $where = $this->replaceTrashedQuery($where, $trashModel);
    //
    //            continue;
    //        }
    //
    //        if (is_string($where)) {
    //            $where = $this->replaceTrashedTable($where, $trashModel);
    //
    //            continue;
    //        }
    //
    //        if (is_array($where)) {
    //            foreach ($where as &$v) {
    //                if ($v instanceof Query) {
    //                    $v = $this->replaceTrashedQuery($v, $trashModel);
    //
    //                    continue;
    //                }
    //
    //                if (is_string($v)) {
    //                    $v = $this->replaceTrashedTable($v, $trashModel);
    //                }
    //            }
    //        }
    //    }
    //
    //    return $query;
    //}
    //
    //protected function replaceTrashedTable(string $value, Model $trashModel)
    //{
    //    $originalTable = $trashModel->getOriginalTable().'.';
    //    $trashTable = $trashModel->getTable().'.';
    //
    //    if (Str::contains($value, $originalTable)) {
    //        return str_replace($originalTable, $trashTable, $value);
    //    }
    //
    //    return $value;
    //}
}
