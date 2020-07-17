<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf Nano.
 *
 * @link     https://www.hyperf.io
 * @document https://nano.hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/nano/blob/master/LICENSE
 */
namespace Hyperf\Nano;

class Constant
{
    /**
     * Merge the config using array_merge_recursive.
     */
    public const CONFIG_MERGE = 1;

    /**
     * Replace the config with array_merge.
     */
    public const CONFIG_REPLACE = 1 << 1;
}
