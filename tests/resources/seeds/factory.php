<?php

use Dcat\Laravel\Database\Tests\Models;
use Faker\Factory as FakerFactory;

function create_posts()
{
    $factory = FakerFactory::create();

    for ($i = 0; $i < 50; $i++) {
        $model = new Models\Post([
            'title'     => $factory->title,
            'body'      => $factory->text(20),
            'author_id' => $i + 1,
        ]);

        $model->save();

        $author = new Models\Author([
            'name' => $factory->name,
        ]);

        $author->save();
    }
}
