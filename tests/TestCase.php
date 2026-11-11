<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Traits that drop or truncate tables. The suite runs against the shop
     * database from .env, so any of them would wipe the running shop.
     *
     * @var array<int, class-string>
     */
    private const SCHEMA_RESETTING_TRAITS = [
        RefreshDatabase::class,
        LazilyRefreshDatabase::class,
        DatabaseMigrations::class,
        DatabaseTruncation::class,
    ];

    /**
     * Fail before the application boots when a test would reset the shop database.
     */
    protected function setUp(): void
    {
        $resettingTraits = array_intersect(self::SCHEMA_RESETTING_TRAITS, class_uses_recursive(static::class));

        if ($resettingTraits !== []) {
            $this->fail(static::class.' uses '.class_basename(reset($resettingTraits)).', which drops the shop database. Use DatabaseTransactions instead.');
        }

        parent::setUp();
    }
}
