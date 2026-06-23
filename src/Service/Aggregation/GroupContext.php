<?php

namespace App\Service\Aggregation;

/**
 * Lightweight DTO that abstracts the "group" concept (Aggregation or Comarca).
 *
 * Strategies receive a GroupContext and never need to know which concrete target
 * type they are operating on — the SQL JOIN fragment is already built for them.
 *
 * The :groupId bind parameter is always used for the group's primary key.
 * The _grp alias is reserved for the join fragment to avoid name collisions.
 */
class GroupContext
{
    public function __construct(
        public readonly int    $id,
        public readonly string $type,          // 'aggregation' | 'comarca'
        public readonly string $memberJoinSql, // SQL JOIN fragment to filter municipalities
    ) {}
}
