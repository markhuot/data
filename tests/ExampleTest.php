<?php

use markhuot\data\attributes\MapFrom;
use markhuot\data\attributes\MapFromCamel;
use markhuot\data\attributes\Skip;
use markhuot\data\Data;
use markhuot\data\DataInterface;
use markhuot\data\DataObject;
use markhuot\data\exceptions\ValidationException;
use Symfony\Component\Validator\Constraints as Assert;

class Foo {
    public $camelCased;
}
test('vanilla mapping', function () {
    $foo = (new Data(new Foo))->fill(['camelCased' => 'bar'])->get();
    
    expect($foo)->camelCased->toBe('bar');
});

class SpecificMapFrom {
    #[MapFrom('baz')]
    public $foo;
}
test('maps specific fields', function () {
    $foo = (new Data(new SpecificMapFrom))->fill(['baz' => 'bar'])->get();

    expect($foo)->foo->toBe('bar');
});

class AttrOnProperty {
    #[MapFromCamel]
    public $camelCased;
}
test('snake case mapping on property', function () {
    $foo = (new Data(new AttrOnProperty))->fill(['camel_cased' => 'bar'])->get();

    expect($foo)->camelCased->toBe('bar');
});

#[MapFromCamel]
class AttrOnClass {
    public $camelCased;
}
test('snake case mapping on class', function () {
    $foo = (new Data(new AttrOnClass))->fill(['camel_cased' => 'bar'])->get();
    
    expect($foo)->camelCased->toBe('bar');
});

class DataObjectSubclass extends DataObject {
    public $camelCased;
}
test('mapping via subclass', function () {
    $foo = new DataObjectSubclass(['camelCased' => 'bar']);
    
    expect($foo)->camelCased->toBe('bar');
});

class ParentClass implements DataInterface {
    /** @var ChildClass[] */
    public array $children;
}
class ChildClass implements DataInterface {
    public string $name;
}
test('mapping nested objects', function () {
    $foo = (new Data(new ParentClass))->fill(['children' => [['name' => 'foo'], ['name' => 'bar']]])->validate()->get();

    expect($foo)
        ->children->{'0'}->name->toBe('foo')
        ->children->{'1'}->name->toBe('bar');
});

class SkippingProperties {
    public $foo;

    #[Skip]
    public $bar = 'baz';
}
test('skips properties', function () {
    $foo = (new Data(new SkippingProperties))->fill(['foo' => 'fooz', 'bar' => 'barz'])->get();

    expect($foo)->bar->toBe('baz');
});

class Validates {
    #[Assert\NotBlank]
    #[Assert\Length(min:2, max: 10)]
    public $name;
}
test('validates required fields', function () {
    $this->expectException(ValidationException::class);

    (new Data(new Validates))->fill(['name' => null])->validate();
});
