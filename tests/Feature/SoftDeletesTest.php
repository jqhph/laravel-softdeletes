<?php

namespace Dcat\Laravel\Database\Tests\Feature;

use Dcat\Laravel\Database\Tests\Models\Post;
use Dcat\Laravel\Database\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

class SoftDeletesTest extends TestCase
{
    public function testDefault()
    {
        // sql
        $sql = Post::query()->where('author_id', '>', 0)->sql();

        $this->assertEquals(
            $sql,
            'select * from `posts` where `author_id` > 0'
        );
    }

    public function testOnlyTrashed()
    {
        // sql
        $sql = Post::onlyTrashed()->where('author_id', '>', 0)->sql();

        $this->assertEquals(
            $sql,
            'select * from `posts_trash` where `author_id` > 0'
        );

        $sql = Post::onlyTrashed()->whereHas('author')->sql();

        $this->assertEquals(
            $sql,
            'select * from `posts_trash` where exists (select * from `authors` where `posts_trash`.`author_id` = `authors`.`id`)'
        );
    }

    public function testWithTrashed()
    {
        // 单表查询
        $sql = Post::withTrashed()
            ->where('author_id', '>', 0)
            ->offset(5)
            ->limit(5)
            ->toSql();

        $this->assertEquals(
            $sql,
            '(select * from `posts` where `author_id` > ?) union (select * from `posts_trash` as `posts` where `author_id` > ?) limit 5 offset 5'
        );

        // whereHas 子查询
        $q = Post::withTrashed()
            ->whereHas('author')
            ->where('author_id', '>', 0)
            ->offset(5)
            ->limit(5);
        $sql = $q->toSql();

        $this->assertEquals(
            $sql,
            '(select * from `posts` where exists (select * from `authors` where `posts`.`author_id` = `authors`.`id`) and `author_id` > ?) union (select * from `posts_trash` as `posts` where exists (select * from `authors` where `posts`.`author_id` = `authors`.`id`) and `author_id` > ?) limit 5 offset 5'
        );

        $models = $q->get();

        $this->assertEquals($models->count(), 5);

        // paginate
        $paginator = Post::withTrashed()
            ->whereHas('author')
            ->where('author_id', '>', 0)
            ->paginate(5);

        $this->assertEquals($paginator->getCollection()->count(), 5);
        $this->assertEquals($paginator->total(), 50); // 总数50
    }

    public function testDelete()
    {
        $count = 5;

        $total = Post::count();

        $deleted = Post::query()->limit($count)->get()->each->delete();

        // 删除成功后表名应该会发生响应变化
        $deleted->each(function (Model $model) {
            $this->assertEquals($model->getTable(), $model->getTrashedTable());
            $this->assertEquals($model->canDelete, true);
        });

        $this->assertEquals(Post::query()->whereIn('id', $deleted->pluck('id'))->count(), 0);
        $this->assertEquals(Post::onlyTrashed()->whereIn('id', $deleted->pluck('id'))->count(), $count);
        $this->assertEquals(Post::count(), $total - $count);

        // 强制删除
        $deleted->each(function (Model $model) {
            $model->delete();
        });

        $this->assertEquals(Post::query()->whereIn('id', $deleted->pluck('id'))->count(), 0);
        $this->assertEquals(Post::onlyTrashed()->whereIn('id', $deleted->pluck('id'))->count(), 0);
        $this->assertEquals(Post::count(), $total - $count);
        $this->assertEquals(Post::onlyTrashed()->count(), 0);
    }

    public function testForceDelete()
    {
        $count = 5;

        $total = Post::count();

        // 直接 forceDelete 删除
        $deleted = Post::query()->limit($count)->get()->each->forceDelete();

        $this->assertEquals(Post::query()->whereIn('id', $deleted->pluck('id'))->count(), 0);
        $this->assertEquals(Post::onlyTrashed()->whereIn('id', $deleted->pluck('id'))->count(), 0);
        $this->assertEquals(Post::count(), $total - $count);

        // 先查后删
        Post::query()->limit($count)->get()->each->delete();

        // 强制删除
        Post::onlyTrashed()->get()->each->delete();

        $this->assertEquals(Post::count(), $total - 10);
        $this->assertEquals(Post::onlyTrashed()->count(), 0);
    }

    public function testRestore()
    {
        $count = 5;

        $deleted = Post::query()->limit($count)->get()->each->delete();

        // 判断删除是否成功
        $this->assertEquals(Post::query()->whereIn('id', $deleted->pluck('id'))->count(), 0);
        $this->assertEquals(Post::onlyTrashed()->whereIn('id', $deleted->pluck('id'))->count(), $count);

        $posts = $deleted->each(function (Model $model) {
            $this->assertEquals($model->getTable(), $model->getTrashedTable());
            $this->assertEquals($model->canDelete, true);

            // 还原
            $model->restore();
        });

        // 判断数量
        $this->assertEquals(Post::query()->whereIn('id', $posts->pluck('id'))->count(), $count);
        $this->assertEquals(Post::onlyTrashed()->whereIn('id', $posts->pluck('id'))->count(), 0);

        $posts->each(function (Model $model) {
            $this->assertEquals($model->getTable(), $model->getOriginalTable());
            $this->assertEquals($model->canDelete, false);
        });
    }
}
