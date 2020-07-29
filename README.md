# Eloquent model cloner

This package was inspired by https://github.com/BKWLD/cloner and modified to also work with OctoberCMS installations.

A trait for Laravel Eloquent models that lets you clone a model and it's relationships, including files. Even to another database.

## Installation

To get started with the Eloquent Model Cloner, use Composer to add the package to your project's dependencies:

```
composer require vdlp/eloquent-model-cloner
```

## Usage

Your model should now look like this:

```php
class Article extends Eloquent {
   use \Vdlp\EloquentModelCloner\Cloneable;
}
```

You can clone an Article model like so:

```php
$clone = Article::first()->duplicate();
```

### Cloning Relationships

Lets say your `Article` has many `Photos` (a one to many relationship) and can have more than one `Authors` (a many to many relationship). Now, your `Article` model should look like this:

```php
class Article extends Eloquent {
   use \Vdlp\EloquentModelCloner\Cloneable;

   protected $cloneableRelations = ['photos', 'authors'];

   public function photos() {
       return $this->hasMany('Photo');
   }

   public function authors() {
        return $this->belongsToMany('Author');
   }
}
```

The `$cloneableRelations` informs the `Cloneable` as to which relations it should follow when cloning.
Now when you call `Article::first()->duplicate()`, all of the `Photo` rows of the original will be copied and associated with the new `Article`.
And new pivot rows will be created associating the new `Article` with the `Authors` of the original (because it is a many to many relationship, no new `Author` rows are created).
Furthermore, if the `Photo` model has many of some other model, you can specify `$cloneableRelations` in its class and `Cloner` will continue replicating them as well.

### Customizing the cloned attributes

By default, `Cloner` does not copy the `id` (or whatever you've defined as the `key` for the model) field; it assumes a new value will be auto-incremented.
It also does not copy the `created_at` or `updated_at`.
You can add additional attributes to ignore as follows:

```php
class Photo extends Eloquent {
   use \Vdlp\EloquentModelCloner\Cloneable;

   protected $cloneExemptAttributes = ['uid', 'source'];

   public function article() {
        return $this->belongsTo('Article');
   }

   public function onCloning($src, $child = null) {
        $this->uid = str_random();

        if ($child) {
            echo 'This was cloned as a relation!';
        }

        echo 'The original key is: ' . $src->getKey();
   }
}
```

The `$cloneExemptAttributes` adds to the defaults.
If you want to replace the defaults altogether, override the trait's `getCloneExemptAttributes()` method and return an array.

Also, note the `onCloning()` method in the example.
It is being used to make sure a unique column stays unique.
The `Cloneable` trait adds to no-op callbacks that get called immediately before a model is saved during a duplication and immediately after: `onCloning()` and `onCloned()`.
The `$child` parameter allows you to customize the behavior based on if it's being cloned as a relation or direct.

In addition, Cloner fires the following Laravel events during cloning:

- `cloner::cloning: ModelClass`
- `cloner::cloned: ModelClass`

`ModelClass` is the classpath of the model being cloned.
The event payload contains the clone and the original model instances.
