# Laravel QueryBuilder for jQuery Query Builder
## [jQuery Query Builder](http://querybuilder.js.org/)

this package is to build query form jQuery Query Builder library,
     it works with laravel models, and it uses the model relations.
     Also it hide your table structure, so you dont need to name the filters as your table
     columns

## Structure


## Install

Via Composer

``` bash
$ composer require hassanalisalem/querybuilder
```

## Usage

in your controller or whatever place you are building your query
``` php
use hassanalisalem\querybuilder\Query;
...
...

$model = new User();
$rules = $request->rules; // rules from the jQuery query builder form.
$query = Query::build($model, $rules);
// you can use it as: $query->get() to get the query result
// or to check the query string: $query->toSql()

```

## in the model
you should define a public array variable named filterable.
This should contain all your filters as filter id or key (from the jquery query builder filters) =>
filter value.

incase of ambiguous key you can add "as". so if I have table named industries and I want to query
whereIn('id', [])...
so the key should be 'filter_name_industries' => 'industries.id as industries'

``` php
public $filterable = [
    'filter_name' => 'this.name',
    'filter_user_post_title' => 'posts.title',
    'filter_user_post_comment' => 'posts.comments.text',
    'filter_industry' => 'industries.id as industries', // if there is ambiguous key.
    // it is recommended to prepend the key with 'filter_'
];
```
this means that the name source is the model itself.
posts means that the title is from another relation named posts (posts) should be a function in the
model that returns a relation..
also comments is a function in Post that returns a relation..

