<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\InstitutionResource;
use App\Services\InstitutionTreeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstitutionController extends Controller
{
    public function index(InstitutionTreeService $institutionTree): AnonymousResourceCollection
    {
        return InstitutionResource::collection($institutionTree->tree());
    }
}
