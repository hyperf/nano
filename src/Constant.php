<?php


namespace Hyperf\Nano;


class Constant
{
    /**
     * Merge the config using array_merge_recursive
     */
    public const CONFIG_MERGE = 1;
    /**
     * Replace the config with array_merge
     */
    public const CONFIG_REPLACE = 1 << 1;
}
