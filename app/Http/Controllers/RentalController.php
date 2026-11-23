<?php

namespace App\Http\Controllers;

use App\Services\Contracts\PropertyServiceInterface;
use App\Services\Contracts\PropertyTypeServiceInterface;

/**
 * @deprecated Use App\Http\Controllers\PropertyTypeController instead.
 */
class RentalController extends PropertyTypeController
{
    public function __construct(
        PropertyTypeServiceInterface $propertyTypeService,
        PropertyServiceInterface $propertyService
    ) {
        parent::__construct($propertyTypeService, $propertyService);
    }
}
