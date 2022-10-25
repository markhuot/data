# Simple data objects

Data (or value) objects can be used to represent data that may not otherwise have a strict class in your application. For example, POST data coming from a web request or JSON data coming from an API call. In these situations you may want a strongly typed version of the data but not want to write all the boilerplate to cast the data from the source to your data object.

This package handles all that boilerplate for you and aims to cover 80% of the most common use cases with the ability to extend the package should you need more specific transformations.

At it's most basic level the package is a transformer from your source representation (usually loosely typed) in to the target representation (more strongly typed). It looks like this (seriously).

```php
class Repository {
    public int $id;
    public string $name;
    public string $fullName;
    public array $topics;
    public bool $private;
}
```

The goal of this package is to be able to layer this package on top of your existing data objects without needing much (if any) customization. It works off attribute labels and tries to infer as much through convention as possible.

To convert source data in to your data object you'll call `->fill($data)` on the data builder.

```php
(new Data(new MyAwesomeObject))->fill($data)->get();
```

When you call `->fill($data)` the package will use introspection to determine how the source `$data` maps to the properties in the `Repository` object. By default there is _no_ mapping and it will blindly look for source fields like `id` and push them in to the destination property of `->id`.

When the mapping is not 1:1 and you need to more control over the field naming you can use the `MapFrom` attribute to help the transformer along.

```php
use markhuot\data\attributes\MapFrom;

class Repository {
    public int $id;
    public string $name;

    #[MapFrom('full_name')]
    public string $fullName;
}
```

That will pull the `full_name` field from the source and drop it in to the `->fullName` property in the destination. If your conversion is snake case to camel case there is a convient helper to do just that,

```php
use markhuot\data\attributes\MapFromCamel;

class Repository {
    public int $id;
    public string $name;

    #[MapFromCamel]
    public string $fullName;
}
```

If you find yourself using `MapFromCamel` several times over you can also apply the attribute to the class to have all property names converted in the same way,

```php
use markhuot\data\attributes\MapFromCamel;

#[MapFromCamel]
class Repository {
    public int $id;
    public string $name;
    public string $fullName;
}
```

In example all fields in the `Repository` class will be converted during transformation. `id` will remain `->id`, `name` will remain `->name` but `full_name` will automatically map to `->fullName`.

## Nested maps

By default nested properties will be mapped based on the type hint. The typehit can either come from PHP or the docblock depending on your needs. For single object nesting you can use native PHP type hints like this,

```php
#[MapFromCamel]
class Repository {
    public int $id;
    public string $name;
    public string $fullName;
    public Owner $owner;
    public array $topics;
    public bool $private;
}

#[MapFromCamel]
class Owner {
    public int $id;
    public string $login;
    public string $avatarUrl;
}
```

This will map the source data `['owner' => ['id' => 1, 'login' => 'foo']]` over to a `Repository` with an `->owner` property that is correctly set to an `Owner` instance with `->id` and `->login` set on the `Owner` instance.

For more advanced mappings you can use a docblock to define properties that PHP doesn't yet understand. For example,

```php
#[MapFromCamel]
class Release {
    public int $id;
    public string $tagName;

    /** @var Asset[] */
    public array $assets;
}

#[MapFromCamel]
class Asset {
    public int $id;
    public string $name;
    public string $url;
}
```

This will correctly read that `->assets` expects to be an array of assets and will try to transform the source in to an array of objects. It would work with the following source data,

```json
{
    "id": "1",
    "tag_name": "1.0.0",
    "assets": [
        {
            "id": "1",
            "name": "1.0.0.zip",
            "url" "..."
        },
        {
            "id": "2",
            "name": "1.0.1.zip",
            "url" "..."
        }
    ]
}
```

## Validation

Because many times strict typing isn't enough to ensure the data is correct you can also use the [Symfony validation component](https://symfony.com/doc/current/validation.html) to validate the data after the `->fill()` process.

```php
use Symfony\Component\Validator\Constraints as Assert;

class CreateBlogPostData {
    #[Assert\NotNull]
    public int $authorId;
    
    #[Assert\NotBlank]
    public string $title;
    
    #[Assert\Regex('/[a-z0-9_-]+/')]
    public ?string $slug;
}

$data = (new Data(new CreateBlogPostData))->fill($_POST)->validate()->get();
```