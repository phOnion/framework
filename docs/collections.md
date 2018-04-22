# Introduction

There are 3 pretty generic collection classes that should provide at
least some benefit in a few scenarios.

## Collection

The base collection class is just an iterator and exposes 1 additional
method `addFilter` which accepts a callback and passing it `$value` and `$key`
in this order, under the hood it uses the built-in PHP `CallbackFilterIterator` to
perform the filtering.

## CallbackCollection

This one is aimed at applying a callback to each element in the array
much like `array_map` does, but instead of applying it instantly it is
lazy and the callback is applied only when an item is retrieved
from the iterator. The goal of that is to have as lazy as possible
collection. Which when working with large datasets will manipulate them
on the fly rather than in a single go.

It extends `Collection` so the filtering is still there, just note that
the callback is applied after the filtering so the filter function has
to use the raw data.

## HydratableCollection

This one a thin wrapper around `CallbackCollection` to provide a simplified
API to generate object specific collections. It takes a prototype instance
that implements `HydratableInterface` as it's 2nd argument in the constructor.

Just note that the filtering will be applied *after* the hydration since
the objects might have their own validation procedures in place and thus
the filtering could be `$item->isVallid()` instead of duplicating the logic
every you have to validate/filter the collection.
