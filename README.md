<div align="center">

# LARAVEL WHERE HAS IN

<p>
    <a href="https://github.com/jqhph/laravel-softdeletes/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-7389D8.svg?style=flat" ></a>
     <a href="https://github.styleci.io/repos/330098031">
        <img src="https://github.styleci.io/repos/330098031/shield" alt="StyleCI">
    </a>
    <a href="https://github.com/jqhph/laravel-softdeletes/releases" ><img src="https://img.shields.io/github/release/jqhph/laravel-softdeletes.svg?color=4099DE" /></a> 
    <a><img src="https://img.shields.io/badge/php-7+-59a9f8.svg?style=flat" /></a> 
</p>

</div>

`Laravel softdeletes`是一个用于替代`Laravel`内置的软删除`softDelets`功能的扩展包，可以把软删数据存储到独立的数据表中，从而不影响原始表字段增加唯一索引，且可以起到提升性能的作用。


## 环境

- PHP >= 7
- laravel >= 5.5


## 安装

```bash
composer require dcat/laravel-softdeletes
```

### 简介

`Laravel`内置的`softDelets`功能会把软删的数据和正常数据存在同一个数据表中，这样会导致数据表的字段无法直接添加`unique`索引，因为很容易产生冲突，这样会给实际业务开发带来诸多不必要的困扰。
而`Laravel softdeletes`则可以把软删数据存储到独立的数据表中，从而不影响原始表字段增加唯一索引，且可以起到提升性能的作用。


### 使用


首先需要创建两张字段一模一样的数据表，并且两张表都需要添加`deleted_at`字段

```php
// 文章表
Schema::create('posts', function (Blueprint $table) {
	$table->bigIncrements('id');
	$table->string('title')->nullable();
	$table->string('body')->nullable();

	// 两张表都需要删除时间字段
	$table->timestamp('deleted_at')->nullable();

	$table->timestamps();
});

// 文章软删除表
Schema::create('posts_trash', function (Blueprint $table) {
	$table->bigIncrements('id');
	$table->string('title')->nullable();
	$table->string('body')->nullable();

	// 两张表都需要删除时间字段
	$table->timestamp('deleted_at')->nullable();

	$table->timestamps();
});
```

模型定义如下，默认的软删除表表名为：`{原始表}_trash`，如上面的`posts`表的默认软删除表表名是`posts_trash`

```php
<?php

namespace App\Models;

use Dcat\Laravel\Database\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use SoftDeletes;
    
    /**
 	 * 自定义软删除表表名，默认为 {$table}_trash 
 	 * 
	 * @var string 
 	 */
    protected $trashedTable = 'posts_trash';
    
    /**
 	 * 自定义软删除表表名，如有需要可以重写此方法
 	 * 
	 * @return mixed
	 */
    public function getTrashedTable()
    {
        return $this->trashedTable;
	}
}

```

除了`withTrashed`只能用于**查询数据**之外，其他方法的使用与`Laravel`内置的软删除功能完全一致，下面是简单的用法示例

> 需要注意`withTrashed`只能用于**查询数据**，不能用于`update`和`delete`！！！

查询正常表数据
```php
$posts = Post::where(...)->get();
```

仅查询软删除表数据 (onlyTrashed)
```php
$trashedPosts = Post::onlyTrashed()->where(...)->get();
```

同时查询正常和软删数据 (withTrashed)，需要注意`withTrashed`只能用于**查询数据**，不能用于`update`和`delete`！！！

```php
Post::withTrashed()
	->where(...)
	->offset(5)
    ->limit(5)
    ->get();

// 可以使用子查询以及whereHas等
Post::withTrashed()
	->whereHas('...', function ($q) {
	    $q->where(...);
	})
	->offset(5)
    ->limit(5)
    ->get();
    
// 分页
Post::withTrashed()
	->whereHas('...', function ($q) {
		$q->where(...);
	})
	->paginate(10);
```

软删除/硬删除/还原

```php
$post = Post::first();

// 软删除
$post->delete();

// 还原
$post->restore();

// 硬删
$post->forceDelete();
```



## License
[The MIT License (MIT)](LICENSE).
