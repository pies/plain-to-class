<?php

declare(strict_types=1);

namespace ClassTransformer\Attributes;

use Attribute;

/**
 *
 * @psalm-api
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class EmptyToNull
{
}