<?php

namespace Packstub\AccountSwitcher\Tests\Fixtures;

/**
 * A user model pinned to one connection, the way multi-database tenancy
 * packages pin the central user model while tenant requests run on
 * another default connection.
 */
class CentralUser extends User
{
    protected $connection = 'sqlite';
}
