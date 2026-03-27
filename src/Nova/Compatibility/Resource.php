<?php

namespace Topoff\LaravelUserLogger\Nova\Compatibility;

use Laravel\Nova\Resource;

if (class_exists(\Laravel\Nova\Resource::class)) {
    abstract class Resource extends \Laravel\Nova\Resource {}
} else {
    abstract class Resource {}
}
