<?php

declare(strict_types=1);

namespace Phast\Database;

/**
 * Contract for database seeders.
 * Dependencies (e.g. ConnectionInterface, models) are injected via the seeder constructor and resolved by the container.
 */
interface SeederInterface
{
    /**
     * Run the seeder.
     */
    public function run(): void;
}
