<?php

/*
 * Project:  Broring (broring-radicle)
 * Origineel: app/Contracts/Builders/SchemaBuilderInterface.php
 * Klein contract voor de schema-builders.
 */

namespace App\Contracts\Builders;

interface SchemaBuilderInterface
{
    public function build(): array;
}
